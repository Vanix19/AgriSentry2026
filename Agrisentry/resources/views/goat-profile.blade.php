<!DOCTYPE html>
<html lang="en">
<head>
@include('partials.language')

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $goat->name }} - Animal Profile</title>
    <link rel="icon" href="/images/anuvimco-logo.png">
    <script>document.documentElement.dataset.theme = localStorage.getItem('agrisentry-theme') || 'light';</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="/js/qrcode.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #00b386;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 24px;
            font-size: 18px;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .header {
            background: #a8e6d3;
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            margin-bottom: 24px;
        }

        .header-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            color: #1a1a1a;
        }

        .goat-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 60px;
        }

        .goat-name {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .goat-info {
            font-size: 16px;
            color: #555;
            margin-bottom: 12px;
        }

        .temp-alert {
            font-size: 20px;
            font-weight: 700;
            color: #e74c3c;
            margin-top: 16px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #7f8c8d;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .qr-section {
            background: white;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .qr-code {
            max-width: 250px;
            margin: 24px auto;
            padding: 16px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .qr-desc {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 24px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .btn {
            padding: 12px 28px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #00b386;
            color: white;
            border-color: #00b386;
        }

        .btn-primary:hover {
            background: #009970;
            border-color: #009970;
        }

        .btn:hover {
            border-color: #999;
        }

        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            background: white;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tab {
            padding: 8px 16px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #00b386;
            border-bottom-color: #00b386;
        }

        .tab:hover {
            color: #333;
        }

        .time-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .time-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            color: #7f8c8d;
            transition: all 0.3s;
        }

        .time-btn.active {
            background: #00b386;
            color: white;
            border-color: #00b386;
        }

        .time-btn:hover {
            border-color: #00b386;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .chart-placeholder {
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .medical-records {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .medical-header {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .medical-records-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .add-record-btn {
            background: #00b386;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .record-item {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .record-type {
            font-size: 14px;
            font-weight: 700;
            color: #00b386;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .record-name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .record-date {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 8px;
        }

        .record-desc {
            font-size: 14px;
            color: #555;
        }

        .summary-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-chip {
            flex: 1 1 160px;
            background: #f9f9f9;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .summary-chip-count {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a1a;
        }

        .summary-chip-label {
            font-size: 12px;
            font-weight: 700;
            color: #00b386;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .summary-chip-last {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 6px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(15, 23, 42, .5);
        }

        .modal-backdrop[hidden] {
            display: none;
        }

        .modal-box {
            width: min(100%, 520px);
            max-height: 88vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: var(--shell-card);
            border: 1px solid var(--shell-line);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,.25);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            background: var(--shell-card);
            color: var(--shell-text);
            border-bottom: 1px solid var(--shell-line);
        }

        .modal-head h3 { font-size: 18px; }

        .modal-close {
            width: 30px;
            height: 30px;
            border: 1px solid var(--shell-line);
            border-radius: 8px;
            background: var(--shell-raised);
            color: var(--shell-muted);
            font-size: 18px;
            cursor: pointer;
        }

        .modal-form { padding: 20px 22px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 13px; overflow-y: auto; }
        .modal-form label { display: flex; flex-direction: column; gap: 6px; font-size: 13px; font-weight: 600; color: var(--shell-text); }
        .modal-form .full { grid-column: 1 / -1; }
        .modal-form label span { color: #e74c3c; }
        .modal-form input, .modal-form select {
            padding: 10px 12px;
            border: 1px solid var(--shell-line);
            border-radius: 9px;
            background: var(--shell-raised);
            color: var(--shell-text);
            font-size: 14px;
            font-family: inherit;
        }
        .modal-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 10px; margin: 6px -22px 0; padding: 16px 22px; border-top: 1px solid var(--shell-line); }
        .modal-error {
            display: none;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 13px;
        }

        :root { --shell-bg:#f3f6f8; --shell-card:#fff; --shell-raised:#f7f9fa; --shell-line:#dce3e8; --shell-text:#111827; --shell-muted:#64748b; --shell-side:#fff; --green:#16a34a; }
        :root[data-theme="dark"] { --shell-bg:#090d13; --shell-card:#11161e; --shell-raised:#151b24; --shell-line:#242b35; --shell-text:#f4f6fa; --shell-muted:#9aa5b8; --shell-side:#090d13; --green:#22c55e; }
        body { background:var(--shell-bg); color:var(--shell-text); }
        .profile-sidebar { position:fixed; inset:0 auto 0 0; width:294px; z-index:20; display:flex; flex-direction:column; background:var(--shell-side); border-right:1px solid var(--shell-line); }
        .profile-brand { min-height:106px; padding:22px 28px; display:flex; align-items:center; gap:13px; border-bottom:1px solid var(--shell-line); }
        .profile-brand img { width:43px; height:52px; object-fit:contain; padding:3px; border-radius:8px; background:rgba(255,255,255,.94); filter:drop-shadow(0 0 8px rgba(34,197,94,.22)); }
        .profile-brand b { display:block; color:var(--shell-text); font-size:20px; }
        .profile-brand span { display:block; margin-top:4px; color:var(--shell-muted); font-size:12px; }
        .profile-nav { padding:16px; }
        .profile-nav h4 { margin:16px 10px 8px; color:var(--shell-muted); font-size:11px; text-transform:uppercase; }
        .profile-nav a { min-height:45px; display:flex; align-items:center; gap:12px; padding:10px 18px; border-radius:9px; color:var(--shell-muted); text-decoration:none; font-size:14px; }
        .profile-nav a:hover, .profile-nav a.active { color:var(--green); background:rgba(34,197,94,.11); }
        .profile-legend { margin-top:auto; padding:24px; color:var(--shell-muted); border-top:1px solid var(--shell-line); font-size:11px; }
        .profile-topbar { position:fixed; left:294px; right:0; top:0; height:77px; z-index:15; display:flex; align-items:center; justify-content:flex-end; gap:16px; padding:14px 40px; background:var(--shell-side); border-bottom:1px solid var(--shell-line); }
        .profile-topbar span, .profile-topbar button { min-height:40px; padding:10px 16px; border:1px solid var(--shell-line); border-radius:10px; background:var(--shell-card); color:var(--shell-muted); }
        .profile-topbar .api { color:var(--green); }
        .profile-topbar .avatar { width:40px; padding:0; display:grid; place-items:center; border-radius:50%; color:#fff; background:var(--green); }
        .container { max-width:none; margin-left:294px; padding:112px 44px 60px; }
        .container > div:first-child { margin-bottom:28px !important; }
        .back-link { color:var(--green); font-size:15px; }
        .profile-page-title { margin:0 0 30px; }
        .profile-page-title h1 { color:var(--shell-text); font-size:3px; }
        .profile-page-title p { margin-top:8px; color:var(--shell-muted); font-size:15px; }
        .header { min-height:307px; display:grid; grid-template-columns:180px 1fr 320px; grid-template-rows:auto auto auto; align-items:center; gap:4px 28px; padding:38px; text-align:left; background:linear-gradient(100deg,rgba(12,92,53,.22),rgba(8,45,34,.12)); border:1px solid rgba(34,197,94,.22); border-radius:13px; }
        .header-title { display:none; }
        .goat-avatar { grid-row:1/4; width:170px; height:170px; margin:0; display:grid; place-items:center; color:var(--green); background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3); font-size:0; }
        .goat-avatar img { width:138px; height:138px; object-fit:contain; }
        .goat-name { align-self:end; margin:0; color:var(--shell-text); font-size:3px; }
        .goat-info { margin:0; color:var(--shell-muted); font-size:17px; }
        .temp-alert { align-self:start; justify-self:start; margin:8px 0 0; padding:10px 18px; border:1px solid var(--shell-line); border-radius:28px; color:#60a5fa; background:var(--shell-raised); font-size:17px; }
        .header::after { content:'✓'; grid-column:3; grid-row:1/4; min-height:110px; display:flex; align-items:center; justify-content:flex-end; padding-right:34px; color:rgba(34,197,94,.24); font-size:76px; text-align:right; background:url('/images/goat-avatar-realistic.png') 24px center / 105px 105px no-repeat; opacity:.72; }
        .grid { grid-template-columns:repeat(4,1fr); gap:20px; }
        .card { min-height:125px; padding:25px 28px; background:var(--shell-card); border:1px solid var(--shell-line); box-shadow:none; }
        .card-label { color:var(--shell-text); font-size:14px; text-transform:none; }
        .card-value { color:var(--shell-muted); font-size:17px; }
        .qr-section, .tabs, .chart-container, .medical-records { background:var(--shell-card); border:1px solid var(--shell-line); box-shadow:none; color:var(--shell-text); }
        .tabs .tab, .chart-title, .medical-records-title, .record-name, .summary-chip-count { color:var(--shell-text); }
        body, .profile-sidebar, .profile-topbar, .header, .card, .qr-section,
        .tabs, .chart-container, .medical-records, .profile-topbar span, .profile-topbar button {
            transition: background-color .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease;
        }
        @media (max-width:1000px) { .profile-sidebar { transform:translateX(-100%); } .profile-topbar { left:0; } .container { margin-left:0; padding-inline:20px; } .header { grid-template-columns:140px 1fr; } .header::after { display:none; } .grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:560px) { .profile-topbar .api, .profile-topbar form { display:none; } .header { display:flex; flex-direction:column; text-align:center; } .grid { grid-template-columns:1fr; } }

        @media print {
            .back-link, .tabs, .time-filters, .button-group, .add-record-btn { display: none !important; }
        }
    </style>
@include('partials.battery-status')

<style>
@include('partials.design-system')
:root, :root[data-theme="dark"] { --shell-bg:var(--bg-app); --shell-card:var(--bg-card); --shell-raised:var(--bg-raised); --shell-line:var(--border); --shell-text:var(--text-0); --shell-muted:var(--text-2); --shell-side:var(--bg-sidebar); }
body, button, input, select, textarea { font-family:var(--sans); }
body .profile-shared-topbar { position:fixed; top:0; left:var(--console-sidebar, 260px); right:0; z-index:15; }
body .container { width:calc(100% - var(--console-sidebar, 260px)); margin-left:var(--console-sidebar, 260px); padding:116px 44px 48px; }
.profile-page-title h1 { font-size:28px; font-weight:800; letter-spacing:-.5px; }
.profile-page-title p { font-size:14px; }
.container .header { min-height:180px; grid-template-columns:116px 1fr; padding:24px; gap:6px 24px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); }
.container .header::after { display:none; }
.container .goat-avatar { width:112px; height:112px; background:var(--green-bg); border-color:var(--green-bg-strong); }
.container .goat-avatar img { width:96px; height:96px; }
.container .goat-name { font-size:26px; }
.container .goat-info { font-size:14px; }
.container .temp-alert { font-size:13px; padding:7px 12px; }
.container .grid { gap:16px; }
.container .card { min-height:100px; padding:20px; border-radius:var(--radius); background:var(--bg-card); border:1px solid var(--border); box-shadow:var(--shadow-sm); }
.container .card-label { font-size:12px; color:var(--text-3); margin-bottom:10px; }
.container .card-value { font-size:18px; font-weight:700; color:var(--text-0); overflow-wrap:anywhere; }
.container .qr-section, .container .chart-container, .container .medical-records { border-radius:var(--radius); background:var(--bg-card); }
.container .back-link { color:var(--green-dk); font-size:14px; }
@media(max-width:1000px) { body .container { width:100%; margin-left:0; padding:108px 20px 40px; } body .profile-shared-topbar { left:0; } body .sidebar { transform:translateX(-100%); } body .sidebar.open { transform:translateX(0); } .profile-shared-topbar .burger { display:flex; } .container .grid { grid-template-columns:repeat(2,minmax(0,1fr))!important; } }
@media(max-width:560px) { body .container { padding:100px 14px 32px; } .container .header { display:flex; flex-direction:column; text-align:center; } .container .temp-alert { align-self:center; } .container .grid { grid-template-columns:1fr!important; } .profile-shared-topbar .status-pill { display:none; } .button-group,.time-filters { flex-wrap:wrap; } }
@media print { .sidebar,.profile-shared-topbar { display:none!important; } body .container { margin:0; width:100%; padding:0; } }
</style>
</head>
<body>
<svg style="display:none" aria-hidden="true">
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><polyline points="9 12 11 14 15 10"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></symbol>
    <symbol id="i-list" viewBox="0 0 24 24"><circle cx="4.5" cy="6" r="1.3" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="4.5" cy="18" r="1.3" fill="currentColor" stroke="none"/><line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/></symbol>
    <symbol id="i-activity" viewBox="0 0 24 24"><polyline points="2 13 7 13 9.5 6 14.5 20 17 13 22 13"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="12"/><line x1="10" y1="21" x2="10" y2="5"/><line x1="16" y1="21" x2="16" y2="9"/><line x1="22" y1="21" x2="22" y2="2"/></symbol>
    <symbol id="i-goat" viewBox="0 0 24 24"><path d="M5 9h10.5a3 3 0 0 1 3 3v4H8a3 3 0 0 1-3-3V9z"/><path d="M5 10 2.5 7.5 5 6l2 3M17 10l2-3 2 1.5-2.5 3M8 16v4M17 16v4"/><circle cx="5" cy="10" r=".4" fill="currentColor"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0v5l2 3H4l2-3V9z"/><path d="M10 20h4"/></symbol>
    <symbol id="i-clipboard" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><rect x="9" y="2.5" width="6" height="3.5" rx="1"/><line x1="8.5" y1="11" x2="15.5" y2="11"/><line x1="8.5" y1="15" x2="13" y2="15"/></symbol>
    <symbol id="i-camera" viewBox="0 0 24 24"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.6h7l1 1.6H19a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z"/><circle cx="12" cy="13" r="3.6"/></symbol>
    <symbol id="i-message" viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5z"/></symbol>
    <symbol id="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4.2"/><line x1="12" y1="2" x2="12" y2="4.4"/><line x1="12" y1="19.6" x2="12" y2="22"/><line x1="4.2" y1="4.2" x2="5.9" y2="5.9"/><line x1="18.1" y1="18.1" x2="19.8" y2="19.8"/><line x1="2" y1="12" x2="4.4" y2="12"/><line x1="19.6" y1="12" x2="22" y2="12"/><line x1="4.2" y1="19.8" x2="5.9" y2="18.1"/><line x1="18.1" y1="5.9" x2="19.8" y2="4.2"/></symbol>
    <symbol id="i-moon" viewBox="0 0 24 24"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5z"/></symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5.3L20 8"/><polyline points="20 3 20 8 15 8"/><path d="M20 12a8 8 0 0 1-14 5.3L4 16"/><polyline points="4 21 4 16 9 16"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></symbol>
    <symbol id="i-download" viewBox="0 0 24 24"><line x1="12" y1="3" x2="12" y2="15"/><polyline points="7 10 12 15 17 10"/><line x1="4" y1="20" x2="20" y2="20"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.2" y2="16.2"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="14 6 20 12 14 18"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3.5L22 20H2L12 3.5z"/><line x1="12" y1="10" x2="12" y2="14.5"/><circle cx="12" cy="17.3" r="0.4" fill="currentColor"/></symbol>
    <symbol id="i-battery" viewBox="0 0 24 24"><rect x="2" y="8" width="17" height="8" rx="1.5"/><line x1="22" y1="10.5" x2="22" y2="13.5"/></symbol>
    <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><polyline points="4 12 9.5 18 20 6"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><polyline points="4 7 20 7"/><path d="M6 7l1 13h10l1-13"/><path d="M9.5 7V4.5h5V7"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M10 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/><polyline points="16 16 21 12 16 8"/><line x1="21" y1="12" x2="9" y2="12"/></symbol>
</svg>
<div class="app">
    <div class="sidebar" id="sidebar">
        <div class="brand" onclick="window.location.href='/agrisentry?screen=dashboard'">
            <div class="brand-mark has-logo"><img src="/images/agrisentry-logo-v2-transparent.png" alt="AgriSentry" class="brand-mark-img" onerror="this.style.display='none'"></div>
            <div>
                <h1 class="brand-name">AgriSentry</h1>
                <div class="brand-sub">ANMPC · Goat Health</div>
            </div>
        </div>

        <nav class="nav-scroll" aria-label="Main navigation">
            <div class="nav-section">Overview</div>
            <div class="nav-item" id="nav-dashboard" onclick="window.location.href='/agrisentry?screen=dashboard'">
                <svg class="icon"><use href="#i-grid"/></svg> Dashboard
            </div>

            <div class="nav-section">Herd</div>
            <div class="nav-item active" id="nav-animals" onclick="window.location.href='/agrisentry?screen=animals'">
                <svg class="icon"><use href="#i-goat"/></svg> All Goats
            </div>
            <div class="nav-item" id="nav-logs" onclick="window.location.href='/agrisentry?screen=logs'">
                <svg class="icon"><use href="#i-activity"/></svg> Health Logs
                <span class="nav-badge zero" id="logs-badge">0</span>
            </div>
            <div class="nav-item" id="nav-medical" onclick="window.location.href='/agrisentry?screen=medical'">
                <svg class="icon"><use href="#i-clipboard"/></svg> Medical Records
            </div>
            <div class="nav-item" id="nav-collars" onclick="window.location.href='/agrisentry?screen=collars'">
                <svg class="icon"><use href="#i-battery"/></svg> Collars
            </div>
            <div class="nav-item" id="nav-reports" onclick="window.location.href='/agrisentry?screen=reports'">
                <svg class="icon"><use href="#i-chart"/></svg> Report Analytics
            </div>

            <div class="nav-section">System</div>
            <div class="nav-item" id="nav-ai" onclick="window.location.href='/agrisentry?screen=ai'">
                <svg class="icon"><use href="#i-message"/></svg> AI Vet Advice
                <span class="nav-badge zero" id="alerts-badge">0</span>
            </div>
            <a class="nav-item" href="/settings"><svg class="icon"><use href="#i-shield"/></svg> Settings</a>
            @if(auth()->user()?->role === 'Admin')
            <div class="nav-item" onclick="window.location.href='/admin/users'">
                <svg class="icon"><use href="#i-list"/></svg> Manage Users
            </div>
            @endif
        </nav>

        <div class="sidebar-foot">
            <div class="led-legend">
                <span><span class="led led-b"></span> &lt;33.0°</span>
                <span><span class="led led-g"></span> 33–38.5°</span>
                <span><span class="led led-r"></span> &gt;38.5°</span>
            </div>
        </div>
    </div>

        <header class="topbar profile-shared-topbar">
            <div class="burger" onclick="document.getElementById('sidebar').classList.toggle('open')"><svg class="icon"><use href="#i-menu"/></svg></div>
            <div class="topbar-title" id="topbar-title">Animal Profile</div>
            <div class="topbar-spacer"></div>
            <div class="status-pill" id="server-status">
                <div class="status-dot"></div> API Connected
            </div>
            <div class="icon-btn" onclick="toggleProfileTheme()" title="Toggle theme">
                <svg class="icon" id="theme-icon"><use href="#i-moon"/></svg>
            </div>
            <form action="/logout" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm" title="Log out" style="cursor:pointer;color:var(--red);border-color:var(--red-bg-strong)">
                    <svg class="icon" style="width:14px;height:14px"><use href="#i-logout"/></svg> Log out
                </button>
            </form>
            <div class="avatar" title="{{ auth()->user()->name ?? '' }} ({{ auth()->user()->role ?? '' }})">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 2)) }}</div>
        </header>

    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
            <a href="/agrisentry" class="back-link" style="margin-bottom:0">← Back to Dashboard</a>
            @if($canManageGoat)
                <button type="button" class="btn btn-primary" style="margin-left:auto" onclick="document.getElementById('edit-goat').showModal()">Edit profile</button>
                <button type="button" class="btn" style="border-color:#e74c3c;color:#e74c3c" onclick="deleteThisGoat()">🗑 Delete Animal</button>
            @endif
        </div>

        <div class="profile-page-title"><h1>Animal Profile</h1><p>Detailed information and real-time status</p></div>

        <div class="header">
            <div class="header-title">Wearable Active • Movement Detector • Collar Tag</div>
            <div class="goat-avatar"><img src="/images/goat-avatar-realistic.png" alt="Goat"></div>
            <div class="goat-name">{{ $goat->name }}</div>
            <div class="goat-info">{{ $goat->code }}{{ $goat->collar ? ' • ' . $goat->collar->collar_code : '' }}</div>
            @if($goat->temperature)
                <div class="temp-alert">{{ $goat->temperature > 38.5 ? '🔴 High' : ($goat->temperature < 33.0 ? '🔵 Low' : '🟢 Normal') }} – {{ $goat->temperature }}°C</div>
            @endif
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-label">Breed</div>
                <div class="card-value">{{ $goat->breed ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Age</div>
                <div class="card-value">{{ $goat->age ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Sex</div>
                <div class="card-value">{{ $goat->sex ?? 'N/A' }}</div>
            </div>
            @if($goat->pregnancy_status === 'pregnant')
            <div class="card" data-pregnancy-status>
                <div class="card-label">Pregnancy status</div>
                <div class="card-value">Pregnant</div>
            </div>
            @endif
            <div class="card">
                <div class="card-label">Weight</div>
                <div class="card-value">{{ $goat->weight ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Owner</div>
                <div class="card-value" translate="no">{{ $goat->owner ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Ear Tag</div>
                <div class="card-value">{{ $goat->ear_tag ?? 'N/A' }}</div>
            </div><div class="card"><div class="card-label">Goat Color</div><div class="card-value">{{ $goat->color ?? 'N/A' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Movement</div>
                <div class="card-value">{{ $goat->movement ?? 'Normal' }}</div>
            </div>
            <div class="card">
                <div class="card-label">Battery</div>
                <div class="card-value" data-live-battery></div>
            </div>
        </div>

        <div class="qr-section">
            <div style="max-width: 300px; margin: 0 auto;">
                <div class="qr-code" id="qr-code-target"></div>
                <div class="qr-title">QR-LINKED DIGITAL RECORD</div>
                <div class="qr-desc">Scan this QR code to open {{ $goat->name }}'s profile.</div>
                <div class="button-group">
                    <button class="btn" onclick="window.print()">Print QR</button>
                    <button class="btn btn-primary" onclick="downloadQR()">Export QR</button>
                </div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('vitals')">Vitals Chart</button>
            <button class="tab" onclick="switchTab('medical')">Medical Records</button>
        </div>

        <div id="vitals" class="tab-content active">
            <div class="time-filters">
                <label>Specific date <input type="date" id="vitals-date" onchange="renderCharts()"></label><button class="time-btn" onclick="document.getElementById('vitals-date').value='';renderCharts()">Clear date</button>
                <button class="time-btn" data-period="daily" onclick="filterByTime('daily')">Daily</button>
                <button class="time-btn" data-period="weekly" onclick="filterByTime('weekly')">Weekly</button>
                <button class="time-btn active" data-period="monthly" onclick="filterByTime('monthly')">Monthly</button>
            </div>

            <div class="grid" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:24px;">
                <div class="card">
                    <div class="card-label">Temperature</div>
                    <div class="card-value">{{ $goat->temperature ?? 'N/A' }}°C</div>
                </div>
                <div class="card">
                    <div class="card-label">Movement</div>
                    <div class="card-value">{{ $goat->movement ?? 'Normal' }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Battery</div>
                    <div class="card-value" data-live-battery></div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">Temperature History – <span class="period-label">Monthly</span></div>
                <canvas id="temp-chart-vitals" height="140"></canvas>
                <div class="chart-placeholder" id="temp-chart-vitals-empty" style="display:none;margin-top:16px;">
                    📊 No temperature readings in this period.
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">Motion Readings &amp; Findings – <span class="period-label">Monthly</span></div>
                <canvas id="motion-chart-vitals" height="140"></canvas>
                <div style="overflow-x:auto;margin-top:16px">
                    <table style="width:100%;text-align:left"><thead><tr><th>Recorded at</th><th>Motion reading / finding</th><th>Health record</th></tr></thead><tbody id="motion-findings">
                    @forelse($healthLogs->filter(fn ($log) => $log->motion_anomaly || filled($log->movement) || str_contains(strtolower($log->event_type ?? ''), 'motion')) as $log)
                        <tr><td>{{ $log->created_at }}</td><td>{{ $log->motion_anomaly ?: ($log->movement ?: 'Motion event (type not specified)') }}</td><td>{{ $log->event_type }} — {{ $log->description }}</td></tr>
                    @empty
                        <tr><td colspan="3">No motion readings recorded.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
        </div>

        <div id="medical" class="tab-content">
            <div class="medical-records">
                <div class="medical-header">
                    <h3 class="medical-records-title">All Medical Records</h3>
                    <div class="btn-row">
                        <a class="btn" href="/api/medical-records/export?goat_id={{ $goat->id }}">Download all records (PDF)</a>
                        @if($canEdit)
                            <button class="btn btn-primary" onclick="openAddRecordModal()">+ Add Record</button>
                        @endif
                    </div>
                </div>

                @php
                    $recordSummary = $medicalRecords
                        ->groupBy(fn ($record) => $record->record_type ?: 'Other')
                        ->map(fn ($group) => [
                            'count' => $group->count(),
                            'last' => $group->sortByDesc(fn ($r) => $r->date_given ?? $r->created_at)->first(),
                        ])
                        ->sortBy(fn ($group, $type) => array_search($type, ['Vaccination', 'Treatment', 'Deworming', 'Checkup', 'Other']));
                @endphp

                @if($recordSummary->isNotEmpty())
                    <div class="summary-chip-row">
                        @foreach($recordSummary as $type => $group)
                            <div class="summary-chip">
                                <div class="summary-chip-count">{{ $group['count'] }}</div>
                                <div class="summary-chip-label">{{ $type }}{{ $group['count'] === 1 ? '' : 's' }}</div>
                                <div class="summary-chip-last">Last: {{ $group['last']->title ?? 'Untitled' }} ({{ $group['last']->date_given ?? $group['last']->created_at->format('Y-m-d') }})</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(count($medicalRecords) > 0)
                    @foreach($medicalRecords as $record)
                        <div class="record-item">
                            <div class="record-type">{{ $record->record_type ?? 'Medical Record' }}</div>
                            <div class="record-name" translate="{{ $record->title ? 'no' : 'yes' }}">{{ $record->title ?? 'Record' }}</div>
                            <div class="record-date">Date: {{ $record->date_given ?? $record->created_at->format('Y-m-d') }}</div>
                            <div class="record-desc" translate="{{ $record->description ? 'no' : 'yes' }}">{{ $record->description ?? 'No description' }}</div>
                            @if($record->reference_photo_url)
                                @if(str_ends_with(strtolower($record->reference_photo_path), '.pdf'))
                                    <a class="btn" href="{{ $record->reference_photo_url }}" target="_blank" rel="noopener" style="margin-top:10px">View attached PDF</a>
                                @else
                                    <a href="{{ $record->reference_photo_url }}" target="_blank" rel="noopener"><img src="{{ $record->reference_photo_url }}" alt="Medical reference" style="width:120px;height:90px;object-fit:cover;border-radius:9px;margin-top:10px;border:1px solid var(--shell-line)"></a>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 40px; color: #999;">
                        No medical records yet
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="add-record-modal" hidden onclick="if(event.target === this) closeAddRecordModal()">
        <div class="modal-box">
            <div class="modal-head">
                <div><h3>Add Medical Record</h3><div style="margin-top:2px;color:var(--shell-muted);font-size:12px;font-weight:400">Vaccination, treatment, or checkup entry</div></div>
                <button type="button" class="modal-close" onclick="closeAddRecordModal()" aria-label="Close">×</button>
            </div>
            <form class="modal-form" id="add-record-form" onsubmit="submitAddRecord(event)">
                <div class="modal-error full" id="add-record-error" role="alert"></div>
                <label>Record Type <span>*</span>
                    <select name="record_type" required>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Treatment">Treatment</option>
                        <option value="Checkup">Checkup</option>
                        <option value="Deworming">Deworming</option>
                        <option value="Other">Other</option>
                    </select>
                </label>
                <label>Date Given
                    <input type="date" name="date_given">
                </label>
                <label class="full">Title <span>*</span>
                    <input name="title" required maxlength="255" placeholder="e.g. Deworming">
                </label>
                <label>Next Due Date
                    <input type="date" name="next_due_date">
                </label>
                <label>Administered By
                    <input name="administered_by" maxlength="255" placeholder="Veterinarian or caretaker">
                </label>
                <label class="full">Previous Diagnosis / Medication File
                    <input type="file" name="reference_photo" accept="image/jpeg,image/png,image/webp,application/pdf">
                    <small>Optional image or PDF, up to 20 MB.</small>
                </label>
                <label class="full">Description
                    <input name="description" placeholder="Notes, dosage, or observations">
                </label>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeAddRecordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-record-submit">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    @if($canManageGoat)
    <style>
        #edit-goat { position:fixed; inset:0; margin:auto; padding:0; width:calc(100% - 40px); max-width:520px; max-height:88vh; border:1px solid var(--shell-line); border-radius:16px; background:var(--shell-card); color:var(--shell-text); box-shadow:0 24px 60px rgba(0,0,0,.25); overflow:hidden; }
        #edit-goat::backdrop { background:rgba(8,12,20,.55); backdrop-filter:blur(3px); }
        #edit-goat-form { display:flex; flex-direction:column; max-height:88vh; }
        #edit-goat .modal-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--shell-line); flex-shrink:0; }
        #edit-goat .modal-title { font-size:16px; font-weight:800; }
        #edit-goat .modal-sub { font-size:11.5px; color:var(--shell-muted); margin-top:2px; font-family:monospace; }
        #edit-goat .modal-close { width:30px; height:30px; border-radius:8px; border:1px solid var(--shell-line); background:var(--shell-raised); color:var(--shell-muted); cursor:pointer; font-size:20px; }
        #edit-goat .modal-body { padding:20px 22px; overflow-y:auto; min-height:0; }
        #edit-goat .form-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:13px; }
        #edit-goat .form-field { display:flex; flex-direction:column; gap:6px; }
        #edit-goat .form-field.full { grid-column:1/-1; }
        #edit-goat label { font-size:11.5px; font-weight:600; color:var(--shell-muted); font-family:monospace; }
        #edit-goat .req, #edit-error { color:#dc2626; }
        #edit-goat input, #edit-goat select { width:100%; min-width:0; padding:9px 12px; border-radius:9px; border:1px solid var(--shell-line); background:var(--shell-raised); color:var(--shell-text); font-size:13px; font-family:inherit; }
        #edit-goat input:focus, #edit-goat select:focus { outline:2px solid var(--green); outline-offset:1px; background:var(--shell-card); }
        #edit-goat .modal-foot { display:flex; justify-content:flex-end; gap:9px; padding:16px 22px; border-top:1px solid var(--shell-line); flex-shrink:0; }
        #edit-goat .btn { padding:9px 16px; border:1px solid var(--shell-line); border-radius:9px; background:var(--shell-raised); color:var(--shell-text); font-size:13px; font-weight:600; cursor:pointer; }
        #edit-goat .btn-primary { background:#16a34a; border-color:#16a34a; color:#fff; }
        #edit-goat .btn:disabled { opacity:.6; cursor:wait; }
        #edit-error:not(:empty) { margin-top:14px; font-size:13px; }
        @media(max-width:560px) { #edit-goat .form-grid { grid-template-columns:1fr; } }
    </style>
    <dialog id="edit-goat" aria-labelledby="edit-goat-title">
      <form id="edit-goat-form">
        <div class="modal-head">
          <div><h2 class="modal-title" id="edit-goat-title">Edit Goat</h2><div class="modal-sub">Update animal information in the herd</div></div>
          <button type="button" class="modal-close" aria-label="Close edit goat" onclick="document.getElementById('edit-goat').close()">&times;</button>
        </div>
        <div class="modal-body"><div class="form-grid">
          @foreach(['name' => 'e.g. Bituin','breed' => 'e.g. Boer','sex' => '', 'age' => 'e.g. 2 years','weight' => 'e.g. 34 kg','owner' => 'Owner name','ear_tag' => 'Ear Tag / Goat ID', 'color' => 'Goat color'] as $field => $placeholder)
          <div class="form-field {{ $field === 'name' ? 'full' : '' }}">
            <label for="edit-goat-{{ $field }}">{{ $field === 'ear_tag' ? 'Ear Tag / Goat ID' : ucfirst($field) }} @if(in_array($field, ['name','ear_tag']))<span class="req">*</span>@endif</label>
            @if($field === 'breed')
              <select id="edit-goat-breed" name="breed"><option value="">Select breed</option>@foreach(['Boer','Anglo-Nubian','Saanen','Alpine','Toggenburg','Native','Crossbreed','Other'] as $breed)<option @selected($goat->breed === $breed)>{{ $breed }}</option>@endforeach</select>
            @elseif($field === 'sex')
              <select id="edit-goat-sex" name="sex">
                <option value="">— Select —</option>
                @if($goat->sex && !in_array($goat->sex, ['Male','Female']))<option value="{{ $goat->sex }}" selected>{{ $goat->sex }}</option>@endif
                <option value="Male" @selected($goat->sex === 'Male')>Male</option><option value="Female" @selected($goat->sex === 'Female')>Female</option>
              </select>
            @else
              <input type="text" id="edit-goat-{{ $field }}" name="{{ $field }}" @if($field === 'code') pattern="GT-[0-9]{3}" title="Use GT- followed by three digits, for example GT-014" @endif value="{{ $field === 'ear_tag' ? ($goat->ear_tag ?: $goat->code) : $goat->$field }}" placeholder="{{ $placeholder }}" maxlength="{{ in_array($field, ['code','age','weight']) ? 100 : 255 }}" {{ in_array($field, ['name','ear_tag']) ? 'required' : '' }}>
            @endif
          </div>
          @endforeach
          <div class="form-field">
            <label for="edit-goat-pregnancy">Pregnancy status</label>
            <select id="edit-goat-pregnancy" name="pregnancy_status">
              @foreach(['unknown' => 'Unknown', 'pregnant' => 'Pregnant', 'not_pregnant' => 'Not pregnant'] as $value => $label)
                <option value="{{ $value }}" @selected(($goat->pregnancy_status ?? 'unknown') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div><p id="edit-error" role="alert"></p></div>
        <div class="modal-foot"><button class="btn" type="button" onclick="document.getElementById('edit-goat').close()">Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
      </form>
    </dialog>
    @endif
    <script>
        document.getElementById('edit-goat-form')?.addEventListener('submit', async event => {
            event.preventDefault(); const button = event.target.querySelector('[type="submit"]'); button.disabled = true;
            try { const response = await fetch('/api/goats/{{ $goat->id }}', {method:'PUT', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(Object.fromEntries(new FormData(event.target)))}); const data = await response.json(); if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not save profile.'); location.reload(); }
            catch(error) { document.getElementById('edit-error').textContent = error.message; button.disabled = false; }
        });
        function renderProfileBattery(goat) {
            document.querySelectorAll('[data-live-battery]').forEach(element => { element.innerHTML = liveBatteryStatus(goat.collar?.battery_level ?? goat.battery, goat.collar?.last_seen); });
        }
        renderProfileBattery(@json($goat));
        window.addEventListener('agrisentry:telemetry', event => {
            const reading = Object.values(event.detail || {}).find(item => String(item.goat_id) === '{{ $goat->id }}');
            if (!reading) return;
            renderProfileBattery({ battery: reading.battery_level, collar: { battery_level: reading.battery_level, last_seen: reading.received_at } });
            document.querySelectorAll('.card').forEach(card => {
                const label = agrisentryOriginalText(card.querySelector('.card-label'));
                const value = card.querySelector('.card-value');
                if (!value) return;
                if (label === 'Temperature') value.textContent = reading.temperature == null ? 'N/A' : `${reading.temperature}°C`;
                if (label === 'Movement') value.textContent = reading.movement || 'N/A';
            });
            let temperature = document.querySelector('.temp-alert');
            if (!temperature) { temperature = document.createElement('div'); temperature.className = 'temp-alert'; document.querySelector('.header').appendChild(temperature); }
            temperature.textContent = reading.temperature == null ? 'No temperature reading' : `${reading.status || ''} — ${reading.temperature}°C`;
            temperature.style.color = reading.temperature == null ? 'var(--shell-muted)' : reading.temperature < 33 ? '#2563eb' : reading.temperature > 38.5 ? '#dc2626' : '#15803d';
        });
        let batteryRefreshPending = false;
        setInterval(async () => {
            if (document.hidden || batteryRefreshPending) return;
            batteryRefreshPending = true;
            try { const response = await fetch('/api/goats/{{ $goat->id }}', {headers:{Accept:'application/json'}}); if (response.ok) renderProfileBattery((await response.json()).goat); } catch (_) { /* Keep the timestamp of the last reading visible. */ }
            finally { batteryRefreshPending = false; }
        }, 15000);
        function toggleProfileTheme() {
            const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem('agrisentry-theme', next);
            updateProfileChartTheme();
        }
        new QRCode(document.getElementById('qr-code-target'), {
            text: {!! json_encode(route('goat.profile.show', $goat->id)) !!},
            width: 200,
            height: 200,
            colorDark: '#1a1a1a',
            colorLight: '#ffffff',
        });

        async function deleteThisGoat() {
            if (!confirm('Delete {{ $goat->name }}? This also removes their health logs, medical records, and alerts. This cannot be undone.')) return;
            try {
                const response = await fetch('/api/goats/{{ $goat->id }}', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message || 'Could not delete this goat.');
                }
                window.location.href = '/agrisentry';
            } catch (error) {
                alert(error.message || 'Could not delete this goat.');
            }
        }

        function downloadQR() {
            const canvas = document.querySelector('#qr-code-target canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = '{{ $goat->name }}-qr-code.png';
                link.click();
            }
        }

        function switchTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked tab
            event.target.classList.add('active');

            // Charts created while their tab was hidden have no size yet — force a resize now that it's visible
            if (tabName === 'vitals' && tempChartVitals && motionChartVitals) {
                tempChartVitals.resize();
                motionChartVitals.resize();
            }
        }

        @php
            $healthLogsForChart = $healthLogs->map(function ($log) {
                return [
                    'date' => $log->created_at->toIso8601String(),
                    'temperature' => $log->temperature !== null ? (float) $log->temperature : null,
                    'event_type' => $log->event_type,
                    'motion_anomaly' => $log->motion_anomaly,
                    'movement' => $log->movement,
                    'description' => $log->description,
                    'severity' => $log->severity,
                ];
            });
        @endphp
        const healthLogsData = @json($healthLogsForChart);

        let currentPeriod = 'monthly';
        let tempChartVitals = null;
        let motionChartVitals = null;

        function updateProfileChartTheme() {
            const dark = document.documentElement.dataset.theme === 'dark';
            const textColor = dark ? '#9aa5b8' : '#64748b';
            const gridColor = dark ? 'rgba(154,165,184,.16)' : 'rgba(100,116,139,.16)';
            [tempChartVitals, motionChartVitals].forEach(chart => {
                if (!chart) return;
                Object.values(chart.options.scales || {}).forEach(scale => {
                    scale.grid = { ...(scale.grid || {}), color: gridColor };
                    scale.ticks = { ...(scale.ticks || {}), color: textColor };
                    if (scale.title) scale.title.color = textColor;
                });
                chart.update('none');
            });
        }

        const PERIOD_LABELS = { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly' };
        // Weekly/monthly now look back further and aggregate into one point per week/month
        // (instead of one point per raw log within a short window).
        const PERIOD_DAYS = { daily: 1, weekly: 84, monthly: 365 };

        function periodStartDate(period) {
            const start = new Date();
            start.setDate(start.getDate() - PERIOD_DAYS[period]);
            return start;
        }

        function isAnomalyLog(log) { return Boolean(log.motion_anomaly); }

        /** Monday-start date (YYYY-MM-DD) of the week containing `date`. */
        function weekBucketKey(date) {
            const d = new Date(date);
            const dayOffset = (d.getDay() + 6) % 7;
            d.setDate(d.getDate() - dayOffset);
            d.setHours(0, 0, 0, 0);
            return d.toISOString().slice(0, 10);
        }

        function monthBucketKey(date) {
            const d = new Date(date);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        }

        function bucketKeyForLog(dateStr, period) {
            if (period === 'weekly') return weekBucketKey(dateStr);
            if (period === 'monthly') return monthBucketKey(dateStr);
            return new Date(dateStr).toISOString().slice(0, 10);
        }

        function bucketLabel(key, period) {
            if (period === 'weekly') {
                const start = new Date(key);
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                return `${start.toLocaleDateString(document.documentElement.lang, { month: 'short', day: 'numeric' })}–${end.toLocaleDateString(document.documentElement.lang, { day: 'numeric' })}`;
            }
            if (period === 'monthly') {
                const [y, m] = key.split('-').map(Number);
                return new Date(y, m - 1, 1).toLocaleDateString(document.documentElement.lang, { month: 'short', year: 'numeric' });
            }
            return new Date(key).toLocaleDateString(document.documentElement.lang, { month: 'short', day: 'numeric' });
        }

        /** Ordered list of {key, label} buckets spanning the whole lookback window for the period. */
        function buildPeriodBuckets(period) {
            const now = new Date();
            if (period === 'weekly') {
                const currentWeekStart = new Date(weekBucketKey(now));
                const buckets = [];
                for (let i = 11; i >= 0; i--) {
                    const start = new Date(currentWeekStart);
                    start.setDate(start.getDate() - i * 7);
                    const key = start.toISOString().slice(0, 10);
                    buckets.push({ key, label: bucketLabel(key, period) });
                }
                return buckets;
            }
            if (period === 'monthly') {
                const buckets = [];
                for (let i = 11; i >= 0; i--) {
                    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
                    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
                    buckets.push({ key, label: bucketLabel(key, period) });
                }
                return buckets;
            }
            const key = now.toISOString().slice(0, 10);
            return [{ key, label: bucketLabel(key, period) }];
        }

        function renderCharts() {
            const selectedDate = document.getElementById('vitals-date').value;
            const start = periodStartDate(currentPeriod);
            const filtered = healthLogsData
                .filter(log => selectedDate ? new Date(log.date).toLocaleDateString() === new Date(selectedDate + 'T00:00:00').toLocaleDateString() : new Date(log.date) >= start)
                .sort((a, b) => new Date(a.date) - new Date(b.date));

            const findings = document.getElementById('motion-findings');
            findings.replaceChildren();
            const motionLogs = filtered.filter(log => log.motion_anomaly || log.movement || /motion/i.test(log.event_type || ''));
            motionLogs.slice().reverse().forEach(log => {
                const row = document.createElement('tr');
                [new Date(log.date).toLocaleString(document.documentElement.lang), log.motion_anomaly || log.movement || 'Motion event (type not specified)', [log.event_type, log.description].filter(Boolean).join(' - ')].forEach(value => {
                    const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
                });
                findings.appendChild(row);
            });
            if (!motionLogs.length) { const row = findings.insertRow(); const cell = row.insertCell(); cell.colSpan = 3; cell.textContent = 'No motion readings in this period.'; }
            if (typeof Chart === 'undefined') return;
            const tempPoints = filtered.filter(log => log.temperature !== null);

            let tempLabels, tempValues;
            if (selectedDate || currentPeriod === 'daily') {
                tempLabels = tempPoints.map(log => new Date(log.date).toLocaleString(document.documentElement.lang, {
                    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
                }));
                tempValues = tempPoints.map(log => log.temperature);
            } else {
                // Weekly/monthly: one averaged point per bucket instead of one point per raw log.
                const sums = {};
                const counts = {};
                tempPoints.forEach(log => {
                    const key = bucketKeyForLog(log.date, currentPeriod);
                    sums[key] = (sums[key] || 0) + log.temperature;
                    counts[key] = (counts[key] || 0) + 1;
                });
                const buckets = buildPeriodBuckets(currentPeriod).filter(b => counts[b.key]);
                tempLabels = buckets.map(b => b.label);
                tempValues = buckets.map(b => Math.round((sums[b.key] / counts[b.key]) * 10) / 10);
            }

            document.getElementById('temp-chart-vitals-empty').style.display = tempValues.length ? 'none' : 'block';
            document.getElementById('temp-chart-vitals').style.display = tempValues.length ? '' : 'none';

            if (!tempChartVitals) {
                tempChartVitals = new Chart(document.getElementById('temp-chart-vitals').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: tempLabels,
                        datasets: [{
                            label: dashboardText('Temperature') + ' (°C)',
                            data: tempValues,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(23,76,60,0.12)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { title: { display: true, text: '°C' } } },
                    },
                });
            } else {
                tempChartVitals.data.labels = tempLabels;
                tempChartVitals.data.datasets[0].data = tempValues;
                tempChartVitals.update();
            }

            const buckets = selectedDate ? [{key: selectedDate, label: selectedDate}] : buildPeriodBuckets(currentPeriod);
            const bucketLabels = buckets.map(b => b.label);
            const anomalyCounts = buckets.map(b =>
                filtered.filter(log => (selectedDate || bucketKeyForLog(log.date, currentPeriod) === b.key) && isAnomalyLog(log)).length
            );

            const otherMotionCounts = buckets.map(b => filtered.filter(log => (selectedDate || bucketKeyForLog(log.date, currentPeriod) === b.key) && !isAnomalyLog(log) && (log.movement || /motion/i.test(log.event_type || ''))).length);
            if (!motionChartVitals) {
                motionChartVitals = new Chart(document.getElementById('motion-chart-vitals').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: bucketLabels,
                        datasets: [{ label: dashboardText('Specific motion anomalies'), data: anomalyCounts, backgroundColor: '#dc2626' }, { label: dashboardText('Other motion readings'), data: otherMotionCounts, backgroundColor: '#16a34a' }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: true } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    },
                });
            } else {
                motionChartVitals.data.labels = bucketLabels;
                motionChartVitals.data.datasets[0].data = anomalyCounts;
                motionChartVitals.data.datasets[1].data = otherMotionCounts;
                motionChartVitals.update();
            }

            if (tempChartVitals) { tempChartVitals.data.datasets[0].label = dashboardText('Temperature') + ' (°C)'; tempChartVitals.update('none'); }
            if (motionChartVitals) { motionChartVitals.data.datasets[0].label = dashboardText('Specific motion anomalies'); motionChartVitals.data.datasets[1].label = dashboardText('Other motion readings'); motionChartVitals.update('none'); }
            document.querySelectorAll('.period-label').forEach(el => { el.textContent = PERIOD_LABELS[currentPeriod]; });
            updateProfileChartTheme();
        }

        function filterByTime(period) {
            document.getElementById('vitals-date').value = '';
            currentPeriod = period;
            document.querySelectorAll('.time-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === period);
            });
            renderCharts();
        }

        function openAddRecordModal() {
            const modal = document.getElementById('add-record-modal');
            const form = document.getElementById('add-record-form');
            const error = document.getElementById('add-record-error');
            form.reset();
            error.style.display = 'none';
            modal.hidden = false;
        }

        function closeAddRecordModal() {
            document.getElementById('add-record-modal').hidden = true;
        }

        async function submitAddRecord(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const error = document.getElementById('add-record-error');
            const submitButton = document.getElementById('add-record-submit');
            const values = new FormData(form);
            const attachment = form.elements.reference_photo?.files?.[0];

            if (attachment && attachment.size > 20 * 1024 * 1024) {
                error.textContent = 'The attachment must be 20 MB or smaller.';
                error.style.display = 'block';
                return;
            }

            error.style.display = 'none';
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';

            try {
                const response = await fetch('/api/goats/{{ $goat->id }}/medical-records', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: values,
                });
                const data = await response.json();
                if (!response.ok) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : data.message;
                    throw new Error(validationMessage || 'Could not save the medical record.');
                }
                window.location.reload();
            } catch (submissionError) {
                error.textContent = submissionError.message || 'Could not save the medical record.';
                error.style.display = 'block';
                submitButton.disabled = false;
                submitButton.textContent = 'Save Record';
            }
        }

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !document.getElementById('add-record-modal').hidden) {
                closeAddRecordModal();
            }
        });

        document.addEventListener('agrisentry:language', renderCharts);
        renderCharts();
    </script>
@include('partials.firebase-live')
</div>
</body>
</html>
