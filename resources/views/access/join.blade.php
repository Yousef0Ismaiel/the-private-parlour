@extends('layouts.access')
@section('title', 'Use an invitation')
@section('heading') You’re <em>invited.</em> @endsection
@section('intro', 'Enter the invitation code your host gave you. You’ll choose a password and pair your phone.')
@section('form')
<form method="post" action="/join" class="fields">
    @csrf
    <label for="invitation">Invitation code</label>
    <input id="invitation" name="invitation" type="text" maxlength="100" autocomplete="off" spellcheck="false" required>
    <button class="primary" type="submit">Use invitation</button>
</form>
<p class="after-form">Already a member? <a href="/login">Sign in</a></p>
@endsection
