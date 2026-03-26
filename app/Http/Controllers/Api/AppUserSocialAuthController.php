<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppUserAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Throwable;

class AppUserSocialAuthController extends Controller
{
    public function __construct(
        protected AppUserAuthService $authService
    ) {
    }

    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        try {
            return $this->authService->redirectToSocialProvider($provider);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Social login is not configured correctly.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function callback(string $provider): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->authService->socialLoginByCallback($provider);

            if (! empty($result['redirect_to'])) {
                return redirect()->away($result['redirect_to']);
            }

            $appRedirect = $this->appRedirectUrl($provider, $result);

           dd(config('services.apple.redirect'));

            if ($appRedirect) {
                return redirect()->away($appRedirect);
            }

            if (isset($result['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => $result['error'],
                    'data' => $result,
                ], $result['code'] ?? 400);
            }

            return response()->json([
                'status' => true,
                'message' => $result['message'] ?? 'Success',
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function appRedirectUrl(string $provider, array $result): ?string
    {
        if (empty($result['token']) || empty($result['user'])) {
            return null;
        }

        $base = config("services.{$provider}.app_redirect");

        if (! $base) {
            return null;
        }

        $separator = str_contains($base, '?') ? '&' : '?';
        $query = http_build_query([
            'token' => $result['token'],
            'name' => $result['user']['full_name'] ?? null,
            'email' => $result['user']['email'] ?? null,
        ]);

        return $base . $separator . $query;
    }
}
