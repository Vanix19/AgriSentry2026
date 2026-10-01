@extends('auth.account-layout')
@section('content')
<style>
#resend-notification { position:fixed; top:20px; right:20px; z-index:1000; display:flex; align-items:flex-start; gap:16px; width:min(440px,calc(100vw - 32px)); padding:16px 18px; background:var(--green-bg); color:var(--green-dk); border:1px solid var(--green-bg-strong); border-left:4px solid var(--green); border-radius:var(--radius-sm); box-shadow:var(--shadow-lg); }
#resend-notification[hidden] { display:none; }
#resend-notification[data-kind="error"] { background:var(--red-bg); color:var(--red); border-color:var(--red-bg-strong); border-left-color:var(--red); }
#resend-notification p { margin:0; font-size:14px; flex:1; }
#resend-notification .dismiss { padding:0; border:0; background:transparent; color:inherit; font-size:22px; line-height:1; cursor:pointer; }
</style>
<div id="resend-notification" hidden><p id="resend-status" role="status" aria-live="polite" aria-atomic="true"></p><button type="button" class="dismiss" aria-label="Dismiss notification" onclick="document.getElementById('resend-notification').hidden=true">&times;</button></div>
<section class="panel" style="max-width:600px;margin:0 auto"><div class="panel-body">
<p class="page-sub">Sign in &middot; Step 2 of 2</p><h1>Verify your sign-in</h1>
<p>Enter the six-digit OTP sent to your registered {{ session('login_channel') === 'phone' ? 'phone number' : 'email address' }}. The code expires in 10 minutes.</p>
<form method="post" action="/login/otp" id="otp-form">@csrf
<label for="otp">Six-digit OTP</label><input id="otp" name="otp" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus>
<button class="btn btn-primary">Verify &amp; sign in</button></form>

<form method="post" action="/login/otp/resend" id="resend-form">@csrf<button class="btn" type="submit">Resend OTP</button>
<a class="btn" href="/login">Back to sign in</a></form>
</div></section>
@include('auth.otp-autosubmit')
<script>
let notificationTimer;
document.getElementById('resend-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button');
    const status = document.getElementById('resend-status');
    if (button.disabled) return;
    button.disabled = true;
    button.textContent = 'Sending...';
    clearTimeout(notificationTimer);
    const notification = document.getElementById('resend-notification');
    notification.hidden = true;
    notification.dataset.kind = 'error';
    status.textContent = '';
    try {
        const response = await fetch(form.action, {
            method: 'POST', body: new FormData(form),
            headers: {Accept: 'application/json', 'ngrok-skip-browser-warning': '1'},
        });
        const data = await response.json().catch(() => null);
        if (response.redirected) { window.location.assign(response.url); return; }
        if (data?.redirect === '/login') { window.location.assign('/login'); return; }
        if (!response.ok) {
            status.textContent = response.status === 429 ? 'Please wait one minute before resending.'
                : response.status === 419 ? 'Your session expired. Go back to sign in.'
                : data?.message || 'The connection was interrupted. Wait a moment and try Resend OTP again.';
            return;
        }
        if (!data?.message) {
            status.textContent = 'No delivery confirmation was received. Please go back to sign in and try again.';
            return;
        }
        notification.dataset.kind = 'success';
        status.textContent = data.message;
        document.getElementById('otp').value = '';
    } catch (error) {
        status.textContent = 'The connection was interrupted. Wait a moment and try Resend OTP again.';
    } finally {
        if (status.textContent) {
            notification.hidden = false;
            if (notification.dataset.kind === 'success') notificationTimer = setTimeout(() => { notification.hidden = true; }, 7000);
        }
        button.disabled = false;
        button.textContent = 'Resend OTP';
    }
});
</script>
@endsection
