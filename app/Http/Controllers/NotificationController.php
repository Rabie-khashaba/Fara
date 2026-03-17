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
            'recipient_app_user_id' => ['required', 'exists:app_users,id', 'different:sender_app_user_id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $sender = AppUser::query()->findOrFail($validated['sender_app_user_id']);
        $recipient = AppUser::query()->findOrFail($validated['recipient_app_user_id']);

        $recipientTokens = $this->tokensForUser($recipient);

        if ($recipientTokens->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['recipient_app_user_id' => 'This user does not have any valid FCM tokens.']);
        }

        $result = $this->firebaseNotificationService->sendToTokens(
            $recipientTokens->all(),
            $validated['title'],
            $validated['body'],
            []
        );

        foreach ($result['data']['results'] ?? [] as $notificationResult) {
            $this->storeNotificationResult($sender, $recipient, $notificationResult, $validated['title'], $validated['body']);
        }

        $message = sprintf(
            'Notification processed for %s. Success: %d, Failed: %d.',
            $recipient->name,
            (int) ($result['data']['success_count'] ?? 0),
            (int) ($result['data']['failed_count'] ?? 0)
        );

        return redirect()
            ->route('notifications.index')
            ->with('status', $message);
    }

    public function storeAll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender_app_user_id' => ['required', 'exists:app_users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $sender = AppUser::query()->findOrFail($validated['sender_app_user_id']);
        $recipients = AppUser::query()
            ->with('deviceTokens')
            ->whereKeyNot($sender->id)
            ->where(function ($query) {
                $query
                    ->whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->orWhereHas('deviceTokens');
            })
            ->orderBy('name')
            ->get();

        if ($recipients->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['title' => 'No users with FCM tokens were found.']);
        }

        $tokensByRecipient = [];
        $allTokens = collect();

        foreach ($recipients as $recipient) {
            $tokens = $this->tokensForUser($recipient);
            if ($tokens->isEmpty()) {
                continue;
            }

            $tokensByRecipient[$recipient->id] = $tokens;
            $allTokens = $allTokens->merge($tokens);
        }

        $allTokens = $allTokens->unique()->values();

        if ($allTokens->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['title' => 'No valid FCM tokens were found for the selected users.']);
        }

        $result = $this->firebaseNotificationService->sendToTokens(
            $allTokens->all(),
            $validated['title'],
            $validated['body'],
            []
        );

        foreach ($recipients as $recipient) {
            foreach ($result['data']['results'] ?? [] as $notificationResult) {
                if (! isset($tokensByRecipient[$recipient->id])) {
                    continue;
                }

                if (! $tokensByRecipient[$recipient->id]->contains((string) ($notificationResult['token'] ?? ''))) {
                    continue;
                }

                $this->storeNotificationResult($sender, $recipient, $notificationResult, $validated['title'], $validated['body']);
            }
        }

        $message = sprintf(
            'Notification processed for %d users. Success: %d, Failed: %d.',
            count($tokensByRecipient),
            (int) ($result['data']['success_count'] ?? 0),
            (int) ($result['data']['failed_count'] ?? 0)
        );

        return redirect()
            ->route('notifications.index')
            ->with('status', $message);
    }

    private function tokensForUser(AppUser $user)
    {
        $deviceTokens = $user->relationLoaded('deviceTokens')
            ? $user->deviceTokens->pluck('token')
            : AppUserDeviceToken::query()->where('app_user_id', $user->id)->pluck('token');

        return $deviceTokens
            ->push($user->fcm_token)
            ->filter(fn ($token) => filled($token))
            ->map(fn ($token) => (string) $token)
            ->unique()
            ->values();
    }

    private function storeNotificationResult(
        ?AppUser $sender,
        AppUser $recipient,
        array $notificationResult,
        string $title,
        string $body
    ): void {
        if (! ($notificationResult['status'] ?? false) || empty($notificationResult['token'])) {
            return;
        }

        AppUserNotification::query()->create([
            'sender_app_user_id' => $sender?->id,
            'recipient_app_user_id' => $recipient->id,
            'target_fcm_token' => (string) $notificationResult['token'],
            'title' => $title,
            'body' => $body,
            'data' => null,
            'is_read' => false,
            'read_at' => null,
            'sent_at' => now(),
        ]);
    }
}
