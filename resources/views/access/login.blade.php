@extends('layouts.access')
@section('title', 'Member sign-in')
@section('progress') @include('access.progress', ['step' => 1]) @endsection
@section('heading') Members’ <em>entrance.</em> @endsection
@section('intro', 'Sign in with your username and password. Have your authenticator handy.')
@section('form')
<form method="post" action="/login" class="fields">
    @csrf
    <label for="username">Username</label>
    <input id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="40" value="{{ old('username') }}" required>
    <label for="password">Password</label>
    <div class="password-wrap">
        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="128" required>
        <button type="button" class="reveal" data-reveal="password" aria-controls="password" aria-pressed="false" hidden>Show</button>
    </div>
    <button class="primary" type="submit">Continue</button>
</form>
<p class="after-form">First visit? <a href="/join">Use an invitation</a></p>
@endsection
