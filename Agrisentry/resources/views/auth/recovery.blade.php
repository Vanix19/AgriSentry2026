@extends('auth.account-layout')
@section('content')
@php($step = !$recovery ? 1 : (isset($recovery['reset_token']) ? 3 : 2))
<section class="panel" style="max-width:600px;margin:0 auto"><div class="panel-body">
<p class="page-sub">Step {{ $step }} of 3</p>
<h1>{{ [1 => 'Reset your password', 2 => 'Verify your OTP', 3 => 'Set your new password'][$step] }}</h1>
@if($step === 1)
<p>Receive a one-time code at the email address or phone number registered by your Admin.</p>
<form method="post" action="/password/otp">@csrf
<label for="username">Username</label><input id="username" name="username" required autocomplete="username" value="{{ old('username') }}">
<label for="channel">Send code through</label><select id="channel" name="channel"><option value="email">Registered email (Gmail)</option><option value="phone">Registered phone number</option></select>
<button class="btn btn-primary">Send OTP</button></form>
@elseif($step === 2)
<p>Enter the six-digit code for <strong>{{ $recovery['username'] }}</strong>. Once verified, you can choose a new password.</p>
<form method="post" action="/password/verify" id="otp-form">@csrf
<input type="hidden" name="username" value="{{ $recovery['username'] }}">
<label for="otp">Six-digit OTP</label><input id="otp" name="otp" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus>
<button class="btn btn-primary">Verify OTP</button></form>
<form method="post" action="/password/otp">@csrf
<input type="hidden" name="username" value="{{ $recovery['username'] }}"><input type="hidden" name="channel" value="{{ $recovery['channel'] ?? 'email' }}">
<button class="btn" type="submit">Resend OTP</button>
<a class="btn" href="/password/recovery?restart=1">Change account or delivery method</a></form>
@else
<p>Your code is verified. Choose a password with at least eight characters.</p>
<form method="post" action="/password/reset">@csrf
<label for="password">New password</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" autofocus>
<label for="confirmation">Confirm new password</label><input id="confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
<button class="btn btn-primary">Reset password</button></form>
<a class="btn" href="/password/recovery?restart=1">Start again</a>
@endif
</div></section>
@include('auth.otp-autosubmit')
@endsection
