@extends('auth.account-layout')
@section('content')
<section class="panel"><div class="panel-body"><h1>Change your password</h1>
@if(auth()->user()->password_change_required)<p>Your Admin provided your initial credentials. You can set your own password now.</p>@endif
<form method="post" action="/password/change">@csrf
<label for="current">Current password</label><input id="current" type="password" name="current_password" required autocomplete="current-password">
<label for="password">New password</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
<label for="confirmation">Confirm new password</label><input id="confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
<button class="btn btn-primary">Save password</button></form>
<div class="settings-links"><a class="btn" href="/settings">Profile settings</a><a class="btn" href="/species">Continue to livestock selection</a></div></div></section>
@endsection
