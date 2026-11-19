@extends('layouts.access')
@section('title', 'Verify your code')
@section('progress') @include('access.progress', ['step' => 2]) @endsection
@section('heading') Check your <em>app.</em> @endsection
@section('intro', 'Open The Parlour in your authenticator app and enter the current six-digit code.')
@section('form')
<form method="post" action="/verify" class="fields">
    @csrf
    <label for="code">Authenticator code</label>
    <input class="otp" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required>
    <button class="primary" type="submit">Enter the parlour</button>
</form>
<form method="post" action="/logout" class="after-form">
    @csrf <button class="text-button">Back to sign-in</button>
</form>
@endsection
