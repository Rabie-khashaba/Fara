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

    public function callback(string $provider): JsonResponse
    {
        try {
            $result = $this->authService->socialLoginByCallback($provider);

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
}
