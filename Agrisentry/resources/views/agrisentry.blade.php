<!DOCTYPE html>
<html lang="en">
<head>
@include('partials.language')

<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AgriSentry — Herd Health Dashboard</title>
<link rel="icon" href="/images/anuvimco-logo.png">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-role" content="{{ auth()->user()->role ?? '' }}">
<meta name="user-name" content="{{ auth()->user()->name ?? '' }}">

<script>
    // The operations console is dark by default, matching the low-light barn UI.
    // A user's explicit theme choice still wins on subsequent visits.
    document.documentElement.dataset.theme = localStorage.getItem('agrisentry-theme') || 'light';
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
@include('partials.design-system')
</style>
@include('partials.battery-status')
<style>
.log-date-toolbar { display:flex; align-items:flex-end; flex-wrap:wrap; gap:16px; padding:18px 20px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:20px; }
body:has(.app) #screen-dashboard .dash-grid { grid-template-columns:minmax(0,1fr); }
body:has(.app) #screen-dashboard .tile-grid { grid-template-columns:repeat(auto-fill,minmax(min(100%,250px),1fr)); }
.language-select { display:block; width:100%; margin:8px 0 16px; min-height:42px; padding:8px; border:1px solid var(--border); border-radius:8px; background:var(--bg-card); color:var(--text-1); font:inherit; }
.sidebar-foot label { font-size:12px; font-weight:600; }
.log-date-toolbar .form-field { flex:0 1 220px; min-width:180px; margin:0; }
.log-date-toolbar .form-field label { display:block; margin-bottom:8px; }
.log-date-toolbar select, .log-date-toolbar input, .log-date-toolbar > .btn { min-height:44px; }
.log-date-toolbar .form-field input, .log-date-toolbar .form-field select { width:100%; color-scheme:light; }
[data-theme="dark"] .log-date-toolbar input, [data-theme="dark"] .log-date-toolbar select { color-scheme:dark; }
@media(max-width:600px) { .log-date-toolbar .form-field { flex:1 1 100%; min-width:0; } .log-date-toolbar > .btn { width:100%; justify-content:center; } }
</style>
</head>

<body>

<!-- ICON SPRITE -->
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

<div class="toast-stack" id="toast-stack"></div>

<div class="app">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand" onclick="goScreen('dashboard')">
            <div class="brand-mark has-logo"><img src="/images/agrisentry-logo-v2-transparent.png" alt="AgriSentry" class="brand-mark-img" onerror="this.style.display='none'"></div>
            <div>
                <h1 class="brand-name">AgriSentry</h1>
                <div class="brand-sub">ANMPC · Goat Health</div>
            </div>
        </div>

        <nav class="nav-scroll" aria-label="Main navigation">
            <div class="nav-section">Overview</div>
            <div class="nav-item active" id="nav-dashboard" onclick="goScreen('dashboard')">
                <svg class="icon"><use href="#i-grid"/></svg> Dashboard
            </div>

            <div class="nav-section">Herd</div>
            <div class="nav-item" id="nav-animals" onclick="goScreen('animals')">
                <svg class="icon"><use href="#i-goat"/></svg> All Goats
            </div>
            <div class="nav-item" id="nav-logs" onclick="goScreen('logs')">
                <svg class="icon"><use href="#i-activity"/></svg> Health Logs
                <span class="nav-badge zero" id="logs-badge">0</span>
            </div>
            <div class="nav-item" id="nav-medical" onclick="goScreen('medical')">
                <svg class="icon"><use href="#i-clipboard"/></svg> Medical Records
            </div>
            <div class="nav-item" id="nav-collars" onclick="goScreen('collars')">
                <svg class="icon"><use href="#i-battery"/></svg> Collars
            </div>
            <div class="nav-item" id="nav-reports" onclick="goScreen('reports')">
                <svg class="icon"><use href="#i-chart"/></svg> Report Analytics
            </div>

            <div class="nav-section">System</div>
            <div class="nav-item" id="nav-ai" onclick="goScreen('ai')">
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
            <label for="dashboard-language" data-i18n="Language">Language</label>
            <select id="dashboard-language" class="language-select" onchange="changeDashboardLanguage(this.value)">
                <option value="en">English</option>
                <option value="ceb">Cebuano</option>
                <option value="fil">Filipino</option>
                <optgroup label="Other languages">
                    <option value="es">Español (Spanish)</option>
                </optgroup>
            </select>
            <div class="led-legend">
                <span><span class="led led-b"></span> &lt;33.0°</span>
                <span><span class="led led-g"></span> 33–38.5°</span>
                <span><span class="led led-r"></span> &gt;38.5°</span>
            </div>
        </div>
    </div>

    <!-- MAIN COLUMN -->
    <div class="main-col">
        <header class="topbar">
            <a href="/species" class="btn" aria-label="Back to livestock selection">&larr; Livestock</a>
            <div class="burger" onclick="toggleSidebar()"><svg class="icon"><use href="#i-menu"/></svg></div>
            <div class="topbar-title" id="topbar-title">Herd Dashboard</div>
            <div class="topbar-spacer"></div>
            <div class="status-pill" id="server-status">
                <div class="status-dot"></div> API Connected
            </div>
            <div class="icon-btn" onclick="toggleTheme()" title="Toggle theme">
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

        <main class="main">

            <!-- DASHBOARD -->
            <div class="screen active" id="screen-dashboard">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">Herd Dashboard</h2>
                        <div class="page-sub mono" id="last-sync">Last sync: loading…</div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary btn-sm" onclick="openAddGoat()"><svg class="icon"><use href="#i-plus"/></svg> Add Goat</button>
                        <button class="btn btn-sm" onclick="loadData()"><svg class="icon"><use href="#i-refresh"/></svg> Refresh</button>
                    </div>
                </div>

                <div class="error-box" id="error-box"></div>

                <div class="stats-grid">
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Total Goats</span><span class="stat-icon si-green"><svg class="icon"><use href="#i-goat"/></svg></span></div>
                        <div class="stat-val" id="total-goats">0</div>
                        <div class="stat-foot">Registered in herd</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Normal</span><span class="stat-icon si-green"><svg class="icon"><use href="#i-check"/></svg></span></div>
                        <div class="stat-val" id="normal-goats">0</div>
                        <div class="stat-foot">33.0°C – 38.5°C</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Warning</span><span class="stat-icon si-amber"><svg class="icon"><use href="#i-eye"/></svg></span></div>
                        <div class="stat-val" id="monitoring-goats">0</div>
                        <div class="stat-foot">Being watched</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Urgent</span><span class="stat-icon si-red"><svg class="icon"><use href="#i-alert"/></svg></span></div>
                        <div class="stat-val" id="urgent-goats">0</div>
                        <div class="stat-foot">Needs attention</div>
                    </div>
                </div>

                <div class="dash-grid">
                    <div>
                        <div class="panel">
                            <div class="panel-head">
                                <h3 class="panel-title"><svg class="icon"><use href="#i-goat"/></svg> Herd Summary</h3>
                                <button class="btn btn-sm" onclick="goScreen('animals')">View all <svg class="icon"><use href="#i-arrow"/></svg></button>
                            </div>
                            <div class="panel-body" tabindex="0" role="region" aria-label="Animal tiles, scroll to see all animals">
                                <div class="tile-grid" id="dashboard-goats"><div class="empty">Loading goats…</div></div>
                            </div>
                            <p class="dashboard-scroll-hint">Scroll up or down to see all animals.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ALL ANIMALS -->
            <div class="screen" id="screen-animals">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">All Goats</h2>
                        <div class="page-sub">Registered goats from the herd database</div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary btn-sm" onclick="openAddGoat()"><svg class="icon"><use href="#i-plus"/></svg> Add Goat</button>
                        <button class="btn btn-sm" onclick="loadData()"><svg class="icon"><use href="#i-refresh"/></svg> Refresh</button>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <svg class="icon"><use href="#i-search"/></svg>
                        <input type="text" class="search-input" id="animal-search" aria-label="Search animals" placeholder="Search by name, code, owner, barn…" oninput="renderAnimals()">
                    </div>
                    <div class="filter-row">
                        <button class="filter-pill active" onclick="filterGoats(event,'all')">All (<span id="animal-all-count">0</span>)</button>
                        <button class="filter-pill" onclick="filterGoats(event,'normal')"><span class="led led-g"></span> Normal (<span id="animal-normal-count">0</span>)</button>
                        <button class="filter-pill" onclick="filterGoats(event,'warning')"><span class="led" style="background:var(--amber)"></span> Warning (<span id="animal-monitoring-count">0</span>)</button>
                        <button class="filter-pill" onclick="filterGoats(event,'urgent')"><span class="led led-r"></span> Urgent (<span id="animal-urgent-count">0</span>)</button>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-body tight">
                        <div class="table-wrap">
                            <table class="tbl">
                                <thead>
                                    <tr><th scope="col">Goat</th><th scope="col">Collar ID</th><th scope="col">Temperature</th><th scope="col">Status</th><th scope="col">Battery</th><th scope="col">Actions</th></tr>
                                </thead>
                                <tbody id="animals-tbody"><tr class="skel-row"><td colspan="6"><div class="skel"></div></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="animals-footer"><span id="animals-result-count">Showing 0 of 0 animals</span><div class="pagination"><button disabled>‹ Previous</button><button class="active">1</button><button disabled>Next ›</button></div></div>
                </div>
            </div>

            <!-- HEALTH LOGS -->
            <div class="screen" id="screen-logs">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">Health Logs</h2>
                        <div class="page-sub">Temperature and movement event history</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="exportLogsCSV()"><svg class="icon"><use href="#i-download"/></svg> Export CSV</button>
                </div>

                <div class="toolbar">
                    <div class="toolbar-label"><b>Filter by Event Type</b><span>Refine logs by event category</span></div>
                    <div class="filter-row">
                        <label for="log-motion" class="sr-only">Motion anomaly</label><select id="log-motion" class="filter-select" onchange="renderHealthLogs()"><option value="all">All motion</option><option value="any">Any motion anomaly</option><option>Prolonged Inactivity</option><option>Excessive Movement</option><option value="none">No motion anomaly</option></select>
                        <button class="filter-pill active" onclick="filterLogs(event,'all')"><svg class="icon"><use href="#i-list"/></svg> All Events</button>
                        <button class="filter-pill" onclick="filterLogs(event,'high')"><span style="color:var(--red)">♨</span> High Temp</button>
                        <button class="filter-pill" onclick="filterLogs(event,'low')"><span style="color:var(--blue)">♨</span> Low Temp</button>
                        <button class="filter-pill" onclick="filterLogs(event,'normal')"><svg class="icon" style="color:var(--green)"><use href="#i-check"/></svg> Normal</button>
                    </div>
                </div>

                <div class="toolbar log-date-toolbar">
                    <div class="form-field"><label for="log-period">Period</label><select id="log-period" onchange="renderHealthLogs()"><option value="all">All dates</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
                    <div class="form-field"><label for="log-date">Specific date</label><input type="date" id="log-date" onchange="renderHealthLogs()"></div>
                    <button type="button" class="btn" onclick="document.getElementById('log-date').value='';renderHealthLogs()"><svg class="icon"><use href="#i-x"/></svg> Clear date</button>
                </div>
                <div class="stats-grid log-stats">
                    <div class="stat"><div class="stat-top"><span class="stat-label">High Temp Events</span><span class="stat-icon si-red"><svg class="icon"><use href="#i-alert"/></svg></span></div><div class="stat-val" id="logs-high-count">0</div><div class="stat-foot">Needs immediate attention</div></div>
                    <div class="stat"><div class="stat-top"><span class="stat-label">Monitoring Events</span><span class="stat-icon si-amber"><svg class="icon"><use href="#i-eye"/></svg></span></div><div class="stat-val" id="logs-low-count">0</div><div class="stat-foot">Being watched</div></div>
                    <div class="stat"><div class="stat-top"><span class="stat-label">Normal Events</span><span class="stat-icon si-green"><svg class="icon"><use href="#i-check"/></svg></span></div><div class="stat-val" id="logs-normal-count">0</div><div class="stat-foot">Healthy goats</div></div>
                    <div class="stat"><div class="stat-top"><span class="stat-label">Total Log Entries</span><span class="stat-icon si-blue"><svg class="icon"><use href="#i-clipboard"/></svg></span></div><div class="stat-val" id="logs-total-count">0</div><div class="stat-foot">Records this period</div></div>
                </div>

                <div class="panel">
                    <div class="panel-body tight">
                        <div class="log-timeline" id="health-log-list">
                            <div class="empty">Loading health logs…</div>
                        </div>
                    </div>
                    <div class="health-log-footer"><span id="health-log-result-count">Showing 0 entries</span><div class="pagination"><button disabled>‹</button><button class="active">1</button><button>2</button><button>3</button><button>...</button><button>32</button><button>›</button></div></div>
                </div>
            </div>

            <!-- MEDICAL RECORDS -->
            <div class="screen" id="screen-medical">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">Medical Records</h2>
                        <div class="page-sub">Vaccination, treatment, and medicine history</div>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-sm" onclick="downloadDocument('/api/medical-records/export','agrisentry-medical-records.pdf')">Download all records (PDF)</button>
                        <button class="btn btn-primary btn-sm" onclick="openAddMedical()"><svg class="icon"><use href="#i-plus"/></svg> Add Record</button>
                    </div>
                </div>

                <div class="panel records-panel">
                    <div class="panel-body tight">
                        <div class="log-timeline" id="medical-record-list">
                            <div class="empty">Loading medical records…</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLLARS -->
            <div class="screen" id="screen-collars">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">Collars</h2>
                        <div class="page-sub">Collar-to-goat assignment, battery, and connectivity status</div>
                    </div>
                    <div class="btn-row"><button class="btn btn-primary btn-sm" onclick="openAddCollar()"><svg class="icon"><use href="#i-plus"/></svg> Add Collar</button><button class="btn btn-sm" onclick="loadData()"><svg class="icon"><use href="#i-refresh"/></svg> Refresh</button></div>
                </div>

                <div class="panel collars-panel">
                    <div class="panel-body tight">
                        <div class="table-wrap">
                            <table class="tbl">
                                <thead>
                                    <tr><th scope="col">Collar Code</th><th scope="col">Dev EUI</th><th scope="col">Assigned Goat</th><th scope="col">Battery</th><th scope="col">Status</th><th scope="col">Last Seen</th><th scope="col">Action</th></tr>
                                </thead>
                                <tbody id="collars-tbody"><tr class="skel-row"><td colspan="7" style="padding-left:19px"><div class="skel"></div></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="collars-footer"><span id="collars-result-count">Showing 0 collars</span><div class="pagination"><button disabled>‹</button><button class="active">1</button><button disabled>›</button></div></div>
                </div>
            </div>

            <!-- REPORT ANALYTICS -->
            <div class="screen" id="screen-reports">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">Report Analytics</h2>
                        <div class="page-sub">Summary reports for goat health monitoring, alerts, and medical records</div>
                    </div>
                    <div class="btn-row" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end"><button class="btn btn-primary btn-sm" onclick="loadReport(currentReportPeriod)"><svg class="icon"><use href="#i-refresh"/></svg> Refresh Report</button><button class="btn btn-sm" onclick="downloadReport('pdf')"><svg class="icon"><use href="#i-download"/></svg> Download PDF</button><button class="btn btn-sm" onclick="downloadReport('csv')"><svg class="icon"><use href="#i-download"/></svg> Export CSV</button></div>
                </div>

                <div class="panel">
                    <div class="panel-body">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px">
                            <div>
                                <div class="profile-section-title" style="margin:0 0 4px">Report Period</div>
                                <div class="page-sub" style="margin:0" id="report-period-note">Showing all available records</div>
                            </div>
                            <div class="filter-row">
                                <button class="filter-pill" onclick="filterReportPeriod(event,'day')">Day</button>
                                <button class="filter-pill" onclick="filterReportPeriod(event,'week')">Week</button>
                                <button class="filter-pill" onclick="filterReportPeriod(event,'month')">Month</button>
                                <button class="filter-pill" onclick="filterReportPeriod(event,'year')">Year</button>
                                <button class="filter-pill active" onclick="filterReportPeriod(event,'all')">All</button>
                            </div>
                        </div>
                        <form id="report-dates" class="form-grid" style="margin:16px 0" onsubmit="event.preventDefault();applyReportDates()"><div class="form-field"><label for="report-start">Start date</label><input type="date" id="report-start" required></div><div class="form-field"><label for="report-end">End date</label><input type="date" id="report-end" required></div><div class="btn-row"><button class="btn btn-primary" type="submit">Apply dates</button><button class="btn" type="button" onclick="resetReportDates()">Clear dates</button></div></form><p id="report-error" role="alert" style="color:var(--red)"></p>
                        <div class="page-sub" style="margin-bottom:2px">Choose a preset or an inclusive date range. Goat status uses the latest health record in the selected period; temperatures use recorded readings.</div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Urgent Goats</span><span class="stat-icon si-red"><svg class="icon"><use href="#i-alert"/></svg></span></div>
                        <div class="stat-val" id="report-urgent">0</div>
                        <div class="stat-foot">Needs attention</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Monitoring</span><span class="stat-icon si-amber"><svg class="icon"><use href="#i-eye"/></svg></span></div>
                        <div class="stat-val" id="report-monitoring">0</div>
                        <div class="stat-foot">Under observation</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Normal</span><span class="stat-icon si-green"><svg class="icon"><use href="#i-check"/></svg></span></div>
                        <div class="stat-val" id="report-normal">0</div>
                        <div class="stat-foot">Healthy status</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span class="stat-label">Medical Records</span><span class="stat-icon si-slate"><svg class="icon"><use href="#i-clipboard"/></svg></span></div>
                        <div class="stat-val" id="report-medical">0</div>
                        <div class="stat-foot">Vaccine/Treatment</div>
                    </div>
                </div>

                <div class="panel"><div class="panel-head"><h3 class="panel-title">Motion Readings &amp; Findings</h3></div><div class="panel-body"><div class="stats-grid">
<div class="stat"><div class="stat-top"><span class="stat-label">Motion readings</span></div><div class="stat-val" id="report-motion_readings_count">0</div></div><div class="stat"><div class="stat-top"><span class="stat-label">Prolonged Inactivity</span></div><div class="stat-val" id="report-prolonged_inactivity_count">0</div></div><div class="stat"><div class="stat-top"><span class="stat-label">Excessive Movement</span></div><div class="stat-val" id="report-excessive_movement_count">0</div></div><div class="stat"><div class="stat-top"><span class="stat-label">Other motion readings</span></div><div class="stat-val" id="report-other_motion_count">0</div></div>
</div><p class="page-sub">Counts are recorded events in the selected period, not individual goats. Other readings include normal movement and motion events without a specific anomaly.</p></div></div>
                <div class="panel">
                    <div class="panel-head"><h3 class="panel-title"><svg class="icon"><use href="#i-activity"/></svg> Report Graphs</h3></div>
                    <div class="panel-body">
                        <div class="dash-grid">
                            <div>
                                <div class="profile-section-title" style="margin-top:0">Goat Health Status Graph</div>
                                <div id="report-status-graph"></div>
                            </div>
                            <div>
                                <div class="profile-section-title" style="margin-top:0">Temperature Summary Graph</div>
                                <div id="report-temp-graph"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3 class="panel-title"><svg class="icon"><use href="#i-clipboard"/></svg> Temperature Summary</h3></div>
                    <div class="panel-body">
                        <div class="stats-grid">
                            <div class="stat">
                                <div class="stat-top"><span class="stat-label">Average Temp</span></div>
                                <div class="stat-val" id="report-temp-avg">—</div>
                            </div>
                            <div class="stat">
                                <div class="stat-top"><span class="stat-label">Highest Temp</span></div>
                                <div class="stat-val t-h" id="report-temp-high">—</div>
                            </div>
                            <div class="stat">
                                <div class="stat-top"><span class="stat-label">Lowest Temp</span></div>
                                <div class="stat-val t-l" id="report-temp-low">—</div>
                            </div>
                            <div class="stat">
                                <div class="stat-top"><span class="stat-label">Health Logs</span></div>
                                <div class="stat-val" id="report-logs-count">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><h3 class="panel-title"><svg class="icon"><use href="#i-list"/></svg> Goat Status Distribution</h3></div>
                    <div class="panel-body tight" id="report-distribution"></div>
                </div>
            </div>

            <!-- AI VET ADVICE -->
            <div class="screen" id="screen-ai">
                <div class="page-header">
                    <div>
                        <h2 class="page-title">AI Vet Advice</h2>
                    </div>
                </div>

                <div class="ai-layout">
                    <div class="chat-box">
                        <div class="panel-head">
                            <h3 class="panel-title"><svg class="icon"><use href="#i-message"/></svg> Conversation</h3>
                            <span class="status-chip status-normal">Gemini</span>
                        </div>

                        <div class="chat-body" id="chat-body">
                            <div class="chat-message alert">
                                <div class="chat-top">
                                    <span class="status-chip status-urgent"><svg class="icon" style="width:11px;height:11px"><use href="#i-alert"/></svg> AUTO-ALERT</span>
                                    <span class="chat-time">Now</span>
                                </div>
                                <div class="chat-text alert-text">
                                    AgriSentry detected an abnormal goat health condition. If the collar shows a red or blue LED, or if the goat shows weak movement, prolonged inactivity, falling, or abnormal body orientation, inspect the goat immediately and contact a veterinarian if the condition continues.
                                </div>
                            </div>
                            <div class="chat-message ai">
                                <div class="chat-top">
                                    <span class="status-chip status-normal">Gemini</span>
                                    <span class="chat-time">Ready</span>
                                </div>
                                <div class="chat-text">
                                    Hello. I can provide simple AI-assisted veterinary guidance based on AgriSentry collar readings, temperature status, motion anomaly signs, and goat health records. This advice is only for monitoring support and does not replace professional veterinary diagnosis.
                                </div>
                            </div>
                        </div>

                        <div style="font-size:11px;color:var(--text-3);padding:8px 2px;line-height:1.5">
                            <svg class="icon" style="width:12px;height:12px;vertical-align:-2px;margin-right:3px"><use href="#i-alert"/></svg>
                            AI-generated guidance only — not a medical diagnosis. Gemini may occasionally provide inaccurate information. Always confirm with a licensed veterinarian.
                        </div>

                        <div class="chat-input-row">
                            <input type="text" class="chat-input" id="ai-question" aria-label="Ask a question" placeholder="Ask about your herd, symptoms, treatments…" onkeydown="if(event.key === 'Enter') sendGeminiQuestion()">
                            <input type="file" id="ai-photo" accept="image/jpeg,image/png,image/webp" hidden onchange="selectGeminiPhoto(this)">
                            <button type="button" class="btn" id="attach-picture" onclick="document.getElementById('ai-photo').click()" aria-label="Attach photo for AI advice" title="Attach photo for AI advice" style="padding:10px;flex-shrink:0">
                                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M16 27H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h20a3 3 0 0 1 3 3v6M3 21l8-9 6 7 5-6 3 3"/>
                                    <path d="m22 18 1-2h3l1 2 2 1 2 2-1 2v3l-2 1-1 3h-4l-1-2-3-1-1-3 1-3 3-1Z"/>
                                    <text x="21" y="25" stroke="none" fill="currentColor" font-size="7" font-family="Arial,sans-serif" font-weight="700">AI</text>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-primary" id="send-ai-message" onclick="sendGeminiQuestion()">Send <svg class="icon"><use href="#i-arrow"/></svg></button>
                        </div>
                        <div id="ai-photo-preview" hidden style="padding:10px 0">
                            <img id="ai-photo-thumbnail" alt="Attached photo preview" style="max-width:160px;max-height:120px;border-radius:8px">
                            <span id="ai-photo-name"></span>
                            <button type="button" class="btn" id="remove-ai-photo" onclick="removeGeminiPhoto()">Remove photo</button>
                            <div class="page-sub">Add a question if you like, then press Send for advice about this photo.</div>
                        </div>
                    </div>

                    <div>
                        <div class="side-card">
                            <h3 class="side-card-head"><span class="side-title-icon">ϟ</span> Quick Prompts</h3>
                            <div class="side-card-body">
                                <button type="button" class="prompt-btn red" onclick="usePrompt('What should I do if a goat has a high temperature reading?')"><i>♨</i><span>High temperature advice<small>What to do for high temperature</small></span><b>›</b></button>
                                <button type="button" class="prompt-btn blue" onclick="usePrompt('What should I do if a goat has low temperature?')"><i>♨</i><span>Low temperature advice<small>What to do for low temperature</small></span><b>›</b></button>
                                <button type="button" class="prompt-btn amber" onclick="usePrompt('What does weak movement or prolonged inactivity mean in goats?')"><i>◉</i><span>Motion anomaly meaning<small>What does motion anomaly mean?</small></span><b>›</b></button>
                                <button type="button" class="prompt-btn red" onclick="usePrompt('What does the red LED mean in AgriSentry?')"><i>☀</i><span>Red LED meaning<small>What does a red LED indicate?</small></span><b>›</b></button>
                                <button type="button" class="prompt-btn blue" onclick="usePrompt('What does the blue LED mean in AgriSentry?')"><i>☀</i><span>Blue LED meaning<small>What does a blue LED indicate?</small></span><b>›</b></button>
                            </div>
                        </div>

                        <div class="side-card">
                            <h3 class="side-card-head"><span class="side-title-icon">☼</span> LED Reference</h3>
                            <div class="side-card-body">
                                <div class="led-reference green-ref">
                                    <span class="led led-g"></span>
                                    <div><div class="led-title" style="color:var(--green-dk)">Green — Normal</div><div class="led-desc">33.0°C – 38.5°C</div></div>
                                </div>
                                <div class="led-reference red-ref">
                                    <span class="led led-r"></span>
                                    <div><div class="led-title" style="color:var(--red)">Red — High Temperature</div><div class="led-desc">&gt; 38.5°C · possible fever / heat stress</div></div>
                                </div>
                                <div class="led-reference blue-ref">
                                    <span class="led led-b"></span>
                                    <div><div class="led-title" style="color:var(--blue)">Blue — Low Temperature</div><div class="led-desc">&lt; 33.0°C · possible hypothermia</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL: ADD GOAT -->
<div class="modal-overlay" id="modal-add-goat">
    <div class="modal">
        <div class="modal-head">
            <div><h3 class="modal-title">Add Goat</h3><div class="modal-sub">Register a new animal in the herd</div></div>
            <div class="modal-close" onclick="closeModal('modal-add-goat')"><svg class="icon"><use href="#i-x"/></svg></div>
        </div>
        <form id="form-add-goat" onsubmit="return submitAddGoat(event)">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-field full"><label for="goat-name">Name <span class="req">*</span></label><input type="text" id="goat-name" name="name" required placeholder="e.g. Bituin"></div>
                    <div class="form-field"><label for="goat-breed">Breed</label><select id="goat-breed" name="breed"><option value="">Select breed</option><option>Boer</option><option>Anglo-Nubian</option><option>Saanen</option><option>Alpine</option><option>Toggenburg</option><option>Native</option><option>Crossbreed</option><option>Other</option></select></div>
                    <div class="form-field"><label for="goat-pregnancy">Pregnancy status</label>
                        <select id="goat-pregnancy" name="pregnancy_status"><option value="unknown">Unknown</option><option value="pregnant">Pregnant</option><option value="not_pregnant">Not pregnant</option></select>
                    </div>
                    <div class="form-field"><label for="goat-sex">Sex</label>
                        <select id="goat-sex" name="sex"><option value="">— Select —</option><option value="Male">Male</option><option value="Female">Female</option></select>
                    </div>
                    <div class="form-field"><label for="goat-age">Age</label><input type="text" id="goat-age" name="age" placeholder="e.g. 2 years"></div>
                    <div class="form-field"><label for="goat-weight">Weight</label><input type="text" id="goat-weight" name="weight" placeholder="e.g. 34 kg"></div>
                    <div class="form-field"><label for="goat-owner">Owner</label><input type="text" id="goat-owner" name="owner" placeholder="Owner name"></div>
                    <div class="form-field"><label for="goat-ear-tag">Ear Tag <span class="req">*</span></label><input id="goat-ear-tag" name="ear_tag" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" placeholder="e.g. GT-014 or 014"><span class="form-hint">This is also the Goat ID. Enter it only once.</span></div>
                    <div class="form-field"><label for="goat-color">Goat Color</label><input id="goat-color" name="color" maxlength="100"></div>
                    <div class="form-field"><label for="goat-collar-id">Collar ID</label><input type="text" id="goat-collar-id" name="collar_id" placeholder="e.g. COLLAR-014"></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeModal('modal-add-goat')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-goat"><svg class="icon"><use href="#i-plus"/></svg> Add Goat</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD MEDICAL RECORD -->
<div class="modal-overlay" id="modal-add-medical">
    <div class="modal">
        <div class="modal-head">
            <div><h3 class="modal-title">Add Medical Record</h3><div class="modal-sub">Vaccination, treatment, or checkup entry</div></div>
            <div class="modal-close" onclick="closeModal('modal-add-medical')"><svg class="icon"><use href="#i-x"/></svg></div>
        </div>
        <form id="form-add-medical" onsubmit="return submitAddMedical(event)">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-field full"><label for="medical-goat-select">Goat <span class="req">*</span></label>
                        <select name="goat_id" id="medical-goat-select" required><option value="">— Select goat —</option></select>
                    </div>
                    <div class="form-field"><label for="medical-record-type">Record Type <span class="req">*</span></label>
                        <select id="medical-record-type" name="record_type" required>
                            <option value="">— Select —</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Treatment">Treatment</option>
                            <option value="Checkup">Checkup</option>
                            <option value="Deworming">Deworming</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-field"><label for="medical-date-given">Date Given</label><input type="date" id="medical-date-given" name="date_given"></div>
                    <div class="form-field full"><label for="medical-title">Title <span class="req">*</span></label><input type="text" id="medical-title" name="title" required placeholder="e.g. CDT Vaccine"></div>
                    <div class="form-field"><label for="medical-next-due-date">Next Due Date</label><input type="date" id="medical-next-due-date" name="next_due_date"></div>
                    <div class="form-field"><label for="medical-administered-by">Administered By</label><input type="text" id="medical-administered-by" name="administered_by" placeholder="Veterinarian / staff name"></div>
                    <div class="form-field full"><label for="medical-reference-photo">Previous Diagnosis / Medication File</label><input type="file" id="medical-reference-photo" name="reference_photo" accept="image/jpeg,image/png,image/webp,application/pdf"><small>Optional image or PDF, up to 20 MB.</small></div>
                    <div class="form-field full"><label for="medical-description">Description</label><textarea id="medical-description" name="description" placeholder="Notes, dosage, observations…"></textarea></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeModal('modal-add-medical')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-medical"><svg class="icon"><use href="#i-plus"/></svg> Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD COLLAR -->
<div class="modal-overlay" id="modal-add-collar">
    <div class="modal">
        <div class="modal-head">
            <div><h3 class="modal-title">Add Collar</h3><div class="modal-sub">Register a collar and optionally assign it to a goat</div></div>
            <div class="modal-close" onclick="closeModal('modal-add-collar')"><svg class="icon"><use href="#i-x"/></svg></div>
        </div>
        <form id="form-add-collar" onsubmit="return submitAddCollar(event)">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-field full"><label for="collar-code">Collar Code <span class="req">*</span></label><input type="text" id="collar-code" name="collar_code" required placeholder="e.g. COL-001"></div>
                    <div class="form-field"><label for="collar-dev-eui">Dev EUI</label><input type="text" id="collar-dev-eui" name="dev_eui" placeholder="e.g. AABBCCDDEEFF0011"></div>
                    <div class="form-field"><label for="collar-battery">Battery %</label><input type="number" id="collar-battery" name="battery_level" min="0" max="100" placeholder="100"></div>
                    <div class="form-field full"><label for="collar-goat-select">Assign to Goat</label>
                        <select name="goat_id" id="collar-goat-select"><option value="">— Unassigned —</option></select>
                    </div>
                    <div class="form-hint" style="grid-column:1/-1">Dev EUI and Collar Code are how LoRaWAN uplinks (<span class="mono">/api/lorawan/uplink</span>) get matched back to this collar.</div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn" onclick="closeModal('modal-add-collar')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-collar"><svg class="icon"><use href="#i-plus"/></svg> Add Collar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: COLLAR ADDED (confirmation + print) -->
<div class="modal-overlay" id="modal-collar-added">
    <div class="modal">
        <div class="modal-head">
            <div><h3 class="modal-title">Collar Added</h3><div class="modal-sub">Print the collar info or QR code now</div></div>
            <div class="modal-close" onclick="closeModal('modal-collar-added')"><svg class="icon"><use href="#i-x"/></svg></div>
        </div>
        <div class="modal-body">
            <div class="print-only-card" id="collar-added-body"></div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" onclick="closeModal('modal-collar-added')">Done</button>
            <button type="button" class="btn btn-primary" onclick="window.print()"><svg class="icon"><use href="#i-download"/></svg> Print</button>
        </div>
    </div>
</div>

<!-- MODAL: GOAT PROFILE -->
<div class="modal-overlay" id="modal-profile">
    <div class="modal wide">
        <div class="modal-head">
            <div class="profile-head">
                <div class="profile-avatar">🐐</div>
                <div>
                    <h3 class="modal-title" id="profile-name">—</h3>
                    <div class="modal-sub" id="profile-code">—</div>
                </div>
            </div>
            <div class="modal-close" onclick="closeModal('modal-profile')"><svg class="icon"><use href="#i-x"/></svg></div>
        </div>
        <div class="modal-body" id="profile-body">
            <div class="empty">Loading profile…</div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-danger" id="profile-delete-btn"><svg class="icon"><use href="#i-trash"/></svg> Delete</button>
            <button type="button" class="btn" onclick="closeModal('modal-profile')">Close</button>
            <button type="button" class="btn" id="profile-view-full-btn">Open Full Profile →</button>
            <button type="button" class="btn btn-primary" id="profile-add-medical-btn"><svg class="icon"><use href="#i-plus"/></svg> Add Medical Record</button>
        </div>
    </div>
</div>


<script src="/js/qrcode.min.js"></script>
<script>
const API_BASE = "/api";

let goats = [];
let healthLogs = [];
let alerts = [];
let medicalRecords = [];
let collars = [];
let animalFilter = "all";
let logFilter = "all";
let profileGoatId = null;
let currentReportPeriod = "all";
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

/* ============ THEME ============ */
function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    document.getElementById("theme-icon").innerHTML = theme === "dark"
        ? '<use href="#i-sun"/>'
        : '<use href="#i-moon"/>';
    localStorage.setItem("agrisentry-theme", theme);
}
function toggleTheme() {
    const current = document.documentElement.getAttribute("data-theme") ||
        (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    applyTheme(current === "dark" ? "light" : "dark");
}
(function initTheme() {
    const saved = localStorage.getItem("agrisentry-theme");
    if (saved) applyTheme(saved);
    else applyTheme("light");
})();

/* ============ NAV ============ */
const SCREEN_TITLES = { dashboard: "Herd Dashboard", animals: "All Goats", logs: "Health Logs", medical: "Medical Records", collars: "Collars", reports: "Report Analytics", ai: "AI Vet Advice" };

function goScreen(screenName) {
    const feature = {dashboard:'dashboard',animals:'goats',logs:'health-logs',medical:'medical-records',collars:'collars',reports:'reports',ai:'gemini-advice'}[screenName];
    if (!FEATURE_PERMISSIONS[feature+'.read']) { toast('Your Admin has disabled access to this feature.', 'error'); return; }
    document.querySelectorAll(".screen").forEach(s => s.classList.remove("active"));
    const selected = document.getElementById("screen-" + screenName);
    if (selected) selected.classList.add("active");

    document.querySelectorAll(".nav-item").forEach(i => i.classList.remove("active"));
    const nav = document.getElementById("nav-" + screenName);
    if (nav) nav.classList.add("active");

    document.getElementById("topbar-title").textContent = dashboardText(SCREEN_TITLES[screenName] || "AgriSentry");
    document.getElementById("sidebar").classList.remove("open");

    if (screenName === "reports") loadReport(currentReportPeriod);
}
function toggleSidebar() { document.getElementById("sidebar").classList.toggle("open"); }

/* ============ TOASTS ============ */
function toast(message, type = "info") {
    const stack = document.getElementById("toast-stack");
    const el = document.createElement("div");
    el.className = "toast " + type;
    const iconId = type === "success" ? "i-check" : type === "error" ? "i-alert" : "i-message";
    el.innerHTML = `<svg class="icon toast-icon"><use href="#${iconId}"/></svg><div>${message}</div>`;
    stack.appendChild(el);
    setTimeout(() => { el.style.opacity = "0"; el.style.transition = "opacity .25s"; setTimeout(() => el.remove(), 250); }, 4000);
}

/* ============ MODALS ============ */
function openModal(id) { document.getElementById(id).classList.add("open"); }
function closeModal(id) {
    document.getElementById(id).classList.remove("open");
    const form = document.querySelector("#" + id + " form");
    if (form) form.reset();
}
document.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-overlay")) e.target.classList.remove("open");
});
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") document.querySelectorAll(".modal-overlay.open").forEach(m => m.classList.remove("open"));
});

/* ============ ERROR / STATUS ============ */
function setError(message, statusLabel = 'API Request Failed') {
    const box = document.getElementById("error-box");
    box.textContent = message;
    box.style.display = "flex";
    const status = document.getElementById("server-status");
    status.classList.add("down");
    status.replaceChildren(Object.assign(document.createElement('div'), {className: 'status-dot'}), document.createTextNode(statusLabel));
}
function clearError() {
    document.getElementById("error-box").style.display = "none";
    const status = document.getElementById("server-status");
    status.classList.remove("down");
    status.innerHTML = `<div class="status-dot"></div> API Connected`;
}

/* ============ DATA FETCH ============ */
async function fetchAPI(endpoint, options = {}) {
    options.headers = {
        ...(options.headers || {}),
        "X-CSRF-TOKEN": CSRF_TOKEN,
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json",
        "ngrok-skip-browser-warning": "true",
    };

    const attempts = (options.method || 'GET').toUpperCase() === 'GET' ? 2 : 1;
    for (let attempt = 0; attempt < attempts; attempt++) {
    let response;
    try {
        response = await fetch(`${API_BASE}${endpoint}`, {...options, signal: options.signal || AbortSignal.timeout(25000)});
    } catch (error) {
        if (attempt + 1 < attempts && !options.signal?.aborted) {
            await new Promise(resolve => setTimeout(resolve, 1000));
            continue;
        }
        throw new Error('Could not connect to the server. Please try again shortly.');
    }

    if (response.status === 401) {
        window.location.href = "/login";
        throw new Error("Session expired.");
    }

    if (response.status === 419) {
        window.location.reload();
        throw new Error("CSRF token expired.");
    }

    const data = await response.json().catch(() => null);
    if (attempt + 1 < attempts && ([502, 503, 504].includes(response.status) || (response.ok && !data))) {
        await new Promise(resolve => setTimeout(resolve, 1000));
        continue;
    }
    if ([502, 503, 504].includes(response.status)) throw new Error('The server connection is temporarily unavailable. Please try again shortly.');
    if (!data || typeof data !== 'object') throw new Error('The server returned an incomplete response. Please try again.');
    if (!response.ok) {
        const err = new Error(response.status >= 500 ? 'The server is temporarily unavailable. Please try again shortly.' : (data.message || `Request to ${endpoint} failed`));
        err.data = data;
        throw err;
    }
    return data;
    }
}

const FEATURE_PERMISSIONS = @json(auth()->user()->isAdmin() ? array_fill_keys(array_keys(\App\Services\AccountAccess::defaults('Staff')), true) : \App\Services\AccountAccess::permissions(auth()->user()->role));
function allowedRead(feature, query = '') { return FEATURE_PERMISSIONS[feature+'.read'] ? fetchAPI('/'+feature+query) : Promise.resolve({}); }
let dataLoadInProgress = false;
async function loadData(incremental = false) {
    if (dataLoadInProgress) return;
    dataLoadInProgress = true;
    try {
        const features = ['goats', 'health-logs', 'alerts', 'medical-records', 'collars'];
        const results = [];
        // Limit connection bursts through the tunnel to two requests at a time.
        for (let index = 0; index < features.length; index += 2) {
            results.push(...await Promise.allSettled(features.slice(index, index + 2).map(feature =>
                allowedRead(feature, feature === 'health-logs' && incremental && healthLogs.length
                    ? '?after_id='+healthLogs.reduce((max, log) => Math.max(max, Number(log.id)), 0) : '')
            )));
        }
        const [goatData, logData, alertData, medicalData, collarData] = results.map(result => result.status === 'fulfilled' ? result.value : null);
        const failed = features.filter((feature, index) => results[index].status === 'rejected');

        const oldGoats = JSON.stringify(goats), oldAlerts = JSON.stringify(alerts), oldMedical = JSON.stringify(medicalRecords), oldCollars = JSON.stringify(collars);
        if (goatData) goats = goatData.goats || [];
        const incomingLogs = logData?.health_logs || [];
        const oldLogCount = healthLogs.length;
        if (logData) {
            const incomingIds = new Set(incomingLogs.map(log => log.id));
            healthLogs = incremental ? [...incomingLogs, ...healthLogs.filter(log => !incomingIds.has(log.id))] : incomingLogs;
        }
        if (goatData && FEATURE_PERMISSIONS['goats.read']) {
            const existingGoats = new Set(goats.map(goat => goat.id));
            healthLogs = healthLogs.filter(log => existingGoats.has(log.goat_id));
        }
        if (alertData) alerts = alertData.alerts || [];
        if (medicalData) medicalRecords = medicalData.medical_records || [];
        if (collarData) collars = collarData.collars || [];

        if (failed.length) {
            const labels = {'goats': 'animals', 'health-logs': 'health logs', 'alerts': 'alerts', 'medical-records': 'medical records', 'collars': 'collars'};
            setError('Could not refresh '+failed.map(feature => labels[feature]).join(', ')+'. Previously loaded information is retained. Please try Refresh.', 'Refresh Incomplete');
        } else clearError();
        updateStats();
        if (!incremental || oldGoats !== JSON.stringify(goats)) { renderDashboardGoats(); renderAnimals(); populateGoatSelect(); }
        if (!incremental || incomingLogs.length || oldLogCount !== healthLogs.length) renderHealthLogs();
        if (!incremental || oldAlerts !== JSON.stringify(alerts)) renderAlerts();
        if (!incremental || oldMedical !== JSON.stringify(medicalRecords)) renderMedicalRecords();
        if (!incremental || oldCollars !== JSON.stringify(collars)) renderCollars();

        document.getElementById("last-sync").textContent = (failed.length ? "Partial refresh" : "Last sync") + ': ' + new Date().toLocaleString(document.documentElement.lang);
    } catch (e) {
        setError(e.name === 'TimeoutError' ? 'The server took too long to respond. Please try again.' : e.message || 'Could not reach the AgriSentry API. Please try again.');
    } finally {
        dataLoadInProgress = false;
    }
}

/* ============ TEMPERATURE / STATUS HELPERS ============
   Bands per AgriSentry spec: blue <33.0°C, green 33.0–38.5°C, red >38.5°C */
function band(temp) {
    const t = parseFloat(temp);
    if (isNaN(t)) return null;
    if (t > 38.5) return "high";
    if (t < 33.0) return "low";
    return "normal";
}
function getTempClass(temp) {
    const b = band(temp);
    if (b === "high") return "t-h";
    if (b === "low") return "t-l";
    if (b === "normal") return "t-n";
    return "";
}
function getLedClass(goat) {
    const b = band(goat.temperature);
    if (b === "high") return "led-r";
    if (b === "low") return "led-b";
    if (b === "normal") return "led-g";
    return String(goat.status || "").toLowerCase() === "urgent" ? "led-r" : "led-g";
}
function getStatusClass(status) {
    const s = String(status || "").toLowerCase();
    if (s === "urgent") return "status-urgent";
    if (s === "monitoring" || s === "warning") return "status-monitoring";
    return "status-normal";
}
/** Vet Advisory accent color for an alert — blue for low temperature, red for high/other. */
/** Full color set for an alert card — blue for low temperature, red for everything else (high temp, battery, movement). */
function advisoryAccentColor(alertType) {
    const type = String(alertType || "").toLowerCase();
    if (type.includes("low temperature")) {
        return {
            border: "var(--blue-bg-strong)",
            icon: "var(--blue)",
            chipBg: "var(--red-bg-strong)",
            chipText: "var(--red-dk)",
            cardBg: "var(--blue-bg)",
            cardBorder: "var(--blue-bg-strong)",
            text: "var(--blue)",
        };
    }
    return {
        border: "var(--red-bg-strong)",
        icon: "var(--red)",
        chipBg: "var(--red-bg-strong)",
        chipText: "var(--red-dk)",
        cardBg: "var(--red-bg)",
        cardBorder: "var(--red-bg-strong)",
        text: "var(--red)",
    };
}
function batteryClass(pct) {
    const v = parseFloat(pct);
    if (isNaN(v)) return "";
    if (v <= 20) return "low";
    if (v <= 50) return "mid";
    return "";
}
function batteryBadge(battery) {
    const v = parseFloat(battery);
    if (isNaN(v)) return `<span class="batt-txt">${battery || "N/A"}</span>`;
    const cls = batteryClass(v);
    return `
        <div class="batt-wrap">
            <div class="batt-bar"><div class="batt-fill ${cls}" style="width:${Math.max(0, Math.min(100, v))}%"></div></div>
            <span class="batt-txt">${v}%</span>
        </div>`;
}
function escapeHtml(str) {
    return String(str ?? "").replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]));
}

function goatIdLabel(goat) {
    return String(goat.id).padStart(3, "0") + " " + (goat.name || "Unnamed");
}

function goatProfileUrl(id) {
    return `${window.location.origin}/goat/${id}/profile`;
}

/* ============ STATS ============ */
function updateStats() {
    const count = s => goats.filter(g => String(g.status || "").toLowerCase() === s).length;
    document.getElementById("total-goats").textContent = goats.length;
    document.getElementById("normal-goats").textContent = count("normal");
    document.getElementById("monitoring-goats").textContent = count("monitoring") + count("warning");
    document.getElementById("urgent-goats").textContent = count("urgent");

    const logsBadge = document.getElementById("logs-badge");
    logsBadge.textContent = healthLogs.length;
    logsBadge.classList.toggle("zero", healthLogs.length === 0);

    const alertsBadge = document.getElementById("alerts-badge");
    alertsBadge.textContent = alerts.length;
    alertsBadge.classList.toggle("zero", alerts.length === 0);
}

/* ============ DASHBOARD TABLE ============ */
function renderDashboardGoats() {
    const grid = document.getElementById("dashboard-goats");
    // Monitoring is the legacy name for Warning used by existing records.
    const attentionGoats = goats.filter(goat => ['warning', 'monitoring', 'urgent'].includes(String(goat.status || '').trim().toLowerCase()));
    if (attentionGoats.length === 0) {
        grid.innerHTML = `<div class="empty">${escapeHtml(dashboardText("No goats currently have a warning or urgent status."))}</div>`;
        return;
    }
    // goats is already ordered Urgent -> Monitoring -> Normal by the API, so goats
    // needing attention are always the first tiles shown here.
    grid.innerHTML = attentionGoats.map(goat => {
        const isUrgent = String(goat.status || "").toLowerCase() === "urgent";
        return `
        <div class="goat-tile ${isUrgent ? "urgent" : ""}" onclick="openGoatProfile(${goat.id})">
            ${isUrgent ? `<div class="goat-tile-alert-banner">${escapeHtml(dashboardText("URGENT ALERT"))}</div>` : ""}
            <div class="goat-tile-head">
                <div>
                    <div class="goat-tile-name">${escapeHtml(goat.name)}</div>
                    <div class="goat-tile-sub">${escapeHtml(goat.code)} · ${escapeHtml(goat.breed) || "N/A"}</div>
                </div>
                <span class="status-chip ${getStatusClass(goat.status)}">${escapeHtml(dashboardText(goat.status || "Unknown"))}</span>
            </div>
            <div class="goat-tile-stats">
                ${goat.pregnancy_status === 'pregnant' ? `<div class="goat-tile-stat">
                    <div class="goat-tile-stat-label">${escapeHtml(dashboardText("Pregnancy status"))}</div>
                    <div style="font-weight:600;font-size:12.5px">${escapeHtml(dashboardText('Pregnant'))}</div>
                </div>` : ''}
                <div class="goat-tile-stat">
                    <div class="goat-tile-stat-label">${escapeHtml(dashboardText("Temperature"))}</div>
                    <div class="temp ${getTempClass(goat.temperature)}"><span class="led ${getLedClass(goat)}"></span> ${goat.temperature ?? "N/A"}°C</div>
                </div>
                <div class="goat-tile-stat">
                    <div class="goat-tile-stat-label">${escapeHtml(dashboardText("Movement Detector"))}</div>
                    <div style="font-weight:600;font-size:12.5px">${escapeHtml(goat.movement) || "N/A"}</div>
                </div>
            </div>
            <div class="goat-tile-foot">
                <span><b>${escapeHtml(dashboardText("COLLAR"))}</b>${goat.collar ? escapeHtml(goat.collar.collar_code) : escapeHtml(dashboardText("None"))}</span>
                <span><b>${escapeHtml(dashboardText("BATTERY"))}</b>${liveBatteryStatus(goat.collar?.battery_level ?? goat.battery, goat.collar?.last_seen)}</span>
            </div>
            <button type="button" class="btn btn-primary btn-sm goat-profile-btn" onclick="event.stopPropagation(); openGoatProfile(${goat.id})">${escapeHtml(dashboardText("View Animal Profile"))} <svg class="icon"><use href="#i-arrow"/></svg></button>
        </div>
    `;
    }).join("");
}

/* ============ ANIMALS TABLE ============ */
function filterGoats(event, filter) {
    animalFilter = filter;
    document.querySelectorAll("#screen-animals .filter-pill").forEach(b => b.classList.remove("active"));
    event.target.classList.add("active");
    renderAnimals();
}

function renderAnimals() {
    const table = document.getElementById("animals-tbody");
    const q = (document.getElementById("animal-search")?.value || "").toLowerCase().trim();

    document.getElementById("animal-all-count").textContent = goats.length;
    ["normal", "urgent"].forEach(status => {
        document.getElementById(`animal-${status}-count`).textContent = goats.filter(goat => String(goat.status || "").toLowerCase() === status).length;
    });
    document.getElementById("animal-monitoring-count").textContent = goats.filter(goat => ["warning", "monitoring"].includes(String(goat.status || "").toLowerCase())).length;

    let filtered = goats.filter(goat => {
        const status = String(goat.status || "").toLowerCase();
        if (animalFilter !== "all" && status !== animalFilter && !(animalFilter === "warning" && status === "monitoring")) return false;
        if (!q) return true;
        return [goat.name, goat.code, goat.collar?.collar_code, goat.owner, goat.barn, goat.breed]
            .some(v => String(v || "").toLowerCase().includes(q));
    });

    document.getElementById("animals-result-count").textContent = `Showing ${filtered.length} of ${goats.length} ${goats.length === 1 ? "animal" : "animals"}`;

    if (filtered.length === 0) {
        table.innerHTML = `<tr><td colspan="6"><div class="empty">No goats match this view.</div></td></tr>`;
        return;
    }

    table.innerHTML = filtered.map(goat => `
        <tr class="clickable" onclick="openGoatProfile(${goat.id})">
            <td><div class="animal-cell"><span class="animal-icon"><img src="/images/goat-avatar-realistic.png" alt="" aria-hidden="true"></span><span><b>${escapeHtml(goat.name)}</b><small>${escapeHtml(goat.code)}</small></span></div></td>
            <td>${goat.collar ? escapeHtml(goat.collar.collar_code) : "None"}</td>
            <td><div class="animal-temp"><span><span class="led ${getLedClass(goat)}"></span> <b class="temp ${getTempClass(goat.temperature)}">${goat.temperature ?? "N/A"}°C</b></span><small>${band(goat.temperature) === "low" ? "Low" : band(goat.temperature) === "high" ? "High" : "Normal"}</small></div></td>
            <td><span class="status-chip ${getStatusClass(goat.status)}">${escapeHtml(goat.status || "Unknown")}</span></td>
            <td><div class="animal-battery"><svg class="icon"><use href="#i-battery"/></svg><span>${liveBatteryStatus(goat.collar?.battery_level ?? goat.battery, goat.collar?.last_seen)}</span></div></td>
            <td><div class="animal-actions"><button type="button" class="row-btn view-details" onclick="event.stopPropagation(); openGoatProfile(${goat.id})">View Details <svg class="icon"><use href="#i-arrow"/></svg></button><button type="button" class="row-btn more-btn" aria-label="More actions">⋮</button></div></td>
        </tr>
    `).join("");
}

async function deleteGoat(id, name) {
    if (!confirm(`Delete ${name}? This also removes their health logs, medical records, and alerts. This cannot be undone.`)) return;
    try {
        await fetchAPI(`/goats/${id}`, { method: "DELETE" });
        toast(`${name} deleted.`, "success");
        await loadData();
    } catch (e) {
        toast(e.message || "Could not delete this goat.", "error");
    }
}

/* ============ HEALTH LOGS ============ */
function filterLogs(event, filter) {
    logFilter = filter;
    document.querySelectorAll("#screen-logs .filter-pill").forEach(b => b.classList.remove("active"));
    event.target.classList.add("active");
    renderHealthLogs();
}

function currentFilteredLogs() {
    return healthLogs.filter(log => {
        const date = new Date(log.created_at), chosen = document.getElementById('log-date').value;
        const period = document.getElementById('log-period').value;
        if (chosen) { if (date.toLocaleDateString('en-CA') !== new Date(chosen + 'T00:00:00').toLocaleDateString('en-CA')) return false; }
        else if (period !== 'all') { const start = new Date(); start.setHours(0,0,0,0); if (period === 'weekly') start.setDate(start.getDate()-6); if (period === 'monthly') start.setDate(1); if (!(date >= start && date <= new Date())) return false; }
        const motion=document.getElementById("log-motion").value;
        if(motion==="any" && !log.motion_anomaly)return false;
        if(motion==="none" && log.motion_anomaly)return false;
        if(!["all","any","none"].includes(motion) && log.motion_anomaly!==motion)return false;
        const b = band(log.temperature);
        if (logFilter === "high") return b === "high";
        if (logFilter === "low") return b === "low";
        if (logFilter === "normal") return b === "normal";
        return true;
    });
}

function renderHealthLogs() {
    const container = document.getElementById("health-log-list");
    const filtered = currentFilteredLogs();
    document.getElementById("logs-high-count").textContent = filtered.filter(log => band(log.temperature) === "high").length;
    document.getElementById("logs-low-count").textContent = filtered.filter(log => band(log.temperature) === "low").length;
    document.getElementById("logs-normal-count").textContent = filtered.filter(log => band(log.temperature) === "normal").length;
    document.getElementById("logs-total-count").textContent = filtered.length;

    if (filtered.length === 0) {
        container.innerHTML = `<div class="empty">No health logs found for this filter.</div>`;
        return;
    }

    container.innerHTML = filtered.map(log => {
        const b = band(log.temperature);
        let dot = "var(--green)", titleColor = "var(--text-0)", title = log.event_type || "Health Event";
        if (b === "high") { dot = "var(--red)"; titleColor = "var(--red)"; }
        else if (b === "low") { dot = "var(--blue)"; titleColor = "var(--blue)"; }

        const goatLabel = log.goat ? `${escapeHtml(log.goat.name)} (${escapeHtml(log.goat.code)})` : `Goat #${log.goat_id ?? "N/A"}`;

        return `
            <div class="log-entry">
                <div class="log-dot-col"><div class="log-dot" style="background:${dot}"></div><div class="log-line"></div></div>
                <div class="log-content">
                    <div class="log-time">${goatLabel} · ${log.created_at ? new Date(log.created_at).toLocaleString(document.documentElement.lang) : "No timestamp"}</div>
                    <div class="log-title" style="color:${titleColor}">${escapeHtml(title)}</div>
                    <div class="log-desc">
                        <b>Temp:</b> ${log.temperature ?? "N/A"}°C · <b>Movement:</b> ${escapeHtml(log.motion_anomaly || log.movement) || "N/A"} · <b>LED:</b> ${escapeHtml(log.led_status) || "N/A"} · <b>Severity:</b> ${escapeHtml(log.severity) || "N/A"}
                        ${log.description ? `<br>${escapeHtml(log.description)}` : ""}
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

// Reference-style horizontal health event cards.
renderHealthLogs = function() {
    const container = document.getElementById("health-log-list");
    const filtered = currentFilteredLogs();
    document.getElementById("logs-high-count").textContent = healthLogs.filter(log => band(log.temperature) === "high").length;
    document.getElementById("logs-low-count").textContent = healthLogs.filter(log => band(log.temperature) === "low").length;
    document.getElementById("logs-normal-count").textContent = healthLogs.filter(log => band(log.temperature) === "normal").length;
    document.getElementById("logs-total-count").textContent = healthLogs.length;
    document.getElementById("health-log-result-count").textContent = `Showing ${filtered.length ? 1 : 0} to ${Math.min(10, filtered.length)} of ${filtered.length} entries`;
    if (!filtered.length) { container.innerHTML = `<div class="empty">No health logs found for this filter.</div>`; return; }
    container.innerHTML = filtered.slice(0, 10).map(log => {
        const b = band(log.temperature), led = b === "high" ? "led-r" : b === "low" ? "led-b" : "led-g";
        const goatName = log.goat ? escapeHtml(log.goat.name) : `Goat #${log.goat_id ?? "N/A"}`;
        const goatCode = log.goat ? escapeHtml(log.goat.code) : "N/A";
        const severity = String(log.severity || '').toLowerCase();
        const border = ['urgent','high','critical'].includes(severity) ? '#DC2626' : ['warning','monitoring'].includes(severity) ? '#EA580C' : 'var(--border)';
        return `<div class="log-entry health-event-row" style="border:2px solid ${border}">
            <div class="event-goat"><span aria-label="${b || 'Unknown'} temperature" style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${b === 'low' ? '#2563EB' : b === 'high' ? '#DC2626' : '#16A34A'}"></span><span class="event-temp-icon ${b}">♨</span><div><b>${goatName}</b><span>${goatCode}</span><small>◷ ${log.created_at ? new Date(log.created_at).toLocaleString(document.documentElement.lang) : "No timestamp"}</small></div></div>
            <div class="event-details"><span><small>Event Type</small><b>${escapeHtml(log.event_type || "Health Event")}</b></span><span><small>Collar ID</small><b>${log.goat?.collar?.collar_code ? escapeHtml(log.goat.collar.collar_code) : "N/A"}</b></span></div>
            <div class="event-reading"><span><small>Temperature</small><b class="temp ${getTempClass(log.temperature)}"><i class="led ${led}"></i>${log.temperature ?? "N/A"}°C</b></span><span><small>Movement</small><b class="t-n">${escapeHtml(log.motion_anomaly || log.movement) || "N/A"}</b></span></div>
            <div class="event-led"><small>LED Status</small><b><i class="led ${led}"></i>${escapeHtml(log.led_status) || "N/A"}</b></div>
            <div class="event-severity"><small>Severity</small><span class="status-chip ${String(log.severity).toLowerCase().includes("high") ? "status-urgent" : getStatusClass(log.severity)}">${escapeHtml(log.severity) || "N/A"}</span></div>
        </div>`;
    }).join("");
};

function exportLogsCSV() {
    const rows = currentFilteredLogs();
    if (rows.length === 0) { toast("No health logs to export.", "error"); return; }

    const header = ["ID", "Goat", "Ear Tag / ID", "Event Type", "Temperature", "Movement", "Motion Anomaly", "LED Status", "Severity", "Description", "Recorded At"];
    const lines = [header.join(",")];

    rows.forEach(log => {
        const cells = [
            log.id,
            log.goat?.name || "",
            log.goat?.code || "",
            log.event_type || "",
            log.temperature ?? "",
            log.movement || "",
            log.motion_anomaly || "",
            log.led_status || "",
            log.severity || "",
            log.description || "",
            log.created_at || "",
        ].map(v => `"${String(v).replace(/"/g, '""')}"`);
        lines.push(cells.join(","));
    });

    const blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `agrisentry-health-logs-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    toast(`Exported ${rows.length} log${rows.length === 1 ? "" : "s"} to CSV.`, "success");
}

/* ============ ALERTS ============ */
function renderAlerts() {
    const dashboardAlerts = document.getElementById("dashboard-alerts");
    if (!dashboardAlerts) return;

    if (alerts.length === 0) {
        dashboardAlerts.innerHTML = `<div class="empty">No active health alerts. The herd looks stable.</div>`;
        return;
    }

    dashboardAlerts.innerHTML = alerts.map(alert => {
        const accent = advisoryAccentColor(alert.alert_type);
        return `
        <div class="chat-message alert" style="margin-bottom:10px;background:${accent.cardBg};border-color:${accent.cardBorder}">
            <div class="chat-top">
                <span class="status-chip" style="background:${accent.chipBg};color:${accent.chipText}">⚠ ${escapeHtml(alert.alert_type || "Alert")}</span>
                <span class="chat-time">${alert.goat ? escapeHtml(alert.goat.name) : "Goat #" + (alert.goat_id ?? "N/A")}</span>
            </div>
            <div class="chat-text alert-text" style="color:${accent.text}">${escapeHtml(alert.message || "No message provided.")}</div>
            ${alert.recommendation ? `
                <div style="margin-top:9px;padding-top:9px;border-top:1px dashed ${accent.border};display:flex;gap:7px;align-items:flex-start">
                    <svg class="icon" style="width:13px;height:13px;color:${accent.icon};margin-top:2px;flex-shrink:0"><use href="#i-message"/></svg>
                    <div class="chat-text" style="font-size:12px"><b>Vet Advisory:</b> ${escapeHtml(alert.recommendation)} <span style="color:var(--text-3);font-size:10.5px">(Guidance only — not a diagnosis)</span></div>
                </div>
            ` : ""}
        </div>
    `;
    }).join("");
}

/* ============ COLLARS ============ */
function renderCollars() {
    const table = document.getElementById("collars-tbody");
    if (!table) return;
    document.getElementById("collars-result-count").textContent = `Showing ${collars.length} of ${collars.length} ${collars.length === 1 ? "collar" : "collars"}`;

    if (collars.length === 0) {
        table.innerHTML = `<tr><td colspan="7" style="padding-left:19px"><div class="empty">No collars registered yet.</div></td></tr>`;
        return;
    }

    table.innerHTML = collars.map(collar => `
        <tr>
            <td><span class="collar-code">${escapeHtml(collar.collar_code)}</span></td>
            <td class="mono" style="font-family:var(--mono);font-size:12px;color:var(--text-2)">${escapeHtml(collar.dev_eui) || "—"}</td>
            <td>${collar.goat ? `<span class="assigned-goat">${escapeHtml(collar.goat.name)} <i>🐐</i></span>` : `<span class="status-chip status-monitoring">Unassigned</span>`}</td>
            <td><span class="collar-battery"><svg class="icon"><use href="#i-battery"/></svg>${liveBatteryStatus(collar.battery_level, collar.last_seen)}</span></td>
            <td><span class="status-chip ${collar.device_status === 'Active' ? 'status-normal' : 'status-urgent'}">${escapeHtml(collar.device_status) || "Unknown"}</span></td>
            <td style="font-size:11.5px;color:var(--text-3)">${collar.last_seen ? new Date(collar.last_seen).toLocaleString(document.documentElement.lang) : "Never"}</td>
            <td><button type="button" class="row-btn collar-delete" aria-label="Delete collar ${escapeHtml(collar.collar_code)}" onclick="deleteCollar(${collar.id}, '${escapeHtml(collar.collar_code).replace(/'/g, "\\'")}')"><svg class="icon"><use href="#i-trash"/></svg></button></td>
        </tr>
    `).join("");
}

async function deleteCollar(id, code) {
    if (!confirm(`Delete collar ${code}? This unassigns it from its goat. This cannot be undone.`)) return;
    try {
        await fetchAPI(`/collars/${id}`, { method: "DELETE" });
        toast(`Collar ${code} deleted.`, "success");
        await loadData();
    } catch (e) {
        toast(e.message || "Could not delete this collar.", "error");
    }
}

/* ============ REPORT ANALYTICS ============ */
const REPORT_PERIOD_LABELS = { day: "today", week: "this week", month: "this month", year: "this year", all: "all available records" };

let reportRange = null;
let reportRequest = 0;
function applyReportDates() {
 const start=document.getElementById("report-start").value,end=document.getElementById("report-end").value;
 if(!start || !end || start>end) {document.getElementById("report-error").textContent="Choose an end date on or after the start date.";return;}
 reportRange={start,end};document.querySelectorAll("#screen-reports .filter-pill").forEach(b=>b.classList.remove("active"));loadReport("all");
}
function resetReportDates(){reportRange=null;document.getElementById("report-dates").reset();document.querySelectorAll("#screen-reports .filter-pill").forEach(b=>b.classList.toggle("active",agrisentryOriginalText(b)==="All"));loadReport("all");}
function filterReportPeriod(event, period) {
    reportRange=null;document.getElementById("report-dates").reset();
    currentReportPeriod = period;
    document.querySelectorAll("#screen-reports .filter-pill").forEach(b => b.classList.remove("active"));
    event.target.classList.add("active");
    loadReport(period);
}

function reportBarRow(label, right, pct, colorClass) {
    return `
        <div class="report-bar-row">
            <div class="report-bar-label"><span>${escapeHtml(label)}</span><span class="mono">${escapeHtml(right)}</span></div>
            <div class="report-bar"><div class="report-bar-fill ${colorClass}" style="width:${Math.max(0, Math.min(100, pct))}%"></div></div>
        </div>
    `;
}

async function downloadDocument(url, filename) {
    try {
        const response=await fetch(url,{headers:{Accept:'application/json'}});
        if(!response.ok){const data=await response.json().catch(()=>({}));throw new Error(data.message||'Download failed.');}
        const blob=await response.blob(),link=document.createElement('a'),objectUrl=URL.createObjectURL(blob);
        link.href=objectUrl;link.download=filename;document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(objectUrl),10000);
    }catch(e){toast(e.message,'error');}
}
function downloadReport(format) {
    const query=new URLSearchParams({period:currentReportPeriod,format});
    if(reportRange){query.set('start_date',reportRange.start);query.set('end_date',reportRange.end);}
    const label=reportRange?reportRange.start+'_'+reportRange.end:currentReportPeriod;
    downloadDocument('/api/reports/export?'+query,'agrisentry-report-'+label+'.'+format);
}
async function loadReport(period) {
    currentReportPeriod = period;
    const request=++reportRequest;
    try {
        document.getElementById("report-error").textContent="";
        document.getElementById("report-period-note").textContent="Loading report…";
        const query=new URLSearchParams({period});
        if(reportRange){query.set("start_date",reportRange.start);query.set("end_date",reportRange.end);}
        const data = await fetchAPI(`/reports?${query}`);
        if(request===reportRequest) renderReport(data);
    } catch (e) {
        if (request!==reportRequest) return;
        document.getElementById("report-period-note").textContent="Report could not be refreshed. Displayed values are from the last successful load.";
        document.getElementById("report-error").textContent=e.message || "Could not load this report. Please try again.";
    }
}

function renderReport(data) {
    ['motion_readings_count','prolonged_inactivity_count','excessive_movement_count','other_motion_count'].forEach(key => document.getElementById('report-'+key).textContent = data[key] ?? 0);
    document.getElementById("report-urgent").textContent = data.urgent_goats;
    document.getElementById("report-monitoring").textContent = data.monitoring_goats;
    document.getElementById("report-normal").textContent = data.normal_goats;
    document.getElementById("report-medical").textContent = data.medical_records_count;
    document.getElementById("report-logs-count").textContent = data.health_logs_count;
    document.getElementById("report-temp-avg").textContent = data.temp_avg !== null ? `${data.temp_avg}°C` : "N/A";
    document.getElementById("report-temp-high").textContent = data.temp_high !== null ? `${data.temp_high}°C` : "N/A";
    document.getElementById("report-temp-low").textContent = data.temp_low !== null ? `${data.temp_low}°C` : "N/A";

    const periodNote = data.period === "custom" ? `Showing records from ${data.start_date} to ${data.end_date}` : data.period === "all" ? "Showing all available records" : `Showing records from ${REPORT_PERIOD_LABELS[data.period] || "the selected period"}`;
    document.getElementById("report-period-note").textContent = periodNote;

    const totalGoats = data.urgent_goats + data.monitoring_goats + data.normal_goats;
    const pct = n => totalGoats > 0 ? Math.round((n / totalGoats) * 100) : 0;

    document.getElementById("report-status-graph").innerHTML =
        reportBarRow("Urgent", `${data.urgent_goats} goat(s) · ${pct(data.urgent_goats)}%`, pct(data.urgent_goats), "red") +
        reportBarRow("Monitoring", `${data.monitoring_goats} goat(s) · ${pct(data.monitoring_goats)}%`, pct(data.monitoring_goats), "amber") +
        reportBarRow("Normal", `${data.normal_goats} goat(s) · ${pct(data.normal_goats)}%`, pct(data.normal_goats), "green");

    const TEMP_SCALE = 45; // reference max °C for bar width
    document.getElementById("report-temp-graph").innerHTML =
        reportBarRow("Average Temperature", data.temp_avg !== null ? `${data.temp_avg}°C` : "N/A", (data.temp_avg / TEMP_SCALE) * 100 || 0, "green") +
        reportBarRow("Highest Temperature", data.temp_high !== null ? `${data.temp_high}°C` : "N/A", (data.temp_high / TEMP_SCALE) * 100 || 0, "red") +
        reportBarRow("Lowest Temperature", data.temp_low !== null ? `${data.temp_low}°C` : "N/A", (data.temp_low / TEMP_SCALE) * 100 || 0, "blue");

    document.getElementById("report-status-graph").innerHTML = `
        <div class="report-donut" style="--urgent:${pct(data.urgent_goats)}%;--monitoring:${pct(data.monitoring_goats)}%"><div><b>${totalGoats}</b><span>Total Goats</span></div></div>
        <div class="report-legend"><span><i class="red"></i>Urgent <b>${data.urgent_goats} (${pct(data.urgent_goats)}%)</b></span><span><i class="amber"></i>Monitoring <b>${data.monitoring_goats} (${pct(data.monitoring_goats)}%)</b></span><span><i class="green"></i>Normal <b>${data.normal_goats} (${pct(data.normal_goats)}%)</b></span></div>`;

    if (!data.health_logs_count) document.getElementById("report-temp-graph").innerHTML = '<div class="empty">No temperature readings for this period.</div>';

    document.getElementById("report-distribution").innerHTML = `<div class="report-quick-stats"><div><span>Total Goats</span><b>${totalGoats}</b><small>With recorded status</small></div><div><span>Health Logs</span><b>${data.health_logs_count}</b><small>Total entries</small></div><div><span>Avg Temperature</span><b class="t-l">${data.temp_avg ?? "N/A"}°C</b><small>Average</small></div><div><span>Highest Temperature</span><b class="t-h">${data.temp_high ?? "N/A"}°C</b><small>Recorded</small></div><div><span>Lowest Temperature</span><b class="t-l">${data.temp_low ?? "N/A"}°C</b><small>Recorded</small></div></div>`;

    const rows = [
        { dot: "var(--red)", title: `Urgent Goats: ${data.urgent_goats}`, desc: "These goats should be checked first because they may have high temperature or motion anomaly signs within the selected report period." },
        { dot: "var(--amber)", title: `Goats Under Monitoring: ${data.monitoring_goats}`, desc: "These goats need observation because their readings are not fully normal within the selected report period." },
        { dot: "var(--green)", title: `Normal Goats: ${data.normal_goats}`, desc: "These goats have no urgent alert based on the available readings in the selected report period." },
        { dot: "var(--blue)", title: `Medical Records: ${data.medical_records_count}`, desc: "This includes vaccine, treatment, medicine, and other medical records saved within the selected report period." },
    ];

    /* The compact metric row above replaces the older narrative distribution list. */
    const distributionNarrative = rows.map(row => `
        <div class="log-entry">
            <div class="log-dot-col"><div class="log-dot" style="background:${row.dot}"></div><div class="log-line"></div></div>
            <div class="log-content">
                <div class="log-time">${periodNote}</div>
                <div class="log-title">${escapeHtml(row.title)}</div>
                <div class="log-desc">${escapeHtml(row.desc)}</div>
            </div>
        </div>
    `).join("");
}

/* ============ MEDICAL RECORDS ============ */
function renderMedicalRecords() {
    const container = document.getElementById("medical-record-list");
    if (medicalRecords.length === 0) {
        container.innerHTML = `<div class="empty medical-empty"><img class="medical-empty-image" src="/images/medical-records-empty.png" alt="Medical clipboard and goat record illustration"><h3>No medical records found yet.</h3><p>Keep track of treatments, prescriptions, and health interventions<br>for your goats in one secure place.</p><button class="btn btn-primary" onclick="openAddMedical()"><svg class="icon"><use href="#i-plus"/></svg> Add Record</button></div>`;
        return;
    }

    container.innerHTML = medicalRecords.map(record => {
        const goatLabel = record.goat ? `${escapeHtml(record.goat.name)} (${escapeHtml(record.goat.code)})` : `Goat #${record.goat_id ?? "N/A"}`;
        return `
            <div class="log-entry">
                <div class="log-dot-col"><div class="log-dot" style="background:var(--blue)"></div><div class="log-line"></div></div>
                <div class="log-content">
                    <div class="log-time">${record.date_given || "No date"} · ${goatLabel}</div>
                    <div class="log-title"><span translate="no">${escapeHtml(record.title)}</span> <span class="tag" style="margin-left:6px">${escapeHtml(record.record_type)}</span></div>
                    ${record.reference_photo_url ? (record.reference_photo_url.toLowerCase().endsWith('.pdf')
                        ? `<a class="btn" href="${record.reference_photo_url}" target="_blank" rel="noopener" style="margin:10px 0">View attached PDF</a>`
                        : `<a href="${record.reference_photo_url}" target="_blank" rel="noopener"><img src="${record.reference_photo_url}" alt="Medical reference" style="width:120px;height:90px;object-fit:cover;border-radius:9px;margin:10px 0"></a>`) : ""}
                    <div class="log-desc">
                        ${record.description ? '<span translate="no">' + escapeHtml(record.description) + "</span><br>" : ""}
                        <b>Administered by:</b> ${escapeHtml(record.administered_by) || "N/A"}
                        ${record.next_due_date ? ` · <b>Next due:</b> ${record.next_due_date}` : ""}
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

/* ============ GOAT PROFILE MODAL ============ */
async function openGoatProfile(id) {
    profileGoatId = id;
    openModal("modal-profile");
    document.getElementById("profile-name").textContent = "Loading…";
    document.getElementById("profile-code").textContent = "";
    document.getElementById("profile-body").innerHTML = `<div class="empty">Loading profile…</div>`;

    try {
        const data = await fetchAPI(`/goats/${id}`);
        const goat = data.goat;

        document.getElementById("profile-name").textContent = goat.name;
        document.getElementById("profile-code").textContent = goat.code;

        const info = [
            ["Status", goat.status || "Unknown"],
            ["Temperature", (goat.temperature ?? "N/A") + "°C"],
            ["Movement", goat.movement || "N/A"],
            ["Battery", (goat.battery ?? "N/A") + (goat.battery ? "%" : "")],
            ["Breed", goat.breed || "N/A"],
            ["Sex", goat.sex || "N/A"],
            ...(goat.pregnancy_status === 'pregnant' ? [["Pregnancy status", "Pregnant"]] : []),
            ["Age", goat.age || "N/A"],
            ["Weight", goat.weight || "N/A"],
            ["Owner", goat.owner || "N/A"],
            ["Ear Tag", goat.ear_tag || "N/A"],
            ["Goat Color", goat.color || "N/A"],
            ["Assigned Collar", goat.collar ? `${goat.collar.collar_code} (${goat.collar.battery_level ?? "?"}%)` : "None assigned"],
            ["Alert Reason", goat.alert_reason || "None"],
        ];

        const healthLogsHtml = (goat.health_logs || []).slice(0, 5).map(log => `
            <div class="log-entry" style="padding:11px 0">
                <div class="log-dot-col"><div class="log-dot" style="background:${band(log.temperature) === "high" ? "var(--red)" : band(log.temperature) === "low" ? "var(--blue)" : "var(--green)"}"></div><div class="log-line"></div></div>
                <div class="log-content">
                    <div class="log-time">${log.created_at ? new Date(log.created_at).toLocaleString(document.documentElement.lang) : ""}</div>
                    <div class="log-title" style="font-size:12.5px">${escapeHtml(log.event_type)}</div>
                    <div class="log-desc">${log.temperature ?? "N/A"}°C · ${escapeHtml(log.motion_anomaly || log.movement) || "N/A"}</div>
                </div>
            </div>
        `).join("") || `<div class="empty">No health logs for this goat yet.</div>`;

        const medicalHtml = (goat.medical_records || []).slice(0, 5).map(rec => `
            <div class="log-entry" style="padding:11px 0">
                <div class="log-dot-col"><div class="log-dot" style="background:var(--blue)"></div><div class="log-line"></div></div>
                <div class="log-content">
                    <div class="log-time">${rec.date_given || "No date"}</div>
                    <div class="log-title" style="font-size:12.5px">${escapeHtml(rec.title)} <span class="tag">${escapeHtml(rec.record_type)}</span></div>
                    <div class="log-desc">${escapeHtml(rec.administered_by) || "N/A"}</div>
                </div>
            </div>
        `).join("") || `<div class="empty">No medical records for this goat yet.</div>`;

        const alertsHtml = (goat.alerts || []).slice(0, 5).map(alert => {
            const accent = advisoryAccentColor(alert.alert_type);
            return `
            <div class="chat-message alert" style="margin-bottom:10px;background:${accent.cardBg};border-color:${accent.cardBorder}">
                <div class="chat-top">
                    <span class="status-chip" style="background:${accent.chipBg};color:${accent.chipText}">⚠ ${escapeHtml(alert.alert_type || "Alert")}</span>
                    <span class="chat-time">${alert.created_at ? new Date(alert.created_at).toLocaleString(document.documentElement.lang) : ""}</span>
                </div>
                <div class="chat-text alert-text" style="color:${accent.text}">${escapeHtml(alert.message || "No message provided.")}</div>
                ${alert.recommendation ? `
                    <div style="margin-top:9px;padding-top:9px;border-top:1px dashed ${accent.border};display:flex;gap:7px;align-items:flex-start">
                        <svg class="icon" style="width:13px;height:13px;color:${accent.icon};margin-top:2px;flex-shrink:0"><use href="#i-message"/></svg>
                        <div class="chat-text" style="font-size:12px"><b>Vet Advisory:</b> ${escapeHtml(alert.recommendation)} <span style="color:var(--text-3);font-size:10.5px">(Guidance only — not a diagnosis)</span></div>
                    </div>
                ` : ""}
            </div>
        `;
        }).join("") || `<div class="empty">No alerts recorded for this goat yet.</div>`;

        document.getElementById("profile-body").innerHTML = `
            <div class="profile-info-grid">
                ${info.map(([label, val]) => `<div class="info-cell"><div class="info-cell-label">${label}</div><div class="info-cell-val">${escapeHtml(val)}</div></div>`).join("")}
            </div>
            <div class="profile-section-title">Recent Alerts</div>
            <div>${alertsHtml}</div>
            <div class="profile-section-title">Recent Health Logs</div>
            <div>${healthLogsHtml}</div>
            <div class="profile-section-title">Recent Medical Records</div>
            <div>${medicalHtml}</div>
        `;
    } catch (e) {
        document.getElementById("profile-body").innerHTML = `<div class="empty">Could not load this goat's profile.</div>`;
    }
}

document.getElementById("profile-add-medical-btn").addEventListener("click", () => {
    closeModal("modal-profile");
    openAddMedical(profileGoatId);
});
document.getElementById("profile-view-full-btn").addEventListener("click", () => {
    if (profileGoatId) window.location.assign(goatProfileUrl(profileGoatId));
});
document.getElementById("profile-delete-btn").addEventListener("click", async () => {
    if (!profileGoatId) return;
    const name = document.getElementById("profile-name").textContent || "this goat";
    if (!confirm(`Delete ${name}? This also removes their health logs, medical records, and alerts. This cannot be undone.`)) return;
    try {
        await fetchAPI(`/goats/${profileGoatId}`, { method: "DELETE" });
        toast(`${name} deleted.`, "success");
        closeModal("modal-profile");
        await loadData();
    } catch (e) {
        toast(e.message || "Could not delete this goat.", "error");
    }
});

/* ============ ADD GOAT ============ */
function openAddGoat() { openModal("modal-add-goat"); }

async function submitAddGoat(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById("btn-submit-goat");
    const payload = Object.fromEntries(new FormData(form).entries());

    Object.keys(payload).forEach(k => { if (payload[k] === "") delete payload[k]; });
    if (payload.temperature) payload.temperature = parseFloat(payload.temperature);

    btn.disabled = true;
    try {
        await fetchAPI("/goats", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify(payload),
        });
        toast(`${payload.name} was added to the herd.`, "success");
        closeModal("modal-add-goat");
        loadData();
    } catch (e) {
        const msg = e.data?.errors ? Object.values(e.data.errors).flat().join(" ") : e.message;
        toast(msg || "Could not add goat.", "error");
    } finally {
        btn.disabled = false;
    }
    return false;
}

/* ============ ADD MEDICAL RECORD ============ */
function populateGoatSelect() {
    const options = `<option value="">— Select goat —</option>` +
        goats.map(g => `<option value="${g.id}">${escapeHtml(g.name)} (${escapeHtml(g.code)})</option>`).join("");

    const select = document.getElementById("medical-goat-select");
    const current = select.value;
    select.innerHTML = options;
    if (current) select.value = current;

    const collarSelect = document.getElementById("collar-goat-select");
    if (collarSelect) {
        const collarCurrent = collarSelect.value;
        collarSelect.innerHTML = `<option value="">— Unassigned —</option>` +
            goats.map(g => `<option value="${g.id}">${escapeHtml(g.name)} (${escapeHtml(g.code)})</option>`).join("");
        if (collarCurrent) collarSelect.value = collarCurrent;
    }
}

/* ============ ADD COLLAR ============ */
function openAddCollar() { openModal("modal-add-collar"); }

async function submitAddCollar(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById("btn-submit-collar");
    const payload = new FormData(form);
    if (payload.battery_level) payload.battery_level = parseInt(payload.battery_level, 10);

    btn.disabled = true;
    try {
        const response = await fetchAPI("/collars", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify(payload),
        });
        toast(`Collar ${payload.collar_code} added.`, "success");
        closeModal("modal-add-collar");
        await loadData();
        showCollarAdded(response.collar);
    } catch (e) {
        const msg = e.data?.errors ? Object.values(e.data.errors).flat().join(" ") : e.message;
        toast(msg || "Could not add collar.", "error");
    } finally {
        btn.disabled = false;
    }
    return false;
}

function showCollarAdded(collar) {
    const info = [
        ["Collar Code", collar.collar_code],
        ["Dev EUI", collar.dev_eui || "N/A"],
        ["Battery", (collar.battery_level ?? "N/A") + (collar.battery_level != null ? "%" : "")],
        ["Assigned Goat", collar.goat ? `${collar.goat.name} (${collar.goat.code})` : "Unassigned"],
    ];

    document.getElementById("collar-added-body").innerHTML = `
        <div class="profile-info-grid">
            ${info.map(([label, val]) => `<div class="info-cell"><div class="info-cell-label">${label}</div><div class="info-cell-val">${escapeHtml(val)}</div></div>`).join("")}
        </div>
        ${collar.goat ? `<div class="qr-canvas" id="collar-added-qr" style="margin-top:16px"></div>` : `<div class="empty">Assign this collar to a goat to also print its QR code.</div>`}
    `;

    openModal("modal-collar-added");

    if (collar.goat && window.QRCode) {
        new QRCode(document.getElementById("collar-added-qr"), {
            text: goatProfileUrl(collar.goat.id),
            width: 140,
            height: 140,
            colorDark: "#0b1220",
            colorLight: "#ffffff",
        });
    }
}

function openAddMedical(preselectGoatId = null) {
    openModal("modal-add-medical");
    if (preselectGoatId) document.getElementById("medical-goat-select").value = preselectGoatId;
}

async function submitAddMedical(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById("btn-submit-medical");
    const attachment = form.elements.reference_photo?.files?.[0];
    if (attachment && attachment.size > 20 * 1024 * 1024) {
        toast("The attachment must be 20 MB or smaller.", "error");
        return false;
    }
    const payload = new FormData(form);
    for (const [key, value] of payload.entries()) {
        if (value === "" || (value instanceof File && value.size === 0)) payload.delete(key);
    }

    const goatId = payload.get("goat_id");
    if (!goatId) { toast("Please select a goat.", "error"); return false; }
    payload.delete("goat_id");

    btn.disabled = true;
    try {
        await fetchAPI(`/goats/${goatId}/medical-records`, {
            method: "POST",
            headers: { "Accept": "application/json" },
            body: payload,
        });
        toast("Medical record saved.", "success");
        closeModal("modal-add-medical");
        loadData();
    } catch (e) {
        const msg = e.data?.errors ? Object.values(e.data.errors).flat().join(" ") : e.message;
        toast(msg || "Could not save medical record.", "error");
    } finally {
        btn.disabled = false;
    }
    return false;
}

/* ============ AI CHAT ============ */
function usePrompt(question) {
    document.getElementById("ai-question").value = dashboardText(question);
    document.getElementById("ai-question").focus();
}

function addChatMessage(type, text, label = "Gemini") {
    const chatBody = document.getElementById("chat-body");
    const messageClass = type === "user" ? "user" : "ai";
    const time = new Date().toLocaleTimeString(document.documentElement.lang, { hour: "2-digit", minute: "2-digit" });
    const chipClass = type === "user" ? "status-monitoring" : "status-normal";

    const el = document.createElement("div");
    el.className = `chat-message ${messageClass}`;
    el.innerHTML = `
        <div class="chat-top"><span class="status-chip ${chipClass}">${escapeHtml(label)}</span><span class="chat-time">${time}</span></div>
        <div class="chat-text">${formatAdviceText(text)}</div>
    `;
    chatBody.appendChild(el);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function addLoadingMessage() {
    const chatBody = document.getElementById("chat-body");
    const el = document.createElement("div");
    el.className = "chat-message ai";
    el.id = "loading-message";
    el.innerHTML = `
        <div class="chat-top"><span class="status-chip status-normal">Gemini</span><span class="chat-time">Thinking</span></div>
        <div class="loading-text">Generating advice <span class="dot-flash"><span></span><span></span><span></span></span></div>
    `;
    chatBody.appendChild(el);
    chatBody.scrollTop = chatBody.scrollHeight;
}
function removeLoadingMessage() { document.getElementById("loading-message")?.remove(); }

function formatAdviceText(text) {
    return escapeHtml(text).replace(/\n/g, "<br>").replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
}

let geminiPhoto = null;
let geminiPhotoUrl = null;
let geminiSending = false;
function removeGeminiPhoto() {
    geminiPhoto = null;
    if (geminiPhotoUrl) URL.revokeObjectURL(geminiPhotoUrl);
    geminiPhotoUrl = null;
    document.getElementById('ai-photo').value = '';
    document.getElementById('ai-photo-preview').hidden = true;
    document.getElementById('ai-photo-thumbnail').removeAttribute('src');
    document.getElementById('ai-photo-name').textContent = '';
    document.getElementById('ai-question').placeholder = 'Ask about your herd, symptoms, treatments…';
}
function selectGeminiPhoto(picker) {
    const file = picker.files[0];
    if (!file || geminiSending) return;
    attachGeminiPhoto(file);
    picker.value = '';
}
function attachGeminiPhoto(file) {
    if (geminiSending) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        toast('Choose a JPG, PNG, or WebP photo up to 2 MB.', 'error');
        return;
    }
    if (geminiPhotoUrl) URL.revokeObjectURL(geminiPhotoUrl);
    geminiPhoto = file;
    geminiPhotoUrl = URL.createObjectURL(file);
    document.getElementById('ai-photo-thumbnail').src = geminiPhotoUrl;
    document.getElementById('ai-photo-name').textContent = file.name;
    document.getElementById('ai-photo-preview').hidden = false;
    const input = document.getElementById('ai-question');
    input.placeholder = 'Ask about this photo (optional)…';
    input.focus();
}
function pasteGeminiPhoto(event) {
    const clipboard = event.clipboardData;
    if (!clipboard) return;
    const item = Array.from(clipboard.items || []).find(item => item.kind === 'file' && item.type.startsWith('image/'));
    const file = item?.getAsFile() || Array.from(clipboard.files || []).find(file => file.type.startsWith('image/'));
    if (!file) return; // Let ordinary text paste normally.
    event.preventDefault();
    if (geminiSending) {
        toast('Please wait for the current reply before attaching another photo.', 'error');
        return;
    }
    attachGeminiPhoto(file);
}
document.getElementById('ai-question').closest('.chat-input-row').addEventListener('paste', pasteGeminiPhoto);
async function sendGeminiQuestion() {
    if (geminiSending) return;
    const input = document.getElementById("ai-question");
    const question = input.value.trim();
    if (!question && !geminiPhoto) { toast("Type a question or attach a photo first.", "error"); return; }
    const photo = geminiPhoto;
    const body = new FormData();
    body.append('language', agrisentryLanguage());
    if (question) body.append('question', question);
    if (photo) body.append('photo', photo);
    geminiSending = true;
    ['send-ai-message', 'attach-picture', 'ai-photo', 'remove-ai-photo'].forEach(id => { document.getElementById(id).disabled = true; });

    addChatMessage("user", question || 'Photo for AI Vet Advice', "Caretaker");
    if (photo) {
        const image = document.createElement('img');
        const url = URL.createObjectURL(photo);
        image.alt = 'Photo attached for AI Vet Advice';
        image.style.cssText = 'display:block;max-width:220px;max-height:180px;border-radius:8px;margin-top:8px';
        image.onload = image.onerror = () => URL.revokeObjectURL(url);
        image.src = url;
        document.getElementById('chat-body').lastElementChild.appendChild(image);
    }
    input.value = "";
    addLoadingMessage();

    try {
        const response = await fetch(`${API_BASE}/gemini-advice`, {
            method: "POST",
            headers: { "Accept": "application/json" },
            body,
        });

        const data = await response.json().catch(() => ({}));
        removeLoadingMessage();

        if (!response.ok) {
            addChatMessage("ai", [data.message, data.advice].filter(Boolean).join("\n\n") || "Gemini is temporarily unavailable. Please inspect the goat manually and contact a veterinarian if the signs are serious.", "System");
            if (!input.value) input.value = question;
            return;
        }

        removeGeminiPhoto();
        addChatMessage("ai", data.advice || "No advice returned.", "Gemini");
    } catch (error) {
        removeLoadingMessage();
        addChatMessage("ai", "Connection error. Please make sure your Laravel server is running.", "System");
        if (!input.value) input.value = question;
    } finally {
        geminiSending = false;
        ['send-ai-message', 'attach-picture', 'ai-photo', 'remove-ai-photo'].forEach(id => { document.getElementById(id).disabled = false; });
    }
}

/* ============ INIT ============ */
for (const [screen,feature] of Object.entries({dashboard:'dashboard',animals:'goats',logs:'health-logs',medical:'medical-records',collars:'collars',reports:'reports',ai:'gemini-advice'})) {
    if (!FEATURE_PERMISSIONS[feature+'.read']) {
        document.getElementById('nav-'+screen)?.setAttribute('hidden','');
        document.getElementById('screen-'+screen)?.classList.remove('active');
    }
}
for (const [fn,feature] of Object.entries({openAddGoat:'goats',openAddMedical:'medical-records',openAddCollar:'collars'})) {
    if (!FEATURE_PERMISSIONS[feature+'.write']) document.querySelectorAll('[onclick^="'+fn+'"]').forEach(el=>el.hidden=true);
}
if (!FEATURE_PERMISSIONS['dashboard.read']) {
    const first = Object.entries({animals:'goats',logs:'health-logs',medical:'medical-records',collars:'collars',reports:'reports',ai:'gemini-advice'}).find(([screen,feature])=>FEATURE_PERMISSIONS[feature+'.read']);
    if (first) goScreen(first[0]);
}
loadData();
const requestedScreen = new URLSearchParams(location.search).get('screen');
if (['dashboard','animals','logs','medical','collars','reports','ai'].includes(requestedScreen)) goScreen(requestedScreen);
let liveRefreshTimer;
window.addEventListener('agrisentry:telemetry', () => {
    if (document.hidden || liveRefreshTimer) return;
    liveRefreshTimer = setTimeout(() => { liveRefreshTimer = null; loadData(true); }, 5000);
});
setInterval(() => { if (!document.hidden) loadData(true); }, 30000);
</script>

@include('partials.firebase-live')

</body>
</html>
