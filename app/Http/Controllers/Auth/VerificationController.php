<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class VerificationController extends Controller
{
    /**
     * Mark the signed verification link's email as verified.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = User::findOrFail((int) $request->route('id'));

        if ($request->route('hash') === null || ! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            throw new InvalidSignatureException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->markEmailAsVerified();

        session()->flash('success', 'Your email address has been verified.');

        return redirect()->route('dashboard');
    }
}
