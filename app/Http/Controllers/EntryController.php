<?php

namespace App\Http\Controllers;

use App\Auth\MemberSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class EntryController
{
    public function password(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'username' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string', 'max:128'],
        ]);
        $input['username'] = strtolower($input['username']);
        $guard = Auth::guard('web');

        // validate() checks credentials without authenticating the browser.
        if (! $guard->validate($input)) {
            return redirect('/login')->withErrors(['entry' => 'That username and password didn’t match.'])
                ->withInput(['username' => $input['username']]);
        }

        $member = $guard->getLastAttempted();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('entry', [
            'mode' => 'login', 'user' => $member->id, 'until' => now()->addMinutes(5)->timestamp,
        ]);
        return redirect('/verify');
    }

    public function challenge(Request $request)
    {
        if (! $this->hasPasswordChallenge($request)) {
            return redirect('/login')->with('notice', 'Enter your password to start a new sign-in.');
        }

        return view('access.verify');
    }

    public function verify(Request $request, Google2FA $totp): RedirectResponse
    {
        if (! $this->hasPasswordChallenge($request)) {
            return redirect('/login')->with('notice', 'Your sign-in expired. Enter your password again.');
        }
        $input = $request->validate(['code' => ['required', 'string', 'regex:/\A[0-9]{6}\z/']]);
        $member = User::find($request->session()->get('entry.user'));
        if (! $member || ! $member->two_factor_secret) {
            $request->session()->forget('entry');
            return redirect('/login');
        }

        $step = $totp->verifyKeyNewer($member->two_factor_secret, $input['code'], $member->last_totp_step, 1);

        // A single conditional write lets only one concurrent request spend the code.
        if ($step === false || ! User::whereKey($member->id)->where('last_totp_step', '<', $step)
            ->update(['last_totp_step' => $step])) {
            return redirect('/verify')->withErrors(['entry' => 'That code didn’t work. Try the next code from your app.']);
        }

        return MemberSession::start($request, $member);
    }

    private function hasPasswordChallenge(Request $request): bool
    {
        return $request->session()->get('entry.mode') === 'login'
            && $request->session()->get('entry.until', 0) > now()->timestamp;
    }

    public function leave(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
