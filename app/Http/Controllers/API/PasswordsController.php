<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Mail\SendPasswordResetOpt;
use App\Models\User;
use App\Services\MailService;
use Carbon\Carbon;
use Exception;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

final class PasswordsController extends BaseController
{
    public function forgotPassword(Request $request, MailService $mailService): JsonResponse
    {
        $request->validate(['email' => "required|email|exists:App\Models\User,email"]);

        // return $this->sendResponse([], 'In progress.');

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError('No Record Found.', ['error' => 'Email address could not found']);
        }

        $otp = rand(1000, 9999);

        $user->fill(['reset_password_token' => $otp, 'reset_password_sent_at' => Carbon::now()])->save();

        try {

            $mailService->sendPasswordResetEmail($user->email);
            // Mail::to($request->email)->send(new SendPasswordResetOpt($user));
        } catch (Exception $e) {
            return $this->sendError('Error.', ['error' => 'Unable to complete your request', 'e' => (string) $e]);
        }

        // return $this->sendResponse([$user->only(['reset_password_token','email'])], 'One time verification code for password reset has been sent to email address');
        return $this->sendResponse([$user->only(['email'])], 'One time verification code for password reset has been sent to email address');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => "required|email|exists:App\Models\User,email",
            'otp' => 'required', // |exists:App\Models\User,reset_password_token",
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError('No Record Found.', ['error' => 'Email address could not found']);
        }

        if ($user->reset_password_token !== $request->otp) {
            return $this->sendError('No Record Found.', ['error' => 'Invalid verification code']);
        }

        $user->fill(['encrypted_password' => $request->password, 'reset_password_token' => null])->save();

        return $this->sendResponse([], 'You password has been reset');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', function ($attr, $password, $validation) use ($user) {
                if (! Hash::check($password, $user->encrypted_password)) {
                    return $validation(__('The current password is incorrect.'));
                }
            }],
            'password' => 'required|min:8|confirmed',
        ]);

        $user->fill(['encrypted_password' => $request->password])->save();

        return $this->sendResponse([], 'You password has been updated');
    }
}
