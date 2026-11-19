<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class MemberSession
{
    public static function start(Request $request, User $member): RedirectResponse
    {
        $request->session()->flush();
        // Laravel's session guard rotates the ID and CSRF token on login.
        Auth::guard('web')->login($member);
        $request->session()->put('verified_member', $member->getAuthIdentifier());
        return redirect('/');
    }
}
