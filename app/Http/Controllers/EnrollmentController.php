<?php

namespace App\Http\Controllers;

use App\Auth\MemberSession;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class EnrollmentController
{
    public function redeem(Request $request, Google2FA $totp): RedirectResponse
    {
        $input = $request->validate(['invitation' => ['required', 'string', 'max:100']]);
        $code = strtoupper(str_replace([' ', '-'], '', trim($input['invitation'])));
        $hash = hash('sha256', $code);
        $member = User::where('invite_hash', $hash)->whereNull('password')
            ->where('invite_expires_at', '>', now())->first();

        if (! $member) {
            return redirect('/join')->withErrors(['entry' => 'This invitation is invalid or has expired.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('entry', [
            'mode' => 'enroll', 'user' => $member->id, 'invite_hash' => $hash,
            'secret' => $totp->generateSecretKey(32),
            'until' => min(now()->addMinutes(15)->timestamp, $member->invite_expires_at->timestamp),
        ]);
        return redirect('/pair');
    }

    public function form(Request $request, Google2FA $totp)
    {
        $member = $this->pending($request);
        if (! $member) {
            return redirect('/join')->with('notice', 'Enter a current invitation to pair your phone.');
        }
        $secret = $request->session()->get('entry.secret');
        $uri = $totp->getQRCodeUrl('The Parlour', $member->username, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 4), new SvgImageBackEnd()));
        $qr = 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($uri));
        return view('access.pair', compact('member', 'qr', 'secret'));
    }

    public function finish(Request $request, Google2FA $totp): RedirectResponse
    {
        $member = $this->pending($request);
        if (! $member) {
            return redirect('/join')->with('notice', 'Your setup session expired. Enter your invitation again.');
        }

        $input = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:128', 'confirmed'],
            'code' => ['required', 'string', 'regex:/\A[0-9]{6}\z/'],
        ]);
        $secret = $request->session()->get('entry.secret');
        $step = $totp->verifyKeyNewer($secret, $input['code'], 0, 1);
        if ($step === false) {
            return redirect('/pair')->withErrors(['entry' => 'The code didn’t match. Check The Parlour entry in your app.']);
        }

        $claimed = User::whereKey($member->id)->whereNull('password')
            ->where('invite_hash', $request->session()->get('entry.invite_hash'))
            ->where('invite_expires_at', '>', now())
            ->update([
                'password' => Hash::make($input['password']),
                // Query-builder updates bypass model casts.
                'two_factor_secret' => Crypt::encryptString($secret),
                'last_totp_step' => $step, 'invite_hash' => null, 'invite_expires_at' => null,
            ]);

        if (! $claimed) {
            $request->session()->forget('entry');
            return redirect('/login')->with('notice', 'This invitation has already been used or replaced.');
        }
        return MemberSession::start($request, $member);
    }

    private function pending(Request $request): ?User
    {
        $pending = $request->session()->get('entry', []);
        if (($pending['mode'] ?? null) !== 'enroll' || ($pending['until'] ?? 0) <= now()->timestamp) {
            return null;
        }
        return User::whereKey($pending['user'])->whereNull('password')
            ->where('invite_hash', $pending['invite_hash'])->where('invite_expires_at', '>', now())->first();
    }
}
