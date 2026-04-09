<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AppUserPushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function index(Request $request): View
    {
        $query = SupportTicket::query()
            ->with(['appUser', 'assignedUser', 'latestMessage.senderUser', 'latestMessage.senderAppUser']);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('appUser', function ($subQuery) use ($search) {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($request->input('status'), [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_CLOSED], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return view('support-tickets.index', [
            'tickets' => $query->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('support-tickets.create', [
            'appUsers' => AppUser::query()->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_user_id' => ['required', 'integer', 'exists:app_users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        $ticket = DB::transaction(function () use ($data, $user) {
            $ticket = SupportTicket::query()->create([
                'ticket_number' => $this->generateTicketNumber(),
                'app_user_id' => $data['app_user_id'],
                'assigned_user_id' => $user?->id,
                'created_by_user_id' => $user?->id,
                'subject' => $data['subject'],
                'status' => SupportTicket::STATUS_OPEN,
            ]);

            $message = $ticket->messages()->create([
                'sender_user_id' => $user?->id,
                'body' => $data['message'],
            ]);

            $ticket->update([
                'last_message_at' => $message->created_at,
            ]);

            return $ticket;
        });

        return redirect()
            ->route('support-tickets.show', $ticket)
            ->with('status', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $support_ticket): View
    {
        $support_ticket->load([
            'appUser',
            'assignedUser',
            'createdByUser',
            'createdByAppUser',
            'messages.senderUser',
            'messages.senderAppUser',
        ]);

        return view('support-tickets.show', [
            'ticket' => $support_ticket,
        ]);
    }

    public function storeMessage(Request $request, SupportTicket $support_ticket): RedirectResponse
    {
        abort_if($support_ticket->status === SupportTicket::STATUS_CLOSED, 422, 'Closed tickets cannot receive new messages.');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        $message = DB::transaction(function () use ($support_ticket, $data, $user) {
            $message = $support_ticket->messages()->create([
                'sender_user_id' => $user?->id,
                'body' => $data['message'],
            ]);

            $support_ticket->update([
                'assigned_user_id' => $support_ticket->assigned_user_id ?? $user?->id,
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });

        if ($support_ticket->appUser) {
            $this->pushNotificationService->sendToUser(
                $support_ticket->appUser,
                null,
                'Support team',
                "The support team has replied to your ticket: {$support_ticket->subject}.",
                [
                    'type' => 'support',
                    'support_ticket_id' => $support_ticket->id,
                    'support_message_id' => $message->id,
                    'sender_user_id' => $user?->id,
                ]
            );
        }

        return redirect()
            ->route('support-tickets.show', $support_ticket)
            ->with('status', 'Reply sent successfully.');
    }

    public function close(SupportTicket $support_ticket): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($support_ticket->status !== SupportTicket::STATUS_CLOSED) {
            $support_ticket->update([
                'status' => SupportTicket::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by_type' => 'user',
                'closed_by_id' => $user?->id,
                'assigned_user_id' => $support_ticket->assigned_user_id ?? $user?->id,
            ]);
        }

        return redirect()
            ->route('support-tickets.show', $support_ticket)
            ->with('status', 'Support ticket closed successfully.');
    }

    public function reopen(SupportTicket $support_ticket): RedirectResponse
    {
        $support_ticket->update([
            'status' => SupportTicket::STATUS_OPEN,
            'closed_at' => null,
            'closed_by_type' => null,
            'closed_by_id' => null,
        ]);

        return redirect()
            ->route('support-tickets.show', $support_ticket)
            ->with('status', 'Support ticket reopened successfully.');
    }

    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'SUP-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (SupportTicket::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
