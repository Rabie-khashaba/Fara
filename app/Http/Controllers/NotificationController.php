<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserDeviceToken;
use App\Models\AppUserNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function index(Request $request): View
    {
        $query = AppUserNotification::query()
            ->with(['sender', 'recipient']);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('sender', fn ($senderQuery) => $senderQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('recipient', fn ($recipientQuery) => $recipientQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('sent_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('sent_at', '<=', $dateTo);
        }

        return view('notifications.index', [
            'notifications' => $query->latest('sent_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('notifications.create', [
            'appUsers' => AppUser::query()
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender_app_user_id' => ['required', 'exists:app_users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $sender = AppUser::query()->findOrFail($validated['sender_app_user_id']);

        $recipients = AppUserDeviceToken::query()
            ->whereHas('appUser', fn ($query) => $query->whereKeyNot($sender->id))
            ->with('appUser:id')
            ->get(['app_user_id', 'token']);

        if ($recipients->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['title' => 'No users with valid FCM tokens were found.']);
        }

        $result = $this->firebaseNotificationService->sendToTokens(
            $recipients->pluck('token')->all(),
            $validated['title'],
            $validated['body'],
            []
        );

        foreach ($result['data']['results'] ?? [] as $notificationResult) {
            if (! ($notificationResult['status'] ?? false) || empty($notificationResult['token'])) {
                continue;
            }

            $recipient = $recipients->firstWhere('token', $notificationResult['token']);
            if (! $recipient) {
                continue;
            }

            AppUserNotification::query()->create([
                'sender_app_user_id' => $sender->id,
                'recipient_app_user_id' => $recipient->app_user_id,
                'target_fcm_token' => (string) $notificationResult['token'],
                'title' => $validated['title'],
                'body' => $validated['body'],
                'data' => null,
                'is_read' => false,
                'read_at' => null,
                'sent_at' => now(),
            ]);
        }

        $message = sprintf(
            'Notification processed. Success: %d, Failed: %d.',
            (int) ($result['data']['success_count'] ?? 0),
            (int) ($result['data']['failed_count'] ?? 0)
        );

        return redirect()
            ->route('notifications.index')
            ->with('status', $message);
    }
}
