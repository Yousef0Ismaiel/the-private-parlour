@extends('layouts.access')
@section('title', 'Set up your account')
@section('layout-class', 'enrolling')
@section('heading') Make yourself <em>at home.</em> @endsection
@section('intro') Your username is <strong>{{ $member->username }}</strong>. Scan the QR code in Google Authenticator, Aegis, or another authenticator app. @endsection
@section('form')
<div class="pair-box">
    <img width="144" height="144" src="{{ $qr }}" alt="Scan this setup QR code with your authenticator app">
    <details class="manual-key">
        <summary>Enter the key manually</summary>
        <p>Add a time-based entry named <strong>The Parlour / {{ $member->username }}</strong>. Use 6 digits, 30 seconds and SHA-1.</p>
        <code>{{ implode(' ', str_split($secret, 4)) }}</code>
    </details>
</div>
<form method="post" action="/pair" class="fields">
    @csrf
    <label for="new-password">Choose a password <small>(at least 12 characters)</small></label>
    <input id="new-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required>
    <label for="confirm">Confirm password</label>
    <input id="confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="128" required>
    <label for="code">Six-digit code from your app</label>
    <input class="otp" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required>
    <button class="primary" type="submit">Finish setup and enter</button>
</form>
<form method="post" action="/logout" class="after-form">
    @csrf <button class="text-button">Cancel setup</button>
</form>
@endsection
