<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
    /**
     * Handle forgot password
     *
     * @param   ForgotPasswordRequest  $request  [$request description]
     *
     * @return  void
     */
    public function forgotPassword(ForgotPasswordRequest $request) {

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'A password reset link sent to your email'
            ]);
        }

        return response()->json([
            'message' => 'Something went wrong to reset password'
        ], 409);
    }

    /**
     * Handle reset password
     *
     * @param   PasswordResetRequest  $request  [$request description]
     *
     * @return  [type]                          [return description]
     */
    public function resetPassword(PasswordResetRequest $request) {
        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function(User $user, string $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ]);

                $user->save();

                Auth::login($user);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {

            return response()->json([
                'message' => 'Password successfully reset',
                'data'    => [
                    'access_token' => Auth::user()->createAuthToken(),
                    'token_type' => 'Bearer',
                ]
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired token'
        ], 422);
    }
}
