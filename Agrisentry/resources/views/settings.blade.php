@extends('auth.account-layout')
@section('content')
<div class="page-header"><div><h1 class="page-title">Settings</h1><p class="page-sub">Manage your profile, recovery contacts, and account security.</p></div><span class="tag">{{ $user->role === 'Staff' ? 'Cooperative Staff' : $user->role }}</span></div>
<nav class="settings-sections" aria-label="Settings sections"><a class="btn" href="#profile">Personal information</a><a class="btn" href="#security">Password &amp; security</a>@if($user->isAdmin())<a class="btn" href="#administration">Administration</a>@endif</nav>
<div class="settings-grid">
<section class="panel" id="profile"><div class="panel-head"><h2 class="panel-title">Personal information</h2></div><div class="panel-body">
<p class="page-sub">Keep your details up to date. Your role is assigned by the Admin.</p>
<form action="/settings" method="post">@csrf @method('PUT')
<div class="form-grid">
<div class="form-field full"><label for="profile-name">Full name</label><input id="profile-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"></div>
<div class="form-field"><label for="profile-username">Username</label><input id="profile-username" name="username" value="{{ old('username', $user->username) }}" required maxlength="100" pattern="[A-Za-z0-9_.\-]+" autocomplete="username"><span class="form-hint">Use this username to sign in.</span></div>
<div class="form-field full"><h3 class="settings-subtitle">Recovery contacts</h3></div><div class="form-field full"><label for="profile-email">Recovery email</label><input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></div>
<div class="form-field full"><label for="profile-phone">Primary phone number</label><input id="profile-phone" type="tel" name="phone_number" value="{{ old('phone_number', $user->phoneNumbers->sortByDesc('is_primary')->first()?->phone_number) }}" pattern="\+?[0-9]{10,15}" placeholder="e.g. +639171234567" autocomplete="tel"><span class="form-hint">Used for password recovery. Leave blank to keep your current phone numbers.</span></div>
<div class="form-field full"><h3 class="settings-subtitle">Confirm changes</h3><label for="profile-current">Confirm with current password</label><input id="profile-current" type="password" name="current_password" required autocomplete="current-password"></div>
</div><button class="btn btn-primary" type="submit">Save profile</button>
</form></div></section>
<section class="panel" id="security"><div class="panel-head"><h2 class="panel-title">Password &amp; security</h2></div><div class="panel-body">
@if($user->password_change_required)<p class="message">Your Admin provided your initial password. Set your own password here.</p>@endif
<p class="page-sub">Choose a password with at least 8 characters.</p>
<form method="post" action="/password/change">@csrf
<div class="form-field"><label for="security-current">Current password</label><input id="security-current" type="password" name="current_password" required autocomplete="current-password"></div>
<div class="form-field"><label for="security-new">New password</label><input id="security-new" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
<div class="form-field"><label for="security-confirm">Confirm new password</label><input id="security-confirm" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
<button class="btn btn-primary">Change password</button></form>
</div></section>
</div>
@if($user->isAdmin())
<section class="panel" id="administration"><div class="panel-head"><h2 class="panel-title">Administration</h2></div><div class="panel-body"><p class="page-sub">Manage accounts, feature access, and user activity.</p><div class="settings-links"><a class="btn" href="/admin/users">Manage users</a><a class="btn" href="/admin/access">Access control &amp; activity logs</a></div></div></section>
@endif
@endsection
