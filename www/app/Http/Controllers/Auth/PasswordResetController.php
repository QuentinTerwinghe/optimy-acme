<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\PasswordResetServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller for handling password reset operations.
 * Follows SOLID principles: Thin controller that delegates to service.
 */
class PasswordResetController extends Controller
{
    /**
     * @param PasswordResetServiceInterface $passwordResetService
     */
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService
    ) {
    }

    /**
     * Show the forgot password form.
     *
     * @return View
     */
    public function showForgotPasswordForm(): View
    {
        /** @var view-string $viewName */
        $viewName = 'auth.forgot-password';
        return view($viewName);
    }

    /**
     * Handle forgot password request.
     * Creates token and dispatches notification job to RabbitMQ.
     *
     * @param ForgotPasswordRequest $request
     * @return RedirectResponse
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        try {
            $this->passwordResetService->createTokenAndDispatchNotification(
                $request->validated('email')
            );

            return redirect()
                ->route('password.request')
                ->with('status', 'We have sent you a password reset link!');
        } catch (\Exception $e) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Failed to send password reset link. Please try again.']);
        }
    }

    /**
     * Show the reset password form.
     *
     * @param string $token
     * @return View
     */
    public function showResetPasswordForm(string $token): View
    {
        $email = request()->query('email', '');

        /** @var view-string $viewName */
        $viewName = 'auth.reset-password';
        return view($viewName, [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle reset password request.
     *
     * @param ResetPasswordRequest $request
     * @return RedirectResponse
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->passwordResetService->resetPassword(
                $validated['email'],
                $validated['token'],
                $validated['password']
            );

            return redirect()
                ->route('login.form')
                ->with('status', 'Your password has been reset successfully! You can now login.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->withErrors(['token' => 'Invalid or expired password reset token.']);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'Failed to reset password. Please try again.']);
        }
    }
}
