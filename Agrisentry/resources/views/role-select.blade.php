<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
@include('partials.language')

<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AgriSentry — Choose Role</title>
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
</svg>

<main class="landing-shell role-screen">
    <div class="farm-backdrop">
        @include('partials.farm-illustration')
        <div class="farm-overlay"></div>
    </div>
    <div class="landing-inner">
        <div class="landing-brand">
            <div class="brand-mark has-logo" style="width:120px;height:120px"><img src="/images/agrisentry-logo-v2-transparent.png" alt="AgriSentry" class="brand-mark-img" onerror="this.style.display='none'"></div>
        </div>
        <h1 class="landing-title">AgriSentry — Goat</h1>
        <div class="landing-sub">Sign in as</div>

        <div class="landing-grid">
            <a href="/login?role=admin" class="landing-card">
                <div class="landing-card-icon" aria-hidden="true">🛡️</div>
                <div class="landing-card-name">Admin</div>
                <div class="landing-card-desc">User management &amp; system settings</div>
            </a>
            <a href="/login?role=staff" class="landing-card">
                <div class="landing-card-icon" aria-hidden="true">🧑‍🌾</div>
                <div class="landing-card-name">Cooperative Staff</div>
                <div class="landing-card-desc">Records, QR profiles &amp; AI guidance</div>
            </a>
            <a href="/login?role=caretaker" class="landing-card">
                <div class="landing-card-icon" aria-hidden="true">📋</div>
                <div class="landing-card-name">Caretaker</div>
                <div class="landing-card-desc">Dashboard &amp; alert notifications</div>
            </a>
        </div>

        <a href="/" class="auth-back">&larr; Back to livestock selection</a>
    </div>

    @include('partials.trust-bar')
</main>

</body>
</html>
