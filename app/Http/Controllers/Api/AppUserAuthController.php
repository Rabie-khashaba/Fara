<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserAuth\ForgotPasswordRequest;
use App\Http\Requests\Api\AppUserAuth\ForgotPasswordVerifyOtpRequest;
use App\Http\Requests\Api\AppUserAuth\LoginRequest;
use App\Http\Requests\Api\AppUserAuth\RegisterRequest;
use App\Http\Requests\Api\AppUserAuth\ResendRegisterOtpRequest;
use App\Http\Requests\Api\AppUserAuth\ResetPasswordRequest;
use App\Http\Requests\Api\AppUserAuth\SocialLoginRequest;
use App\Http\Requests\Api\AppUserAuth\VerifyRegisterOtpRequest;
use App\Services\AppUserAuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AppUserAuthController extends Controller
{
    public function __construct(
        protected AppUserAuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->executeSafely(
            fn (): array => $this->authService->requestRegistration($request->validated()),
            201
        );
    }

    public function verifyRegisterOtp(VerifyRegisterOtpRequest $request): JsonResponse
    {
        return $this->executeSafely(function () use ($request): array {
            $data = $request->validated();

            return $this->authService->completeRegistration($data['phone'], $data['otp']);
        });
    }

    public function resendRegisterOtp(ResendRegisterOtpRequest $request): JsonResponse
    {
        return $this->executeSafely(function () use ($request): array {
            $data = $request->validated();
            return $this->authService->resendRegisterOtp($data['phone']);
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->executeSafely(function () use ($request): array {
            $data = $request->validated();

            return $this->authService->loginByPhone(
                $data['phone'],
                $data['password'],
                $data['fcm_token'] ?? null,
                $data['platform'] ?? null,
                $data['device_name'] ?? null
            );
        });
    }

    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        return $this->executeSafely(
            fn (): array => $this->authService->socialLogin($request->validated()),
            201
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->executeSafely(
            fn (): array => $this->authService->forgotPassword($request->validated()['phone'])
        );
    }

    public function forgotPasswordVerifyOtp(ForgotPasswordVerifyOtpRequest $request): JsonResponse
    {
        return $this->executeSafely(function () use ($request): array {
            $data = $request->validated();

            return $this->authService->verifyForgotPasswordOtp($data['phone'], $data['otp']);
        });
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->executeSafely(function () use ($request): array {
            $data = $request->validated();

            return $this->authService->resetPassword($data['phone'], $data['password']);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->executeSafely(
            fn (): array => $this->authService->logout($request->user())
        );
    }

    private function jsonResponse(array $result, int $successStatus = 200): JsonResponse
    {
        if (isset($result['error'])) {
            $payload = [
                'status' => false,
                'message' => $result['error'],
            ];

            if (array_key_exists('inactive_reason', $result)) {
                $payload['data'] = [
                    'inactive_reason' => $result['inactive_reason'],
                ];
            }

            return response()->json($payload, $result['code'] ?? 400);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'] ?? 'Success',
            'data' => $result,
        ], $successStatus);
    }

    private function executeSafely(Closure $callback, int $successStatus = 200): JsonResponse
    {
        try {
            $result = $callback();

            $status = $successStatus;

            if (isset($result['token'])) {
                $status = 200;
            }

            return $this->jsonResponse($result, $status);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
