<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
@include('partials.language')

<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AgriSentry — Sign In</title>
<link rel="icon" href="/images/anuvimco-logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
@include('partials.design-system')
</style>
</head>
<body>

<svg style="display:none" aria-hidden="true">
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><polyline points="9 12 11 14 15 10"/></symbol>
    <symbol id="i-signal" viewBox="0 0 24 24"><path d="M2 20h.01M7 20v-4M12 20v-8M17 20v-12M22 20v-16"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V12M10 20V6M16 20v-9M22 20H2"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
</svg>

<main class="auth-shell">
    <div class="auth-visual">
        <div class="farm-backdrop">
            @include('partials.farm-illustration')
            <div class="farm-overlay"></div>
        </div>
        <div class="hero-livestock" aria-hidden="true">
            <img class="hero-animal hero-goat" src="/images/goat-hero-clear.png" alt="">
            <img class="hero-animal hero-pig" src="/images/pig-hero-clear.png" alt="">
            <img class="hero-animal hero-cow" src="/images/cow-hero-clear.png" alt="">
        </div>
        <div class="auth-visual-content">
            <div class="brand-mark has-logo" style="width:64px;height:64px"><img src="/images/agrisentry-logo-v2-transparent.png" alt="AgriSentry" class="brand-mark-img" onerror="this.style.display='none'"></div>
            <h1 class="auth-visual-title">Smart monitoring.<br>Healthier herds.<br><span class="accent">Stronger future.</span></h1>
            <div class="auth-visual-sub">IoT-powered insights to keep your livestock healthy and productive.</div>
        </div>
        <div class="auth-visual-badges">
            <div class="auth-visual-badge">
                <svg class="icon" width="18" height="18"><use href="#i-shield"/></svg>
                <div>
                    <div class="auth-visual-badge-title">Secure</div>
                    <div class="auth-visual-badge-desc">Your data is always protected</div>
                </div>
            </div>
            <div class="auth-visual-badge">
                <svg class="icon" width="18" height="18"><use href="#i-signal"/></svg>
                <div>
                    <div class="auth-visual-badge-title">Connected</div>
                    <div class="auth-visual-badge-desc">Real-time IoT monitoring</div>
                </div>
            </div>
            <div class="auth-visual-badge">
                <svg class="icon" width="18" height="18"><use href="#i-chart"/></svg>
                <div>
                    <div class="auth-visual-badge-title">Insightful</div>
                    <div class="auth-visual-badge-desc">Actionable insights for better decisions</div>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">
            <div class="auth-head">
                <div class="brand-mark has-logo" style="width:88px;height:88px"><img src="/images/agrisentry-logo-v2-transparent.png" alt="AgriSentry" class="brand-mark-img" onerror="this.style.display='none'"></div>
                <h1 class="auth-title">Sign in to AgriSentry</h1>
                <div class="auth-sub">Your account role is identified automatically</div>
            </div>

            @if(session('status'))<p role="status" style="padding:12px;margin-bottom:14px;color:var(--green-dk);background:var(--green-bg);border-radius:9px">{{ session('status') }}</p>@endif
            @if ($errors->any())
                <div class="auth-error" style="margin-bottom:14px" role="alert">{{ $errors->first() }}</div>
            @endif

            <p id="login-feedback" role="status" aria-live="polite" hidden style="margin-bottom:14px"></p>
            <form class="auth-form" method="POST" action="/login" id="login-form">
                @csrf
                <div class="form-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="e.g. admin">
                </div>
                <div class="form-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="justify-content:center;margin-top:4px">Continue</button>
            </form>

            <a href="/password/recovery" class="auth-back">Forgot password? Recover with OTP</a>
        </div>
    </div>
</main>

<script src="/js/login.js" defer></script>
</body>
</html>
