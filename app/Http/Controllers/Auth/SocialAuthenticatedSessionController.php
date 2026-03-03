<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\SocialAuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class SocialAuthenticatedSessionController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService,
    ) {
    }

    public function redirect(string $provider): RedirectResponse
    {
        try {
            return $this->socialAuthService->redirectToProvider($provider);
        } catch (AuthenticationException | InvalidArgumentException $exception) {
            return redirect()->route('login')->withErrors([
                'social' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Social login redirect failed.', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'social' => 'Social login is not configured correctly. Please contact the administrator.',
            ]);
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        try {
            $this->socialAuthService->authenticate($provider);
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        } catch (AuthenticationException | InvalidArgumentException $exception) {
            return redirect()->route('login')->withErrors([
                'social' => $exception->getMessage(),
            ]);
        } catch (InvalidStateException $exception) {
            return redirect()->route('login')->withErrors([
                'social' => 'The login session expired. Please try again.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Social login callback failed.', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'social' => 'Social login failed. Please try again.',
            ]);
        }
    }
}
