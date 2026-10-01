/* ============ RESET & TOKENS ============ */
* { box-sizing: border-box; margin: 0; padding: 0; }

.sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}

:root {
    --bg-app: #eef1f5;
    --bg-sidebar: #ffffff;
    --bg-card: #ffffff;
    --bg-raised: #f6f8fa;
    --bg-hover: #f0f3f7;
    --border: #e2e6ec;
    --border-soft: #edf0f4;
    --text-0: #0b1220;
    --text-1: #1c2433;
    --text-2: #4b5567;
    --text-3: #475569;

    --green: #16a34a;
    --green-dk: #15803d;
    --green-bg: #e9f9ef;
    --green-bg-strong: #dcfce7;

    --red: #dc2626;
    --red-dk: #b91c1c;
    --red-bg: #fdeceb;
    --red-bg-strong: #fee2e2;

    --amber: #92400e;
    --amber-bg: #fef3c7;

    --blue: #2563eb;
    --blue-bg: #eaf1ff;
    --blue-bg-strong: #dbeafe;

    --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
    --shadow-md: 0 4px 16px rgba(15, 23, 42, 0.06);
    --shadow-lg: 0 16px 48px rgba(15, 23, 42, 0.16);

    --mono: 'DM Mono', monospace;
    --sans: 'DM Sans', sans-serif;
    --radius: 14px;
    --radius-sm: 9px;
}

:root[data-theme="dark"] {
    --bg-app: #0b0f16;
    --bg-sidebar: #10151f;
    --bg-card: #131924;
    --bg-raised: #171e2b;
    --bg-hover: #1b2331;
    --border: #232c3d;
    --border-soft: #1c2432;
    --text-0: #f4f6fa;
    --text-1: #dde3ee;
    --text-2: #9aa5b8;
    --text-3: #94a3b8;

    --green: #22c55e;
    --green-dk: #4ade80;
    --green-bg: rgba(34,197,94,0.12);
    --green-bg-strong: rgba(34,197,94,0.18);

    --red: #f87171;
    --red-dk: #fca5a5;
    --red-bg: rgba(248,113,113,0.12);
    --red-bg-strong: rgba(248,113,113,0.18);

    --amber: #fbbf24;
    --amber-bg: rgba(251,191,36,0.14);

    --blue: #60a5fa;
    --blue-bg: rgba(96,165,250,0.12);
    --blue-bg-strong: rgba(96,165,250,0.18);

    --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.35);
    --shadow-lg: 0 20px 60px rgba(0,0,0,0.55);
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg-app: #0b0f16;
        --bg-sidebar: #10151f;
        --bg-card: #131924;
        --bg-raised: #171e2b;
        --bg-hover: #1b2331;
        --border: #232c3d;
        --border-soft: #1c2432;
        --text-0: #f4f6fa;
        --text-1: #dde3ee;
        --text-2: #9aa5b8;
        --text-3: #94a3b8;
        --green: #22c55e;
        --green-dk: #4ade80;
        --green-bg: rgba(34,197,94,0.12);
        --green-bg-strong: rgba(34,197,94,0.18);
        --red: #f87171;
        --red-dk: #fca5a5;
        --red-bg: rgba(248,113,113,0.12);
        --red-bg-strong: rgba(248,113,113,0.18);
        --amber: #fbbf24;
        --amber-bg: rgba(251,191,36,0.14);
        --blue: #60a5fa;
        --blue-bg: rgba(96,165,250,0.12);
        --blue-bg-strong: rgba(96,165,250,0.18);
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
        --shadow-md: 0 4px 20px rgba(0,0,0,0.35);
        --shadow-lg: 0 20px 60px rgba(0,0,0,0.55);
    }
}

html, body { height: 100%; }

body {
    font-family: var(--sans);
    background: var(--bg-app);
    color: var(--text-1);
    -webkit-font-smoothing: antialiased;
}

.icon {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
}

/* ============ APP SHELL ============ */
.app {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 234px;
    flex-shrink: 0;
    background: var(--bg-sidebar);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 40;
    transition: transform .25s ease;
}

.brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 20px 18px;
    border-bottom: 1px solid var(--border-soft);
    cursor: pointer;
}

.brand-mark {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    background: linear-gradient(135deg, #22c55e, #0d6b32);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(22,163,74,0.28);
    flex-shrink: 0;
}

.brand-mark .icon { width: 19px; height: 19px; stroke: #fff; }

.brand-mark.has-logo { background: transparent; box-shadow: none; }
.brand-mark-img { width: 100%; height: 100%; object-fit: contain; }

.brand-name { font-size: 15.5px; font-weight: 800; color: var(--text-0); letter-spacing: -.2px; }
.brand-sub { font-size: 10px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; }

.nav-scroll { flex: 1; overflow-y: auto; padding: 14px 0 10px; }

.nav-section {
    font-size: 10px;
    font-weight: 700;
    color: var(--text-3);
    letter-spacing: 1px;
    padding: 14px 18px 7px;
    text-transform: uppercase;
    font-family: var(--mono);
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 18px;
    margin: 1px 8px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text-2);
    cursor: pointer;
    transition: background .15s, color .15s;
    user-select: none;
}

.nav-item:hover { color: var(--text-0); background: var(--bg-hover); }

.nav-item.active {
    color: var(--green-dk);
    background: var(--green-bg);
    font-weight: 600;
}

.nav-item .icon { color: inherit; }

.nav-badge {
    margin-left: auto;
    background: var(--red-bg-strong);
    color: var(--red-dk);
    font-size: 10px;
    padding: 1px 7px;
    border-radius: 20px;
    font-family: var(--mono);
    font-weight: 700;
    min-width: 16px;
    text-align: center;
}

.nav-badge.zero { display: none; }

.sidebar-foot {
    padding: 12px;
    border-top: 1px solid var(--border-soft);
}

.led-legend {
    display: flex;
    justify-content: space-between;
    padding: 10px 10px;
    border-radius: 10px;
    background: var(--bg-raised);
    font-family: var(--mono);
    font-size: 10.5px;
    color: var(--text-3);
}

.led-legend span { display: flex; align-items: center; gap: 5px; }

/* MAIN COLUMN */
.main-col {
    margin-left: 234px;
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.topbar {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 26px;
    background: color-mix(in srgb, var(--bg-app) 82%, transparent);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
}

.burger {
    display: none;
    width: 34px; height: 34px;
    align-items: center; justify-content: center;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    cursor: pointer;
}

.topbar-title { font-size: 14px; font-weight: 700; color: var(--text-0); }
.topbar-spacer { flex: 1; }

.status-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 6px 13px;
    font-size: 11px;
    color: var(--green-dk);
    font-family: var(--mono);
    font-weight: 500;
}

.status-dot {
    width: 7px; height: 7px;
    background: var(--green);
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

.status-pill.down { color: var(--red); }
.status-pill.down .status-dot { background: var(--red); animation: none; }

@keyframes pulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .45; transform: scale(.75); } }

.icon-btn {
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-2);
    cursor: pointer;
    transition: all .15s;
}
.icon-btn:hover { color: var(--text-0); background: var(--bg-hover); }

.avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--green), var(--green-dk));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff;
    flex-shrink: 0;
}

.main {
    flex: 1;
    padding: 26px 26px 60px;
    max-width: 1360px;
    width: 100%;
    margin: 0 auto;
}

.screen { display: none; animation: fadein .25s ease; }
.screen.active { display: block; }
@keyframes fadein { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

/* PAGE HEADER */
.page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
}
.page-title { font-size: 25px; font-weight: 800; color: var(--text-0); letter-spacing: -.4px; }
.page-sub { font-size: 12.5px; color: var(--text-3); margin-top: 5px; }
.page-sub .mono { font-family: var(--mono); }

.btn-row { display: flex; gap: 9px; flex-wrap: wrap; }

.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 16px; font-size: 13px; font-weight: 600;
    border-radius: 10px; border: 1px solid var(--border);
    background: var(--bg-card); color: var(--text-1);
    cursor: pointer; transition: all .15s;
}
.btn:hover { background: var(--bg-hover); border-color: var(--text-3); }
.btn:active { transform: translateY(1px); }
.btn-primary { background: var(--green); color: #fff; border-color: var(--green); }
.btn-primary:hover { background: var(--green-dk); border-color: var(--green-dk); }
.btn-danger { color: var(--red); border-color: var(--red-bg-strong); }
.btn-danger:hover { background: var(--red-bg); }
.btn-sm { padding: 7px 13px; font-size: 12.5px; }
.btn:disabled { opacity: .55; cursor: not-allowed; }
.btn .icon { width: 14px; height: 14px; }

/* ERROR BANNER */
.error-box {
    display: none;
    align-items: center; gap: 9px;
    padding: 12px 15px; background: var(--red-bg); color: var(--red);
    border: 1px solid var(--red-bg-strong);
    border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;
}

/* STATS */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }

.stat {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 18px 19px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden;
}
.stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.stat-label { font-size: 10.5px; color: var(--text-3); text-transform: uppercase; letter-spacing: .7px; font-family: var(--mono); font-weight: 600; }
.stat-icon { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; }
.stat-icon .icon { width: 15px; height: 15px; }
.stat-val { font-size: 30px; font-weight: 800; color: var(--text-0); letter-spacing: -.5px; line-height: 1; }
.stat-foot { font-size: 11px; color: var(--text-3); margin-top: 8px; }

.si-slate { background: var(--bg-raised); color: var(--text-2); }
.si-green { background: var(--green-bg); color: var(--green-dk); }
.si-amber { background: var(--amber-bg); color: var(--amber); }
.si-red { background: var(--red-bg-strong); color: var(--red); }

/* GRID LAYOUT (dashboard 2-col) */
.dash-grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 16px; align-items: start; }

/* PANEL */
.panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 16px; box-shadow: var(--shadow-sm); }
.panel-head { display: flex; align-items: center; justify-content: space-between; padding: 15px 19px; border-bottom: 1px solid var(--border-soft); gap: 12px; }
.panel-title { font-size: 13.5px; font-weight: 700; color: var(--text-0); display: flex; align-items: center; gap: 8px; }
.panel-title .icon { color: var(--green); width: 15px; height: 15px; }
.panel-body { padding: 18px 19px; }
.panel-body.tight { padding: 8px 0; }

/* TABLE */
.table-wrap { overflow-x: auto; }
.tbl { width: 100%; border-collapse: collapse; min-width: 640px; }
.tbl th {
    font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .6px;
    font-family: var(--mono); padding: 0 12px 11px 0; text-align: left; font-weight: 700;
}
.tbl td { padding: 12px 12px 12px 0; font-size: 13px; color: var(--text-1); border-top: 1px solid var(--border-soft); vertical-align: middle; }
.tbl tr.clickable { cursor: pointer; }
.tbl tr.clickable:hover td { background: var(--bg-hover); }

.tag { font-family: var(--mono); font-size: 11px; color: var(--green-dk); background: var(--green-bg-strong); padding: 3px 8px; border-radius: 6px; font-weight: 700; white-space: nowrap; }

.led { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.led-g { background: var(--green); box-shadow: 0 0 8px rgba(22,163,74,0.35); }
.led-r { background: var(--red); box-shadow: 0 0 8px rgba(220,38,38,0.35); }
.led-b { background: var(--blue); box-shadow: 0 0 8px rgba(37,99,235,0.35); }

.temp { font-family: var(--mono); font-size: 13px; font-weight: 500; }
.t-n { color: var(--green-dk); }
.t-h { color: var(--red); }
.t-l { color: var(--blue); }

.status-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; padding: 3px 9px 3px 7px; border-radius: 20px; font-family: var(--mono); font-weight: 700; white-space: nowrap; }
.status-normal { background: var(--green-bg-strong); color: var(--green-dk); }
.status-urgent { background: var(--red-bg-strong); color: var(--red-dk); }
.status-monitoring { background: var(--amber-bg); color: var(--amber); }

.batt-wrap { display: flex; align-items: center; gap: 6px; }
.batt-bar { width: 34px; height: 8px; border-radius: 3px; background: var(--bg-raised); border: 1px solid var(--border); overflow: hidden; }
.batt-fill { height: 100%; background: var(--green); }
.batt-fill.low { background: var(--red); }
.batt-fill.mid { background: var(--amber); }
.batt-txt { font-family: var(--mono); font-size: 10.5px; color: var(--text-3); }

.row-btn {
    background: none; border: 1px solid var(--border); color: var(--text-2);
    font-size: 11px; padding: 5px 11px; border-radius: 7px; cursor: pointer;
    font-family: var(--mono); font-weight: 600; display: inline-flex; align-items: center; gap: 5px;
}
.row-btn:hover { color: var(--green-dk); border-color: var(--green); background: var(--green-bg); }

/* FILTERS / SEARCH */
.toolbar { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
.filter-row { display: flex; gap: 8px; flex-wrap: wrap; }

.filter-pill {
    font-size: 11.5px; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border);
    background: var(--bg-card); color: var(--text-2); cursor: pointer; font-family: var(--mono); font-weight: 600;
    transition: all .15s;
}
.filter-pill:hover { border-color: var(--green); color: var(--green-dk); }
.filter-pill.active { background: var(--green); border-color: var(--green); color: #fff; }

.search-box { position: relative; flex: 1; min-width: 180px; max-width: 320px; }
.search-box .icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3); width: 14px; height: 14px; }
.search-input {
    width: 100%; padding: 8px 12px 8px 32px; font-size: 12.5px; border-radius: 20px;
    border: 1px solid var(--border); background: var(--bg-card); color: var(--text-1); outline: none; font-family: var(--sans);
}
.search-input:focus { border-color: var(--green); }

/* LOG TIMELINE */
.log-timeline { display: flex; flex-direction: column; }
.log-entry { display: flex; gap: 14px; padding: 15px 19px; border-bottom: 1px solid var(--border-soft); }
.log-entry:last-child { border-bottom: none; }
.log-dot-col { display: flex; flex-direction: column; align-items: center; }
.log-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.log-line { width: 1.5px; background: var(--border); flex: 1; margin: 5px 0; }
.log-content { flex: 1; min-width: 0; }
.log-time { font-size: 10.5px; color: var(--text-3); font-family: var(--mono); margin-bottom: 3px; }
.log-title { font-size: 13px; font-weight: 700; color: var(--text-0); margin-bottom: 4px; }
.log-desc { font-size: 12.5px; color: var(--text-2); line-height: 1.55; }
.log-desc b { color: var(--text-1); font-weight: 600; }

.empty { padding: 30px 16px; color: var(--text-3); font-size: 13px; text-align: center; }
.empty .icon { width: 26px; height: 26px; margin: 0 auto 10px; display: block; color: var(--text-3); opacity: .6; }

/* AI CHAT */
.ai-layout { display: grid; grid-template-columns: 1.8fr 1fr; gap: 16px; }
.chat-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); }
.chat-body { padding: 18px; max-height: 540px; min-height: 300px; overflow-y: auto; flex: 1; }
.chat-message { border: 1px solid var(--border-soft); border-radius: 12px; padding: 13px 15px; margin-bottom: 11px; background: var(--bg-raised); }
.chat-message.user { background: var(--green-bg); margin-left: 15%; border-color: var(--green-bg-strong); }
.chat-message.ai { background: var(--bg-card); }
.chat-message.alert { border-color: var(--red-bg-strong); background: var(--red-bg); }
.chat-top { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
.chat-time { margin-left: auto; font-size: 10px; color: var(--text-3); font-family: var(--mono); }
.chat-text { font-size: 13px; line-height: 1.65; color: var(--text-1); }
.chat-text.alert-text { color: var(--red); }

.chat-input-row { display: flex; gap: 8px; padding: 14px 16px; border-top: 1px solid var(--border-soft); background: var(--bg-card); }
.chat-input { flex: 1; border: 1px solid var(--border); border-radius: 10px; padding: 10px 13px; font-size: 13px; outline: none; font-family: var(--sans); background: var(--bg-raised); color: var(--text-1); }
.chat-input:focus { border-color: var(--green); }

.side-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 16px; box-shadow: var(--shadow-sm); }
.side-card-head { padding: 14px 17px; border-bottom: 1px solid var(--border-soft); font-size: 13.5px; font-weight: 700; color: var(--text-0); }
.side-card-body { padding: 14px 17px; }

.prompt-btn {
    width: 100%; text-align: left; padding: 11px 13px; background: var(--bg-raised);
    border: 1px solid var(--border); border-radius: 10px; margin-bottom: 8px; cursor: pointer;
    font-size: 12.5px; font-weight: 600; color: var(--text-1); transition: all .15s;
}
.prompt-btn:hover { border-color: var(--green); color: var(--green-dk); background: var(--green-bg); }

.led-reference { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 9px; }
.led-reference.green-ref { background: var(--green-bg); }
.led-reference.red-ref { background: var(--red-bg); border-color: var(--red-bg-strong); }
.led-reference.blue-ref { background: var(--blue-bg); border-color: var(--blue-bg-strong); }
.led-title { font-size: 12.5px; font-weight: 700; font-family: var(--mono); }
.led-desc { font-size: 11.5px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }

.loading-text { font-size: 12px; color: var(--text-3); font-family: var(--mono); padding: 4px 0; display: flex; align-items: center; gap: 8px; }
.dot-flash { display: inline-flex; gap: 3px; }
.dot-flash span { width: 5px; height: 5px; border-radius: 50%; background: var(--text-3); animation: dotflash 1.2s infinite ease-in-out; }
.dot-flash span:nth-child(2) { animation-delay: .2s; }
.dot-flash span:nth-child(3) { animation-delay: .4s; }
@keyframes dotflash { 0%,80%,100% { opacity: .25; } 40% { opacity: 1; } }

/* ============ MODALS ============ */
.modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(8,12,20,0.55);
    backdrop-filter: blur(3px); z-index: 100; align-items: center; justify-content: center; padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px;
    width: 100%; max-width: 520px; max-height: 88vh; display: flex; flex-direction: column;
    box-shadow: var(--shadow-lg); animation: modalin .18s ease;
}
.modal.wide { max-width: 720px; }
@keyframes modalin { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

.modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--border-soft); }
.modal-title { font-size: 16px; font-weight: 800; color: var(--text-0); }
.modal-sub { font-size: 11.5px; color: var(--text-3); margin-top: 2px; font-family: var(--mono); }
.modal-close { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-raised); color: var(--text-2); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.modal-close:hover { color: var(--text-0); background: var(--bg-hover); }
.modal-body { padding: 20px 22px; overflow-y: auto; }
.modal-foot { display: flex; justify-content: flex-end; gap: 9px; padding: 16px 22px; border-top: 1px solid var(--border-soft); }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field.full { grid-column: 1 / -1; }
.form-field label { font-size: 11.5px; font-weight: 600; color: var(--text-2); font-family: var(--mono); }
.form-field label .req { color: var(--red); }
.form-field input, .form-field select, .form-field textarea {
    padding: 9px 12px; border-radius: 9px; border: 1px solid var(--border); background: var(--bg-raised);
    color: var(--text-1); font-size: 13px; font-family: var(--sans); outline: none; transition: border-color .15s;
}
.form-field textarea { resize: vertical; min-height: 64px; }
.form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color: var(--green); background: var(--bg-card); }
.form-hint { font-size: 10.5px; color: var(--text-3); margin-top: 2px; }

/* GOAT PROFILE MODAL */
.profile-head { display: flex; align-items: center; gap: 14px; }
.profile-avatar { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, var(--green), var(--green-dk)); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: 0 4px 14px rgba(22,163,74,0.25); }
.profile-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 18px 0; }
.info-cell { background: var(--bg-raised); border: 1px solid var(--border-soft); border-radius: 10px; padding: 10px 12px; }
.info-cell-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; font-family: var(--mono); letter-spacing: .5px; margin-bottom: 4px; }
.info-cell-val { font-size: 13.5px; font-weight: 600; color: var(--text-0); }
.profile-section-title { font-size: 11.5px; font-weight: 700; color: var(--text-2); text-transform: uppercase; letter-spacing: .6px; font-family: var(--mono); margin: 20px 0 10px; }

/* TOASTS */
.toast-stack { position: fixed; top: 18px; right: 18px; z-index: 200; display: flex; flex-direction: column; gap: 10px; max-width: 340px; }
.toast {
    display: flex; align-items: flex-start; gap: 10px; padding: 13px 15px; border-radius: 11px;
    background: var(--bg-card); border: 1px solid var(--border); box-shadow: var(--shadow-lg);
    font-size: 12.5px; color: var(--text-1); animation: toastin .2s ease;
}
@keyframes toastin { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
.toast.success { border-color: var(--green-bg-strong); }
.toast.error { border-color: var(--red-bg-strong); }
.toast-icon { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
.toast.success .toast-icon { color: var(--green); }
.toast.error .toast-icon { color: var(--red); }
.toast.info .toast-icon { color: var(--blue); }

/* SKELETONS */
.skel-row td { padding: 12px; }
.skel { background: linear-gradient(90deg, var(--bg-raised) 25%, var(--bg-hover) 50%, var(--bg-raised) 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 6px; height: 13px; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* SCROLLBAR */
::-webkit-scrollbar { width: 9px; height: 9px; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 20px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-3); }

/* ============ FARM BACKDROP (shared by landing + auth) ============ */
.farm-backdrop { position: absolute; inset: 0; z-index: 0; overflow: hidden; }
.farm-illustration { width: 100%; height: 100%; display: block; }
.farm-overlay {
    position: absolute; inset: 0; z-index: 1;
    background: linear-gradient(180deg, rgba(238,241,245,.5) 0%, rgba(238,241,245,.85) 60%, var(--bg-app) 100%);
}
:root[data-theme="dark"] .farm-overlay,
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .farm-overlay { background: linear-gradient(180deg, rgba(11,15,22,.5) 0%, rgba(11,15,22,.85) 60%, var(--bg-app) 100%); } }

/* ============ TRUST BAR ============ */
.trust-bar {
    position: relative; z-index: 2; width: 100%; margin-top: auto;
    background: var(--bg-card); border-top: 1px solid var(--border);
    padding: 14px 28px; display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.trust-bar-brand { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: var(--text-2); }
.trust-bar-brand strong { color: var(--text-0); }
.trust-items { display: flex; gap: 22px; flex-wrap: wrap; }
.trust-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-2); font-family: var(--mono); }
.trust-item .icon { color: var(--green); }

/* ============ LANDING (species / role select) ============ */
.landing-shell {
    position: relative; min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 48px 20px 0; overflow: hidden;
    background: var(--bg-app);
}
.landing-inner { position: relative; z-index: 2; width: 100%; max-width: 920px; text-align: center; margin-bottom: 44px; }
.landing-brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.landing-brand .brand-mark { width: 44px; height: 44px; }
.landing-brand .brand-mark .icon { width: 23px; height: 23px; }
.landing-title { font-size: 26px; font-weight: 800; color: var(--text-0); letter-spacing: -.4px; }
.landing-sub { font-size: 13px; color: var(--text-3); margin: 8px 0 34px; font-family: var(--mono); }
.landing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.landing-card {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 34px 20px; box-shadow: var(--shadow-md); text-decoration: none; color: inherit;
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    transition: all .18s; cursor: pointer; position: relative; overflow: hidden;
}
.landing-card:hover:not(.disabled) { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--green); }
.landing-card.disabled { opacity: .6; cursor: not-allowed; }
.landing-card-icon {
    width: 76px; height: 76px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 36px; line-height: 1; background: var(--green-bg); margin-bottom: 4px;
}
.landing-card.disabled .landing-card-icon { background: var(--bg-raised); }
.landing-card-name { font-size: 15px; font-weight: 700; color: var(--text-0); }
.landing-card-desc { font-size: 11.5px; color: var(--text-3); font-family: var(--mono); }
.soon-badge {
    position: absolute; top: 10px; right: 10px; font-size: 10.5px; font-weight: 700;
    background: var(--amber-bg); color: var(--amber); padding: 3px 8px; border-radius: 20px;
    font-family: var(--mono); text-transform: uppercase; letter-spacing: .5px;
}

/* ============ AUTH CARD (split screen) ============ */
.auth-shell { min-height: 100vh; display: flex; background: var(--bg-app); }
.auth-visual {
    position: relative; flex: 0 0 44%; display: flex; flex-direction: column;
    justify-content: space-between; padding: 48px 44px; overflow: hidden;
}
.auth-visual-content { position: relative; z-index: 2; }
.auth-visual .brand-mark { margin-bottom: 30px; }
.auth-visual-title { font-size: 30px; font-weight: 800; line-height: 1.28; color: var(--text-0); margin-bottom: 14px; }
.auth-visual-title .accent { color: var(--green-dk); }
.auth-visual-sub { font-size: 13px; color: var(--text-3); max-width: 320px; line-height: 1.6; }
.auth-visual-badges { position: relative; z-index: 2; display: flex; flex-wrap: wrap; gap: 20px 26px; margin-top: 30px; }
.auth-visual-badge { display: flex; align-items: flex-start; gap: 10px; max-width: 200px; }
.auth-visual-badge .icon { color: var(--green); margin-top: 2px; }
.auth-visual-badge-title { font-size: 12.5px; font-weight: 700; color: var(--text-0); }
.auth-visual-badge-desc { font-size: 11px; color: var(--text-3); line-height: 1.4; }
.auth-form-side { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.auth-card {
    width: 100%; max-width: 380px; background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 30px 28px;
}
.auth-head { text-align: center; margin-bottom: 22px; }
.auth-head .brand-mark { margin: 0 auto 14px; }
.auth-title { font-size: 19px; font-weight: 800; color: var(--text-0); }
.auth-sub { font-size: 11.5px; color: var(--text-3); margin-top: 4px; font-family: var(--mono); text-transform: capitalize; }
.auth-form { display: flex; flex-direction: column; gap: 14px; }
.auth-error { background: var(--red-bg); border: 1px solid var(--red-bg-strong); color: var(--red); font-size: 12.5px; padding: 10px 12px; border-radius: var(--radius-sm); }
.auth-back { display: block; text-align: center; margin-top: 16px; font-size: 12px; color: var(--text-3); text-decoration: none; }
.auth-back:hover { color: var(--green-dk); }

@media (max-width: 900px) {
    .auth-visual { display: none; }
    .auth-form-side { padding: 20px; }
}

/* ============ ADMIN USERS ============ */
.phone-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
.phone-row input { flex: 1; }
.phone-remove { width: 30px; height: 30px; flex-shrink: 0; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-raised); color: var(--text-2); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.phone-remove:hover { color: var(--red); border-color: var(--red-bg-strong); }
.role-chip { font-size: 10.5px; padding: 3px 9px; border-radius: 20px; font-family: var(--mono); font-weight: 700; }
.role-admin { background: var(--red-bg-strong); color: var(--red-dk); }
.role-staff { background: var(--blue-bg-strong); color: var(--blue); }
.role-caretaker { background: var(--green-bg-strong); color: var(--green-dk); }

/* ============ GOAT TILES (DASHBOARD) ============ */
.tile-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; padding: 8px 0; }
.goat-tile {
    background: var(--bg-raised); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 15px 16px; cursor: pointer; transition: all .15s; display: flex; flex-direction: column; gap: 11px;
}
.goat-tile:hover { border-color: var(--green); box-shadow: var(--shadow-md); transform: translateY(-2px); }
.goat-tile.urgent { border-color: var(--red-bg-strong); background: var(--red-bg); }
.goat-tile-alert-banner {
    align-self: flex-start; background: var(--red); color: #fff; font-size: 10px; font-weight: 700;
    letter-spacing: .5px; padding: 3px 9px; border-radius: 20px; font-family: var(--mono); text-transform: uppercase;
}
.goat-tile-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.goat-tile-name { font-size: 14.5px; font-weight: 800; color: var(--text-0); }
.goat-tile-sub { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }
.goat-tile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.goat-tile-stat { background: var(--bg-card); border: 1px solid var(--border-soft); border-radius: 9px; padding: 8px 10px; }
.goat-tile-stat-label { font-size: 10.5px; color: var(--text-3); text-transform: uppercase; font-family: var(--mono); letter-spacing: .5px; margin-bottom: 4px; }
.goat-tile-reason { font-size: 11.5px; color: var(--text-2); line-height: 1.5; }
.goat-tile-foot { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-3); font-family: var(--mono); }

.qr-canvas { background: #fff; padding: 10px; border-radius: 9px; display: flex; align-items: center; justify-content: center; }

/* ============ REPORT BARS ============ */
.report-bar-row { margin-bottom: 14px; }
.report-bar-row:last-child { margin-bottom: 0; }
.report-bar-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-2); margin-bottom: 6px; }
.report-bar-label .mono { font-family: var(--mono); color: var(--text-3); }
.report-bar { height: 9px; border-radius: 5px; background: var(--bg-raised); border: 1px solid var(--border); overflow: hidden; }
.report-bar-fill { height: 100%; border-radius: 5px; }
.report-bar-fill.red { background: var(--red); }
.report-bar-fill.amber { background: var(--amber); }
.report-bar-fill.green { background: var(--green); }
.report-bar-fill.blue { background: var(--blue); }

/* ============ PRINT ============ */
@media print {
    .sidebar, .topbar, .toast-stack, .page-header button, .btn-row, .modal-close, .modal-foot { display: none !important; }
    .main-col { margin-left: 0; }
    .main { padding: 0; max-width: 100%; }
    .screen:not(.active) { display: none !important; }
    body:has(.modal-overlay.open) .app { display: none !important; }
    .modal-overlay.open {
        position: static !important; background: none !important; backdrop-filter: none !important;
        padding: 0 !important; display: block !important;
    }
    .modal-overlay.open .modal { box-shadow: none !important; border: none !important; max-width: 100% !important; max-height: none !important; }
}

/* RESPONSIVE */
@media (max-width: 1080px) {
    .dash-grid { grid-template-columns: 1fr; }
    .ai-layout { grid-template-columns: 1fr; }
    .chat-message.user { margin-left: 0; }
}

@media (max-width: 860px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
    .main-col { margin-left: 0; }
    .burger { display: flex; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .form-grid { grid-template-columns: 1fr; }
    .landing-grid { grid-template-columns: 1fr; }
}

@media (max-width: 560px) {
    .main { padding: 18px 14px 50px; }
    .stats-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; }
}

/* ============ POLISHED PUBLIC ENTRY SCREENS ============ */
body:has(.landing-shell), body:has(.auth-shell) { background: #edf6f1; }
.landing-shell { isolation: isolate; padding: 40px 80px; background: #eef7f2 url('/images/farm-hero-v2.png') center / cover no-repeat; }
.landing-shell::before { content: ''; position: absolute; inset: 0; z-index: -1; background: rgba(239,248,243,.56); }
.landing-shell .farm-backdrop { display: none; }
.landing-inner { display:flex; flex-direction:column; justify-content:center; min-height:min(760px,calc(100vh - 150px)); max-width:none; margin:0; padding:54px 40px 34px; border-radius:22px 22px 0 0; background:rgba(255,255,255,.91); box-shadow:0 16px 36px rgba(29,76,49,.13); }
.landing-brand { margin-bottom:12px; }.landing-brand .brand-mark { width:112px !important; height:112px !important; }
.species-screen .landing-brand { align-self:center; justify-content:center; }
.landing-title { font-size:clamp(38px,3.2vw,56px); line-height:1.05; letter-spacing:-1.5px; color:#082d2b; }
.landing-sub { font-family:var(--sans); font-size:17px; margin:13px 0 38px; color:#626c77; }
.landing-grid { width:min(100%,1060px); align-self:center; gap:26px; }
.landing-card { min-height:350px; padding:42px 24px 70px; border-radius:20px; border-color:#e4ebe7; box-shadow:0 10px 24px rgba(24,57,38,.09); gap:10px; }
.landing-card:hover:not(.disabled) { transform:translateY(-5px); box-shadow:0 20px 35px rgba(24,57,38,.14); border-color:#249147; }
.landing-card:first-child { border:2px solid #29944a; }.landing-card-icon { width:118px; height:118px; background:transparent; font-size:76px; margin:0 0 8px; filter:drop-shadow(0 9px 8px rgba(19,62,35,.12)); }
.landing-card.disabled .landing-card-icon { background:transparent; filter:grayscale(.35) opacity(.72); }.landing-card-name { font-size:27px; color:#082d2b; }.landing-card-desc { font-family:var(--sans); font-size:15px; color:#68727d; }
.landing-card:not(.disabled)::after { content:'→'; position:absolute; bottom:24px; width:48px; height:48px; border-radius:50%; display:grid; place-items:center; border:1.5px solid #269144; color:#16813a; font-size:27px; font-weight:400; }
.soon-badge { top:20px; right:20px; padding:6px 11px; color:#9b7c45; background:#fbf4e5; font-size:10px; }.auth-back { color:#43515e; font-family:var(--sans); font-size:15px; margin-top:28px; }.auth-back:hover { color:#16813a; }
.landing-inner + .trust-bar { width:100%; min-height:62px; padding:14px 36px; border-radius:0 0 18px 18px; background:rgba(250,252,251,.93); border:0; box-shadow:0 12px 24px rgba(29,76,49,.08); }
.trust-bar-brand { font-size:13px; }.trust-items { gap:34px; flex-wrap:nowrap; }.trust-item { font-family:var(--sans); font-size:13px; }

.auth-shell { min-height:100vh; background:#f7fbf8; }.auth-visual { flex-basis:39%; padding:56px 76px; }.auth-visual .farm-backdrop { background:url('/images/farm-hero-v2.png') center / cover no-repeat; }.auth-visual .farm-illustration { display:none; }.auth-visual .farm-overlay { background:linear-gradient(180deg,rgba(245,251,247,.32),rgba(244,250,246,.54)); }
.auth-visual .brand-mark { width:118px !important; height:118px !important; margin-bottom:46px; }.auth-visual-title { font-size:39px; line-height:1.2; letter-spacing:-1.1px; margin-bottom:28px; }.auth-visual-sub { font-size:17px; line-height:1.55; max-width:360px; }
.hero-livestock { position:absolute; z-index:1; left:6%; right:6%; bottom:102px; height:270px; display:flex; align-items:flex-end; justify-content:space-around; pointer-events:none; }
.hero-animal { width:33%; height:245px; object-fit:contain; object-position:center bottom; transform-origin:center bottom; animation:animal-walk 1.9s ease-in-out infinite; }
.hero-goat { animation-delay:-.2s; }.hero-pig { animation-delay:-.85s; width:29%; }.hero-cow { animation-delay:-1.35s; width:38%; }
.auth-visual-content, .auth-visual-badges { z-index:2; }
@keyframes animal-walk { 0%,100% { transform:translate(0,0) rotate(0); } 25% { transform:translate(3px,-9px) rotate(.7deg); } 50% { transform:translate(7px,0) rotate(0); } 75% { transform:translate(3px,-6px) rotate(-.6deg); } }
.species-screen .animal-art { animation:animal-card-arrive .55s both; }
.species-screen .landing-card:nth-child(2) .animal-art { animation-delay:.1s; }.species-screen .landing-card:nth-child(3) .animal-art { animation-delay:.2s; }
.species-screen .landing-card:hover .animal-art { animation:animal-card-idle 1.4s ease-in-out infinite; }
@keyframes animal-card-arrive { from { opacity:0; transform:translateY(12px) scale(.92); } to { opacity:1; transform:translateY(0) scale(1); } }
@keyframes animal-card-idle { 0%,100% { transform:translateY(0) rotate(0); } 50% { transform:translateY(-8px) rotate(-1deg); } }
@media (prefers-reduced-motion: reduce) { .hero-animal, .species-screen .animal-art { animation:none !important; } }
.auth-visual-badges { padding:19px 20px; background:rgba(255,255,255,.6); border:1px solid rgba(255,255,255,.72); border-radius:12px; gap:16px; flex-wrap:nowrap; }.auth-visual-badge { gap:8px; }.auth-visual-badge-title { font-size:12px; }.auth-visual-badge-desc { font-size:10px; }
.auth-form-side { position:relative; background:rgba(255,255,255,.86); padding:40px; }.auth-form-side::before { content:''; position:absolute; width:400px; height:400px; border:1px solid #e6efe9; border-radius:50%; right:-210px; top:22%; }
.auth-card { position:relative; z-index:1; max-width:570px; border-radius:20px; padding:44px 48px; box-shadow:0 16px 34px rgba(24,57,38,.1); }.auth-head .brand-mark { width:112px !important; height:112px !important; margin-bottom:18px; }.auth-title { font-size:31px; letter-spacing:-.7px; }.auth-sub { font-family:var(--sans); font-size:16px; margin-top:8px; }.auth-form { gap:20px; }.form-field { gap:8px; }.form-field label { font-family:var(--sans); font-size:15px; color:#17322e; }.form-field input { padding:15px 17px; border-radius:11px; font-size:16px; background:#fbfdfb; }.auth-card .btn { min-height:52px; font-size:17px; border-radius:10px; }.auth-card .auth-back { color:#239143; font-weight:700; }
@media (max-width:900px) { .landing-shell { padding:20px; }.landing-inner { min-height:auto; padding:38px 22px; }.landing-grid { grid-template-columns:1fr; max-width:360px; }.landing-card { min-height:285px; }.landing-inner + .trust-bar { border-radius:0 0 14px 14px; }.auth-visual { display:none; } }

/* Artwork and proportions shared with the supplied selection references. */
.species-screen .landing-card-icon { font-size: 0; width: 170px; height: 132px; background-repeat: no-repeat; background-position: center; background-size: contain; background-color: #fff; filter: none; }
.species-screen .landing-card:nth-child(1) .landing-card-icon { background-image: url('/images/goat-3d-v2.png'); }
.species-screen .landing-card:nth-child(2) .landing-card-icon { background-image: url('/images/pig-3d-v2.png'); }
.species-screen .landing-card:nth-child(3) .landing-card-icon { background-image: url('/images/cow-3d-v2.png'); }
.species-screen .landing-card { min-height: 365px; }
.species-screen .landing-card.disabled { opacity: 1; }
.species-screen .landing-card.disabled .landing-card-icon { background-color: #fff; filter: grayscale(.08) opacity(.88); }
.species-screen .landing-card.disabled .landing-card-name,
.species-screen .landing-card.disabled .landing-card-desc { opacity: .68; }
.species-screen .landing-card:has(.animal-art) .landing-card-icon { display: none; }
.species-screen .landing-card.disabled { opacity: 1 !important; }
.species-screen .animal-art { display: block; width: 205px; height: 170px; object-fit: contain; object-position: center; margin: -8px 0 2px; filter: none; opacity: 1; }
@media (min-width: 1300px) { .species-screen .animal-art { width: 235px; height: 190px; } }
.role-screen .landing-card { min-height: 330px; padding-top: 38px; }
.role-screen .landing-card:first-child { border: 1px solid #e4ebe7; }
.role-screen .landing-card-icon { width: 144px; height: 120px; font-size: 0; background: #fff url('/images/role-card-sprite-v2.png') no-repeat; background-size: 300% auto; filter: none; }
.role-screen .landing-card:nth-child(1) .landing-card-icon { background-position: 0 center; }
.role-screen .landing-card:nth-child(2) .landing-card-icon { background-position: 50% center; }
.role-screen .landing-card:nth-child(3) .landing-card-icon { background-position: 100% center; }
.role-screen .landing-card-name { font-size: 25px; }
@media (min-width: 1300px) { .landing-grid { width: min(100%, 1120px); }.landing-card { min-height: 380px; }.species-screen .landing-card-icon { width: 190px; height: 145px; } }

/* ============ AGRISENTRY OPERATIONS CONSOLE ============ */
/* Dashboard-only polish based on the supplied desktop references. */
body:has(.app) {
    --console-bg: #090d13;
    --console-sidebar-bg: rgba(9, 13, 19, .97);
    --console-topbar-bg: rgba(9, 13, 19, .88);
    --console-card: rgba(17, 22, 30, .92);
    --console-card-solid: #11161e;
    --console-line: #242b35;
    --console-soft-line: #1d242e;
    background:
        radial-gradient(circle at 100% 55%, rgba(0, 138, 75, .07), transparent 31%),
        var(--console-bg);
}
body:has(.app) { overflow-x: hidden; }
:root[data-theme="light"] body:has(.app) {
    --console-bg: #f3f6f8;
    --console-sidebar-bg: rgba(255, 255, 255, .98);
    --console-topbar-bg: rgba(247, 249, 251, .9);
    --console-card: rgba(255, 255, 255, .96);
    --console-card-solid: #fff;
    --console-line: #dce3e8;
    --console-soft-line: #e7ecef;
    background: radial-gradient(circle at 100% 55%, rgba(22, 163, 74, .06), transparent 31%), var(--console-bg);
}

body:has(.app) .app { --console-sidebar: 286px; }
body:has(.app) .sidebar {
    width: var(--console-sidebar);
    background: var(--console-sidebar-bg);
    border-right-color: var(--console-soft-line);
}
body:has(.app) .main-col { margin-left: var(--console-sidebar); }
body:has(.app) .brand { min-height: 104px; padding: 22px 26px; gap: 13px; }
body:has(.app) .brand-mark { width: 41px; height: 52px; border-radius: 0; }
body:has(.app) .brand-mark.has-logo { padding: 3px; border-radius: 8px; background: rgba(255,255,255,.94); box-shadow: 0 0 10px rgba(34,197,94,.18); }
body:has(.app) .brand-mark-img { filter: saturate(1.2) contrast(1.1); }
body:has(.app) .brand-name { font-size: 21px; letter-spacing: -.45px; }
body:has(.app) .brand-sub { margin-top: 3px; font-family: var(--sans); font-size: 12px; }
body:has(.app) .nav-scroll { padding: 13px 8px 12px; }
body:has(.app) .nav-section { padding: 14px 17px 8px; font-family: var(--sans); font-size: 11px; }
body:has(.app) .nav-item {
    min-height: 46px; margin: 1px 8px; padding: 11px 17px;
    border: 1px solid transparent; border-radius: 9px; font-size: 15px;
}
body:has(.app) .nav-item .icon { width: 19px; height: 19px; }
body:has(.app) .nav-item.active {
    color: #39df7d; border-color: rgba(34, 197, 94, .14);
    background: linear-gradient(90deg, rgba(22, 163, 74, .18), rgba(22, 163, 74, .1));
}
body:has(.app) .sidebar-foot { padding: 12px 16px 22px; }
body:has(.app) .led-legend { padding: 13px 8px; background: #10151d; border: 1px solid #1d2530; }

body:has(.app) .topbar {
    min-height: 76px; padding: 15px 40px; gap: 17px;
    background: var(--console-topbar-bg); border-bottom-color: var(--console-soft-line);
}
body:has(.app) .topbar-title { display: none; }
body:has(.app) .status-pill { min-height: 40px; padding: 8px 17px; border-radius: 12px; font-family: var(--sans); font-size: 13px; }
body:has(.app) .icon-btn, body:has(.app) .avatar { width: 40px; height: 40px; }
body:has(.app) .topbar .btn { min-height: 40px; padding-inline: 17px; }
body:has(.app) .main { max-width: none; padding: 34px 44px 64px; }
body:has(.app) .page-header { align-items: center; margin-bottom: 24px; }
body:has(.app) .page-title { font-size: 30px; letter-spacing: -.65px; }
body:has(.app) .page-sub { margin-top: 4px; font-size: 14px; }

body:has(.app) .btn { min-height: 42px; padding: 9px 17px; border-radius: 10px; font-size: 14px; }
body:has(.app) .btn-primary { background: linear-gradient(180deg, #079341, #057332); box-shadow: inset 0 1px rgba(255,255,255,.12); }
body:has(.app) .panel, body:has(.app) .stat, body:has(.app) .chat-box, body:has(.app) .side-card {
    background: var(--console-card); border-color: var(--console-line); box-shadow: 0 1px 1px rgba(0,0,0,.12);
}
body:has(.app) .stats-grid { gap: 16px; margin-bottom: 18px; }
body:has(.app) .stat { min-height: 150px; padding: 21px 23px; }
body:has(.app) .stat-icon { width: 50px; height: 50px; border-radius: 50%; }
body:has(.app) .stat-icon .icon { width: 24px; height: 24px; }
body:has(.app) .stat-val { margin-top: -9px; font-size: 38px; }
body:has(.app) .stat-foot { margin-top: 10px; font-size: 13px; }
body:has(.app) .dash-grid { grid-template-columns: 1.02fr 1fr; gap: 18px; }
body:has(.app) .panel-head { min-height: 68px; padding: 16px 20px; }
body:has(.app) .panel-title { font-size: 16px; }
body:has(.app) .panel-title .icon { width: 20px; height: 20px; }
body:has(.app) .panel-body { padding: 18px 20px; }
body:has(.app) .toolbar { min-height: 82px; padding: 17px 22px; background: var(--console-card); border: 1px solid var(--console-line); border-radius: 12px; }
body:has(.app) .toolbar-label { min-width: 220px; display: flex; flex-direction: column; gap: 3px; font-size: 14px; color: var(--text-0); }
body:has(.app) .toolbar-label span { color: var(--text-3); font-size: 12px; }
body:has(.app) .search-box { max-width: 475px; }
body:has(.app) .search-input { min-height: 48px; border-radius: 9px; padding-left: 42px; font-size: 14px; }
body:has(.app) .filter-row { margin-left: auto; }
body:has(.app) .filter-pill { min-height: 43px; padding: 9px 18px; border-radius: 9px; font-family: var(--sans); font-size: 13px; }
body:has(.app) .filter-pill.active { color: #70e9a0; background: rgba(12, 111, 59, .2); border-color: #168648; }
:root[data-theme="light"] body:has(.app) .filter-pill.active {
    color: #166534;
    background: #dcfce7;
    border-color: #15803d;
    font-weight: 700;
}
:root[data-theme="light"] body:has(.app) .filter-pill:focus-visible {
    outline: 2px solid #166534;
    outline-offset: 3px;
}
body:has(.app) .tbl th { height: 56px; padding: 0 18px; }
body:has(.app) .tbl td { height: 76px; padding: 14px 18px; font-size: 14px; }
body:has(.app) .log-entry { margin: 8px 0; padding: 18px 22px; border: 1px solid var(--console-line); border-left: 4px solid var(--red); border-radius: 10px; background: var(--console-card-solid); }
body:has(.app) .chat-body { min-height: 450px; }
body:has(.app) .si-blue { background: var(--blue-bg); color: var(--blue); }
body:has(.app) .log-stats .stat { min-height: 134px; }
body:has(.app) .records-panel { min-height: 690px; }
body:has(.app) .records-panel .panel-body,
body:has(.app) .records-panel .log-timeline { min-height: inherit; }
body:has(.app) .medical-empty { min-height: 650px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
body:has(.app) .medical-empty-art { position: relative; width: 170px; height: 170px; display: grid; place-items: center; border-radius: 50%; color: var(--green); background: radial-gradient(circle, var(--green-bg-strong), transparent 68%); }
body:has(.app) .medical-empty-art .icon { width: 100px; height: 100px; stroke-width: 1.1; }
body:has(.app) .medical-empty-art span { position: absolute; font-size: 42px; font-weight: 700; }
body:has(.app) .medical-empty-image { width: 310px; height: 285px; object-fit: contain; margin-bottom: -5px; filter: drop-shadow(0 18px 24px rgba(0,0,0,.26)); }
body:has(.app) .medical-empty h3 { margin-top: 18px; color: var(--text-0); font-size: 24px; }
body:has(.app) .medical-empty p { margin: 10px 0 26px; color: var(--text-3); line-height: 1.6; }
:root[data-theme="light"] body:has(.app) .led-legend { background: #f7f9fa; border-color: var(--console-line); }
:root[data-theme="light"] body:has(.app) .btn-primary { color: #fff; }
:root[data-theme="light"] body:has(.app) .nav-item.active { color: #087a38; }
:root[data-theme="light"] body:has(.app) .medical-empty-image { filter: drop-shadow(0 18px 25px rgba(25,80,50,.16)); }

body:has(.app) #screen-medical .page-header { margin-bottom: 45px; }
body:has(.app) #screen-medical .records-panel { min-height: 690px; border-radius: 13px; }
body:has(.app) #screen-medical .medical-empty { min-height: 686px; }
body:has(.app) #screen-medical .medical-empty .btn { min-width: 174px; justify-content: center; }

/* Dashboard composition from the supplied Herd Dashboard reference. */
body:has(.app) #screen-dashboard .page-header { margin-bottom: 21px; }
body:has(.app) #screen-dashboard .page-sub { color: var(--text-3); }
body:has(.app) #screen-dashboard #last-sync::before { content: '⟳'; color: var(--green); font-size: 18px; margin-right: 7px; vertical-align: -1px; }
body:has(.app) #screen-dashboard .stats-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
body:has(.app) #screen-dashboard .stat { min-height: 150px; border-radius: 13px; }
body:has(.app) #screen-dashboard .stat-label { font-family: var(--sans); font-size: 12px; }
body:has(.app) #screen-dashboard .dash-grid { grid-template-columns: minmax(0, 1.04fr) minmax(0, 1fr); align-items: stretch; }
body:has(.app) #screen-dashboard .dash-grid > div > .panel { height: 594px; margin-bottom: 0; display: flex; flex-direction: column; }
body:has(.app) #screen-dashboard .dash-grid .panel-body { flex: 1; min-height: 0; }
body:has(.app) #dashboard-goats { display: block; height: 100%; padding: 0; }
body:has(.app) #dashboard-goats .goat-tile { width: min(100%, 451px); min-height: 444px; padding: 15px 20px 20px; gap: 13px; }
body:has(.app) .goat-tile-alert-banner { padding: 5px 12px; font-family: var(--sans); font-size: 11px; }
body:has(.app) .goat-tile-name { font-size: 21px; }
body:has(.app) .goat-tile-sub { margin-top: 4px; font-family: var(--sans); font-size: 13px; }
body:has(.app) .goat-tile .status-chip { padding: 8px 15px; border-radius: 8px; font-family: var(--sans); font-size: 12px; }
body:has(.app) .goat-tile-stats { gap: 16px; }
body:has(.app) .goat-tile-stat { min-height: 88px; padding: 15px; }
body:has(.app) .goat-tile-stat-label { font-family: var(--sans); font-size: 11px; font-weight: 700; }
body:has(.app) .goat-tile-stat .temp { margin-top: 8px; font-family: var(--sans); font-size: 17px; font-weight: 700; }
body:has(.app) .goat-tile-reason { padding: 6px 0 16px; border-bottom: 1px dashed var(--border); font-size: 13px; }
body:has(.app) .goat-tile-foot { justify-content: flex-start; gap: 0; font-family: var(--sans); font-size: 14px; }
body:has(.app) .goat-tile-foot span { width: 50%; display: flex; flex-direction: column; gap: 5px; padding: 0 15px; }
body:has(.app) .goat-tile-foot span:first-child { padding-left: 7px; border-right: 1px solid var(--border); }
body:has(.app) .goat-tile-foot b { color: var(--text-3); font-size: 10px; letter-spacing: .5px; }
body:has(.app) .goat-profile-btn { position: relative; width: 100%; justify-content: center; margin-top: auto; white-space: nowrap; }
body:has(.app) .goat-profile-btn .icon { position: absolute; right: 17px; }
body:has(.app) #dashboard-alerts { overflow-y: auto; padding: 13px 18px; }
body:has(.app) #dashboard-alerts .chat-message { min-height: 138px; padding: 13px 15px; border-radius: 10px; }
body:has(.app) #dashboard-alerts .chat-text { line-height: 1.45; }

@media (max-width: 1180px) {
    body:has(.app) #screen-dashboard .dash-grid { grid-template-columns: 1fr; }
    body:has(.app) #screen-dashboard .dash-grid > div > .panel { height: auto; min-height: 480px; }
    body:has(.app) #dashboard-goats .goat-tile { width: 100%; }
}
@media (max-width: 700px) {
    body:has(.app) #screen-dashboard .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    body:has(.app) .goat-profile-btn { padding-left: 17px; justify-content: center; }
}
@media (max-width: 430px) {
    body:has(.app) #screen-dashboard .stats-grid { grid-template-columns: 1fr; }
    body:has(.app) .goat-tile-stats { grid-template-columns: 1fr; }
}

/* All Animals composition from the supplied reference. */
body:has(.app) #screen-animals .toolbar { margin-bottom: 22px; padding: 25px 24px; }
body:has(.app) #screen-animals .filter-pill { display: inline-flex; align-items: center; gap: 8px; }
body:has(.app) #screen-animals .filter-pill .led { width: 8px; height: 8px; }
body:has(.app) #screen-animals .panel { border-radius: 13px; }
body:has(.app) #screen-animals .tbl { min-width: 900px; table-layout: fixed; }
body:has(.app) #screen-animals .tbl th:nth-child(1) { width: 18%; }
body:has(.app) #screen-animals .tbl th:nth-child(2) { width: 17%; }
body:has(.app) #screen-animals .tbl th:nth-child(3) { width: 17%; }
body:has(.app) #screen-animals .tbl th:nth-child(4) { width: 14%; }
body:has(.app) #screen-animals .tbl th:nth-child(5) { width: 13%; }
body:has(.app) #screen-animals .tbl th:nth-child(6) { width: 21%; }
body:has(.app) #screen-animals .tbl th { height: 66px; padding: 0 26px; font-family: var(--sans); font-size: 11px; }
body:has(.app) #screen-animals .tbl td { height: 116px; padding: 18px 26px; }
body:has(.app) .animal-cell { display: flex; align-items: center; gap: 16px; }
body:has(.app) .animal-icon { width: 58px; height: 58px; display: grid; place-items: center; border-radius: 50%; background: var(--green-bg); border: 1px solid var(--green-bg-strong); font-size: 27px; }
body:has(.app) .animal-icon img { width: 48px; height: 48px; display: block; object-fit: contain; }
body:has(.app) .animal-cell b { display: block; color: var(--text-0); font-size: 17px; }
body:has(.app) .animal-cell small, body:has(.app) .animal-temp small { display: block; margin-top: 4px; color: var(--text-3); font-size: 12px; }
body:has(.app) .animal-temp b { margin-left: 7px; font-family: var(--sans); font-size: 17px; }
body:has(.app) #screen-animals .status-chip { padding: 9px 15px; border-radius: 8px; font-family: var(--sans); font-size: 12px; }
body:has(.app) .animal-battery { display: flex; align-items: center; gap: 10px; }
body:has(.app) .animal-battery .icon { width: 21px; height: 21px; color: var(--green); }
body:has(.app) .animal-actions { display: flex; align-items: center; gap: 14px; }
body:has(.app) .view-details { min-height: 43px; padding: 10px 17px; font-family: var(--sans); font-size: 13px; color: var(--text-0); }
body:has(.app) .view-details .icon { width: 15px; height: 15px; }
body:has(.app) .more-btn { width: 43px; height: 43px; justify-content: center; padding: 0; font-size: 23px; }
body:has(.app) .animals-footer { min-height: 96px; display: flex; align-items: center; justify-content: space-between; padding: 20px 26px; border-top: 1px solid var(--border-soft); color: var(--text-2); font-size: 13px; }
body:has(.app) .pagination { display: flex; align-items: center; gap: 7px; }
body:has(.app) .pagination button { min-height: 44px; padding: 9px 15px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text-2); }
body:has(.app) .pagination button.active { min-width: 44px; color: var(--green-dk); border-color: var(--green); background: var(--green-bg); }
body:has(.app) .pagination button:disabled { opacity: .38; }

/* Report Analytics composition from the supplied reference. */
body:has(.app) #screen-reports .page-header { margin-bottom: 22px; }
body:has(.app) #screen-reports > .panel:first-of-type .panel-body { padding: 20px; }
body:has(.app) #screen-reports > .stats-grid .stat { min-height: 130px; border-left: 2px solid var(--border); }
body:has(.app) #screen-reports > .stats-grid .stat:nth-child(1) { border-left-color: var(--red); }
body:has(.app) #screen-reports > .stats-grid .stat:nth-child(2) { border-left-color: var(--amber); }
body:has(.app) #screen-reports > .stats-grid .stat:nth-child(3) { border-left-color: var(--green); }
body:has(.app) #screen-reports > .stats-grid .stat:nth-child(4) { border-left-color: var(--blue); }
body:has(.app) #screen-reports .panel:has(#report-status-graph) > .panel-head { display: none; }
body:has(.app) #screen-reports .panel:has(#report-status-graph) { background: transparent; border: 0; box-shadow: none; overflow: visible; }
body:has(.app) #screen-reports .panel:has(#report-status-graph) > .panel-body { padding: 0; }
body:has(.app) #screen-reports .panel:has(#report-status-graph) .dash-grid { grid-template-columns: 1fr 2fr; gap: 16px; }
body:has(.app) #screen-reports .panel:has(#report-status-graph) .dash-grid > div { min-height: 326px; padding: 18px; background: var(--console-card); border: 1px solid var(--console-line); border-radius: 13px; }
body:has(.app) #screen-reports .profile-section-title { margin: 0 0 20px !important; font-family: var(--sans); color: var(--text-0); font-size: 15px; text-transform: none; }
body:has(.app) #report-status-graph { display: flex; align-items: center; justify-content: center; gap: 24px; min-height: 225px; }
body:has(.app) .report-donut { width: 176px; height: 176px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 50%; background: conic-gradient(var(--red) 0 var(--urgent), var(--amber) var(--urgent) calc(var(--urgent) + var(--monitoring)), var(--green) 0 100%); position: relative; }
body:has(.app) .report-donut::after { content: ''; position: absolute; inset: 29px; border-radius: 50%; background: var(--console-card-solid); }
body:has(.app) .report-donut > div { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; }
body:has(.app) .report-donut b { color: var(--text-0); font-size: 28px; }
body:has(.app) .report-donut span { color: var(--text-3); font-size: 11px; }
body:has(.app) .report-legend { min-width: 155px; display: flex; flex-direction: column; gap: 16px; }
body:has(.app) .report-legend span { display: grid; grid-template-columns: 10px 1fr auto; gap: 8px; align-items: center; color: var(--text-2); font-size: 12px; }
body:has(.app) .report-legend i { width: 9px; height: 9px; border-radius: 50%; }
body:has(.app) .report-legend i.red { background: var(--red); } body:has(.app) .report-legend i.amber { background: var(--amber); } body:has(.app) .report-legend i.green { background: var(--green); }
body:has(.app) .report-legend b { color: var(--text-1); font-size: 11px; }
body:has(.app) .line-chart { position: relative; height: 225px; padding: 10px 12px 25px 40px; background: repeating-linear-gradient(to bottom, transparent 0 36px, var(--border-soft) 37px); }
body:has(.app) .line-chart svg { width: 100%; height: 165px; overflow: visible; }
body:has(.app) .chart-area { fill: url(#chartFill); }
body:has(.app) .chart-line { fill: none; stroke: var(--blue); stroke-width: 2.2; vector-effect: non-scaling-stroke; }
body:has(.app) .chart-points circle { fill: var(--console-card-solid); stroke: var(--blue); stroke-width: 2; vector-effect: non-scaling-stroke; }
body:has(.app) .chart-y { position: absolute; left: 0; top: 6px; bottom: 32px; display: flex; flex-direction: column; justify-content: space-between; color: var(--text-3); font-size: 10px; }
body:has(.app) .chart-x { display: flex; justify-content: space-between; color: var(--text-3); font-size: 10px; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) { margin-left: calc(33.333% + 6px); margin-top: -101px; position: relative; z-index: 2; border-radius: 0 0 13px 13px; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) > .panel-head { display: none; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) .stats-grid { margin: 0; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) .stat { min-height: 76px; border: 0; border-radius: 0; box-shadow: none; background: transparent; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) .stat-val { font-size: 19px; margin: 0; }
body:has(.app) #screen-reports .panel:has(#report-temp-avg) .stats-grid .stat:last-child { display: none; }
body:has(.app) #screen-reports .panel:has(#report-distribution) { background: transparent; border: 0; box-shadow: none; }
body:has(.app) #screen-reports .panel:has(#report-distribution) > .panel-head { display: none; }
body:has(.app) .report-quick-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
body:has(.app) .report-quick-stats > div { min-height: 135px; padding: 22px; display: flex; flex-direction: column; justify-content: center; background: var(--console-card); border: 1px solid var(--console-line); border-radius: 12px; }
body:has(.app) .report-quick-stats span { color: var(--text-3); font-size: 11px; text-transform: uppercase; }
body:has(.app) .report-quick-stats b { margin: 8px 0; color: var(--text-0); font-size: 22px; }
body:has(.app) .report-quick-stats small { color: var(--text-3); font-size: 11px; }
@media (max-width: 1100px) { body:has(.app) #screen-reports .panel:has(#report-status-graph) .dash-grid { grid-template-columns: 1fr; } body:has(.app) #screen-reports .panel:has(#report-temp-avg) { margin: 0; } body:has(.app) .report-quick-stats { grid-template-columns: repeat(2, 1fr); } }

/* Health Logs horizontal event cards. */
body:has(.app) #screen-logs .log-stats .stat { min-height: 134px; }
body:has(.app) #screen-logs > .panel .panel-body { padding: 0; }
body:has(.app) #screen-logs .log-timeline { gap: 14px; padding: 0; }
body:has(.app) .health-event-row { display: grid; grid-template-columns: 1.45fr 1.18fr 1fr .85fr .65fr; align-items: stretch; gap: 0; min-height: 138px; margin: 0; padding: 20px; border-left-width: 4px; }
body:has(.app) .health-event-row > div { padding: 0 24px; border-right: 1px solid var(--border); display: flex; justify-content: center; flex-direction: column; }
body:has(.app) .health-event-row > div:first-child { padding-left: 0; }
body:has(.app) .health-event-row > div:last-child { border-right: 0; }
body:has(.app) .event-goat { flex-direction: row !important; align-items: center; justify-content: flex-start !important; gap: 18px; }
body:has(.app) .event-goat > div, body:has(.app) .event-details, body:has(.app) .event-reading { gap: 10px; }
body:has(.app) .event-goat b { display: block; color: var(--text-0); font-size: 19px; }
body:has(.app) .event-goat span:not(.event-temp-icon), body:has(.app) .event-goat small { display: block; margin-top: 4px; color: var(--text-3); font-size: 12px; }
body:has(.app) .event-temp-icon { width: 58px; height: 58px; display: grid; place-items: center; flex: 0 0 auto; border-radius: 50%; color: var(--red); background: var(--red-bg); border: 1px solid var(--red-bg-strong); font-size: 25px; }
body:has(.app) .event-temp-icon.low { color: var(--blue); background: var(--blue-bg); border-color: var(--blue-bg-strong); }
body:has(.app) .health-event-row small { color: var(--text-3); font-size: 9px; text-transform: uppercase; letter-spacing: .4px; }
body:has(.app) .health-event-row b { color: var(--text-1); font-size: 13px; font-weight: 500; }
body:has(.app) .event-details span, body:has(.app) .event-reading span { display: flex; flex-direction: column; gap: 5px; }
body:has(.app) .event-reading .temp { font-size: 16px; font-weight: 700; }
body:has(.app) .event-reading .led, body:has(.app) .event-led .led { margin-right: 8px; }
body:has(.app) .event-severity .status-chip { align-self: flex-start; margin-top: 9px; border-radius: 7px; padding: 7px 11px; }
body:has(.app) .health-log-footer { min-height: 76px; display: flex; justify-content: space-between; align-items: center; padding: 14px 22px; border-top: 1px solid var(--border); color: var(--text-3); font-size: 12px; }
@media (max-width: 1100px) { body:has(.app) .health-event-row { grid-template-columns: 1fr 1fr; } body:has(.app) .health-event-row > div { border: 0; padding: 10px; } }

/* Collars table composition from the supplied reference. */
body:has(.app) #screen-collars .page-header { margin-bottom: 36px; }
body:has(.app) #screen-collars .collars-panel { min-height: 690px; display: flex; flex-direction: column; border-radius: 13px; }
body:has(.app) #screen-collars .collars-panel > .panel-body { flex: 1; padding: 0; }
body:has(.app) #screen-collars .tbl { min-width: 960px; table-layout: fixed; }
body:has(.app) #screen-collars .tbl th { height: 67px; padding: 0 34px; font-family: var(--sans); font-size: 11px; }
body:has(.app) #screen-collars .tbl td { height: 84px; padding: 16px 34px; font-size: 14px; }
body:has(.app) #screen-collars .tbl th:nth-child(1) { width: 16%; }
body:has(.app) #screen-collars .tbl th:nth-child(2) { width: 19%; }
body:has(.app) #screen-collars .tbl th:nth-child(3) { width: 17%; }
body:has(.app) #screen-collars .tbl th:nth-child(4) { width: 12%; }
body:has(.app) #screen-collars .tbl th:nth-child(5) { width: 12%; }
body:has(.app) #screen-collars .tbl th:nth-child(6) { width: 19%; }
body:has(.app) #screen-collars .tbl th:nth-child(7) { width: 8%; }
body:has(.app) .collar-code { color: var(--text-1); white-space: nowrap; }
body:has(.app) .assigned-goat { display: inline-flex; align-items: center; gap: 14px; color: var(--text-1); }
body:has(.app) .assigned-goat i { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 9px; background: var(--green-bg); border: 1px solid var(--green-bg-strong); font-style: normal; font-size: 20px; }
body:has(.app) .collar-battery { display: inline-flex; align-items: center; gap: 10px; }
body:has(.app) .collar-battery .icon { width: 21px; height: 21px; color: var(--green); }
body:has(.app) #screen-collars .status-chip { padding: 5px 12px; }
body:has(.app) .collar-delete { width: 41px; height: 41px; padding: 0; justify-content: center; color: var(--red); border-color: var(--red-bg-strong); background: var(--red-bg); }
body:has(.app) .collar-delete .icon { width: 18px; height: 18px; }
body:has(.app) .collars-footer { min-height: 86px; margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-top: 1px solid var(--border); color: var(--text-2); font-size: 13px; }

/* Pixel geometry for the supplied 1584 × 992 dashboard reference. */
@media (min-width: 1181px) {
    body:has(.app) .app { --console-sidebar: 278px; }
    body:has(.app) .brand { min-height: 104px; padding-left: 28px; }
    body:has(.app) .topbar { min-height: 82px; padding-inline: 39px; }
    body:has(.app) .main { padding: 34px 55px 30px; }
    body:has(.app) #screen-dashboard .page-header { min-height: 72px; margin-bottom: 20px; }
    body:has(.app) #screen-dashboard .page-title { font-size: 30px; line-height: 1.12; }
    body:has(.app) #screen-dashboard .stats-grid { height: 151px; margin-bottom: 20px; }
    body:has(.app) #screen-dashboard .stat { min-height: 151px; height: 151px; padding: 21px 24px; }
    body:has(.app) #screen-dashboard .dash-grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; }
    body:has(.app) #screen-dashboard .dash-grid > div > .panel { height: 584px; }
    body:has(.app) #screen-dashboard .panel-head { min-height: 67px; height: 67px; padding: 13px 18px; }
    body:has(.app) #screen-dashboard .panel-body { padding: 20px 18px; }
    body:has(.app) #dashboard-goats .goat-tile { width: 444px; min-height: 407px; height: 407px; padding: 15px 20px 18px; }
    body:has(.app) #dashboard-alerts { padding: 14px 18px; }
    body:has(.app) #dashboard-alerts .chat-message { min-height: 134px; margin-bottom: 10px !important; }
}

/* Supplied light-mode Health Logs variant. */
:root[data-theme="light"] body:has(.app) #screen-logs .page-header { margin-bottom: 31px; }
:root[data-theme="light"] body:has(.app) #screen-logs .page-title { color: #111827; }
:root[data-theme="light"] body:has(.app) #screen-logs .toolbar { min-height: auto; padding: 0; margin-bottom: 27px; border: 0; background: transparent; }
:root[data-theme="light"] body:has(.app) #screen-logs .toolbar-label { display: none; }
:root[data-theme="light"] body:has(.app) #screen-logs .toolbar .filter-row { margin-left: 0; gap: 20px; }
:root[data-theme="light"] body:has(.app) #screen-logs .filter-pill { min-height: 47px; padding: 10px 19px; background: #fff; border-color: #dfe5e8; color: #172033; box-shadow: 0 2px 7px rgba(15,23,42,.05); }
:root[data-theme="light"] body:has(.app) #screen-logs .filter-pill { display:inline-flex; align-items:center; gap:10px; }
:root[data-theme="light"] body:has(.app) #screen-logs .filter-pill.active { background: #e4f7eb; border-color: #d7f0e1; color: #087a38; }
:root[data-theme="light"] body:has(.app) #screen-logs .log-stats { display: none; }
:root[data-theme="light"] body:has(.app) #screen-logs > .panel { background: transparent; border: 0; box-shadow: none; overflow: visible; }
:root[data-theme="light"] body:has(.app) #screen-logs > .panel > .panel-body { overflow: visible; }
:root[data-theme="light"] body:has(.app) #screen-logs .log-timeline { gap: 26px; }
:root[data-theme="light"] body:has(.app) #screen-logs .health-event-row { min-height: 165px; padding: 31px 20px; border: 1px solid #e7ebee; border-left: 1px solid #e7ebee; border-radius: 13px; background: #fff; box-shadow: 0 7px 18px rgba(15,23,42,.055); }
:root[data-theme="light"] body:has(.app) #screen-logs .health-event-row > div { border-right-color: #e5e9ec; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-temp-icon { color: transparent; font-size: 0; background: #e4f8ec; border-color: #dff4e7; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-temp-icon::before { content: ''; width: 48px; height: 48px; background: url('/images/goat-avatar-realistic.png') center / contain no-repeat; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-goat b { color: #111827; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-goat span:not(.event-temp-icon),
:root[data-theme="light"] body:has(.app) #screen-logs .event-goat small,
:root[data-theme="light"] body:has(.app) #screen-logs .health-event-row small { color: #536071; }
:root[data-theme="light"] body:has(.app) #screen-logs .health-event-row b { color: #3f4b5a; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-reading .temp,
:root[data-theme="light"] body:has(.app) #screen-logs .event-led b { color: #1475dc; }
:root[data-theme="light"] body:has(.app) #screen-logs .event-severity .status-chip { min-width: 65px; justify-content: center; color: #fff; background: linear-gradient(#f43f3f,#d91616); box-shadow: 0 3px 7px rgba(220,38,38,.2); }
:root[data-theme="light"] body:has(.app) #screen-logs .health-log-footer { display: none; }
:root[data-theme="light"] body:has(.app) #screen-logs .btn-primary { background: linear-gradient(#10a653,#07843f); }

/* Supplied light-mode Herd Dashboard variant. */
:root[data-theme="light"] body:has(.app) { background:#f6f8fb; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .page-title { color:#101827; }
:root[data-theme="light"] body:has(.app) #screen-dashboard #last-sync { color:#657184; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .stat,
:root[data-theme="light"] body:has(.app) #screen-dashboard .panel { background:#fff; border-color:#e7ebef; box-shadow:0 6px 16px rgba(15,23,42,.055); }
:root[data-theme="light"] body:has(.app) #screen-dashboard .stat { min-height:150px; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .stat-label { color:#4b5565; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .stat-val { color:#111827; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .stat-foot { color:#536071; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .panel-head { background:#fff; border-bottom-color:#e8ecef; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .panel-title { color:#172033; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .panel-title .icon { width:40px; height:40px; padding:10px; border-radius:50%; color:#149847; background:#e5f8ec; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .panel-head .btn { background:#fff; color:#172033; border-color:#dfe5e8; box-shadow:0 2px 6px rgba(15,23,42,.04); }
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile.urgent { background:#fff0f0; border-color:#ffd3d3; box-shadow:none; }
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile-stat { background:rgba(255,255,255,.9); border-color:#e9e5e5; }
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile-name { color:#111827; }
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile-sub,
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile-reason,
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-tile-foot { color:#536071; }
:root[data-theme="light"] body:has(.app) #dashboard-goats .goat-profile-btn { color:#fff; background:linear-gradient(#10a954,#07883f); border-color:#07883f; }
:root[data-theme="light"] body:has(.app) #dashboard-alerts { background:#fff; }
:root[data-theme="light"] body:has(.app) #dashboard-alerts .chat-message { box-shadow:none; }
:root[data-theme="light"] body:has(.app) #dashboard-alerts .chat-message .chat-text { color:#263348; }
:root[data-theme="light"] body:has(.app) #dashboard-alerts .chat-message[style*="blue"] { background:#f0f6ff !important; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .btn-primary { color:#fff; background:linear-gradient(#10a954,#07883f); }
:root[data-theme="light"] body:has(.app) #screen-dashboard .si-green { color:#149847; background:#e7f8ed; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .si-amber { color:#bf8208; background:#fff4d5; }
:root[data-theme="light"] body:has(.app) #screen-dashboard .si-red { color:#e23636; background:#ffebeb; }

/* Theme parity: switching theme must never reflow a screen or hide information. */
body:has(.app) #screen-logs .page-header { margin-bottom: 31px; }
body:has(.app) #screen-logs .toolbar {
    min-height: auto; padding: 0; margin-bottom: 27px; border: 0; background: transparent;
}
body:has(.app) #screen-logs .toolbar-label { display: none; }
body:has(.app) #screen-logs .toolbar .filter-row { margin-left: 0; gap: 20px; }
body:has(.app) #screen-logs .filter-pill { min-height: 47px; padding: 10px 19px; }
body:has(.app) #screen-logs .log-stats { display: none; }
body:has(.app) #screen-logs > .panel { background: transparent; border: 0; box-shadow: none; overflow: visible; }
body:has(.app) #screen-logs > .panel > .panel-body { overflow: visible; }
body:has(.app) #screen-logs .log-timeline { gap: 26px; }
body:has(.app) #screen-logs .health-event-row {
    min-height: 165px; padding: 31px 20px; border-width: 1px; border-left-width: 1px;
    border-radius: 13px; background: var(--console-card); box-shadow: var(--shadow-md);
}
body:has(.app) #screen-logs .event-temp-icon { color: transparent; font-size: 0; }
body:has(.app) #screen-logs .event-temp-icon::before { content: ''; width: 48px; height: 48px; background: url('/images/goat-avatar-realistic.png') center / contain no-repeat; }
body:has(.app) #screen-logs .event-severity .status-chip { min-width: 65px; justify-content: center; }
body:has(.app) #screen-logs .health-log-footer { display: none; }
body:has(.app) #screen-dashboard .panel-title .icon {
    width: 40px; height: 40px; padding: 10px; border-radius: 50%;
    color: var(--green); background: var(--green-bg);
}

/* Smooth palette changes without animating layout geometry. */
body:has(.app), body:has(.app) .sidebar, body:has(.app) .topbar,
body:has(.app) .panel, body:has(.app) .stat, body:has(.app) .toolbar,
body:has(.app) .btn, body:has(.app) .filter-pill, body:has(.app) .nav-item,
body:has(.app) .health-event-row, body:has(.app) .goat-tile {
    transition-property: background-color, border-color, color, box-shadow;
    transition-duration: .2s;
    transition-timing-function: ease;
}

/* Dashboard responsive rules must win over the desktop reference geometry. */
@media (max-width: 1180px) {
    body:has(.app) #screen-dashboard .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        height: auto;
    }
    body:has(.app) #screen-dashboard .stat {
        width: auto;
        height: auto;
        min-height: 142px;
    }
    body:has(.app) #screen-dashboard .dash-grid {
        grid-template-columns: 1fr;
    }
    body:has(.app) #screen-dashboard .dash-grid > div > .panel {
        width: 100%;
        height: auto;
        min-height: 520px;
    }
    body:has(.app) #dashboard-goats .goat-tile {
        width: min(100%, 444px);
        height: auto;
        min-height: 407px;
    }
    body:has(.app) #dashboard-alerts {
        max-height: 580px;
    }
}

@media (max-width: 860px) {
    body:has(.app) #screen-dashboard .page-header {
        align-items: flex-start;
    }
    body:has(.app) #screen-dashboard .stats-grid {
        width: 100%;
    }
}

@media (max-width: 700px) {
    body:has(.app) #screen-dashboard .stats-grid {
        grid-template-columns: 1fr;
    }
    body:has(.app) #dashboard-goats .goat-tile {
        max-width: none;
    }
    body:has(.app) #screen-dashboard .page-header {
        flex-direction: column;
    }
    body:has(.app) #screen-dashboard .page-header .btn-row {
        width: 100%;
    }
    body:has(.app) #screen-dashboard .page-header .btn-row .btn {
        flex: 1;
        justify-content: center;
    }
    body:has(.app) #screen-dashboard .panel-head {
        padding-inline: 14px;
    }
    body:has(.app) #screen-dashboard .panel-title {
        min-width: 0;
        font-size: 14px;
    }
    body:has(.app) #screen-dashboard .panel-head .btn {
        flex-shrink: 0;
    }
    body:has(.app) #screen-dashboard .goat-tile-stats {
        grid-template-columns: 1fr 1fr;
    }
    body:has(.app) #screen-dashboard .goat-tile-foot span {
        padding-inline: 8px;
    }
}

@media (max-width: 430px) {
    body:has(.app) #screen-dashboard .goat-tile-stats {
        grid-template-columns: 1fr;
    }
    body:has(.app) #screen-dashboard .goat-tile-foot {
        flex-direction: column;
        gap: 14px;
    }
    body:has(.app) #screen-dashboard .goat-tile-foot span {
        width: 100%;
        border-right: 0;
    }
}

/* AI Vet Advice composition from the supplied reference. */
body:has(.app) #screen-ai .page-header { margin-bottom: 20px; }
body:has(.app) #screen-ai .ai-layout { grid-template-columns: 1.3fr 1fr; gap: 22px; align-items: start; }
body:has(.app) #screen-ai .chat-box { min-height: 742px; padding: 0 18px 18px; border-radius: 13px; }
body:has(.app) #screen-ai .chat-box > .panel-head { margin: 0 -18px; padding: 16px 20px; }
body:has(.app) #screen-ai .chat-box > .panel-head .status-chip { display: none; }
body:has(.app) #screen-ai .chat-body { min-height: 525px; max-height: 525px; padding: 18px 0; }
body:has(.app) #screen-ai .chat-message { padding: 16px 18px; border-radius: 11px; }
body:has(.app) #screen-ai .chat-message.alert { min-height: 98px; border-color: var(--red-bg-strong); }
body:has(.app) #screen-ai .chat-message.ai { min-height: 276px; padding: 22px 24px 22px 96px; position: relative; }
body:has(.app) #screen-ai .chat-message.ai::before { content: ''; position: absolute; left: 18px; top: 18px; width: 50px; height: 56px; padding: 5px; border: 1px solid rgba(34,197,94,.28); border-radius: 12px; background: rgba(255,255,255,.96) url('/images/agrisentry-logo-v2-transparent.png') center / 42px 48px no-repeat; box-shadow: 0 0 0 3px rgba(34,197,94,.07), 0 8px 18px rgba(0,0,0,.2); }
:root[data-theme="light"] body:has(.app) #screen-ai .chat-message.ai::before { background-color: #fff; box-shadow: 0 7px 16px rgba(22,101,52,.13); }
body:has(.app) #screen-ai .chat-message.ai .chat-top { align-items: center; }
body:has(.app) #screen-ai .chat-message.ai .status-chip { font-size: 14px; background: transparent; padding: 0; color: var(--text-0); }
body:has(.app) #screen-ai .chat-message.ai .chat-text { line-height: 1.65; color: var(--text-2); }
body:has(.app) #screen-ai .chat-input-row { min-height: 126px; margin-top: auto; padding: 16px 12px; align-items: flex-end; border: 1px solid var(--border); border-radius: 11px; background: var(--bg-raised); }
body:has(.app) #screen-ai .chat-input { align-self: flex-start; border: 0; background: transparent; }
body:has(.app) #screen-ai .chat-input-row .btn { min-width: 100px; justify-content: center; }
body:has(.app) #screen-ai .side-card { border-radius: 13px; }
body:has(.app) #screen-ai .side-card:first-child { min-height: 414px; }
body:has(.app) #screen-ai .side-card:last-child { min-height: 306px; }
body:has(.app) #screen-ai .side-card-head { min-height: 65px; display: flex; align-items: center; gap: 11px; padding: 15px 18px; font-size: 16px; }
body:has(.app) .side-title-icon { width: 39px; height: 39px; display: grid; place-items: center; border-radius: 50%; color: var(--green); background: var(--green-bg); border: 1px solid var(--green-bg-strong); font-size: 21px; }
body:has(.app) #screen-ai .side-card-body { padding: 0 18px 14px; }
body:has(.app) #screen-ai .prompt-btn { min-height: 66px; margin: 0; padding: 9px 12px; display: grid; grid-template-columns: 42px 1fr 20px; align-items: center; gap: 11px; border-radius: 0; border-width: 0 0 1px; background: transparent; }
body:has(.app) #screen-ai .prompt-btn:first-child { border-radius: 10px 10px 0 0; border-top: 1px solid var(--border); }
body:has(.app) #screen-ai .prompt-btn:last-child { border-radius: 0 0 10px 10px; }
body:has(.app) #screen-ai .prompt-btn i { width: 39px; height: 39px; display: grid; place-items: center; border-radius: 50%; font-style: normal; font-size: 18px; }
body:has(.app) #screen-ai .prompt-btn.red i { color: var(--red); background: var(--red-bg); }
body:has(.app) #screen-ai .prompt-btn.blue i { color: var(--blue); background: var(--blue-bg); }
body:has(.app) #screen-ai .prompt-btn.amber i { color: var(--amber); background: var(--amber-bg); }
body:has(.app) #screen-ai .prompt-btn span { display: flex; flex-direction: column; gap: 3px; }
body:has(.app) #screen-ai .prompt-btn small { color: var(--text-3); font-size: 11px; font-weight: 400; }
body:has(.app) #screen-ai .prompt-btn b { color: var(--text-2); font-size: 24px; font-weight: 400; }
body:has(.app) #screen-ai .led-reference { min-height: 64px; margin: 0; padding: 11px 14px; border-radius: 0; background: transparent; border-width: 0 0 1px; }
body:has(.app) #screen-ai .led-reference:first-child { border-radius: 10px 10px 0 0; border-top: 1px solid var(--border); }
body:has(.app) #screen-ai .led-reference:last-child { border-radius: 0 0 10px 10px; }
@media (max-width: 1080px) { body:has(.app) #screen-ai .ai-layout { grid-template-columns: 1fr; } }
@media (max-width: 560px) { body:has(.app) #screen-ai .chat-message.ai { padding: 86px 18px 18px; } body:has(.app) #screen-ai .chat-message.ai::before { left:18px; top:16px; } }

@media (max-width: 1080px) {
    body:has(.app) .dash-grid { grid-template-columns: 1fr; }
}
@media (max-width: 860px) {
    body:has(.app) .sidebar { width: min(286px, 88vw); }
    body:has(.app) .main-col { margin-left: 0; }
    body:has(.app) .topbar { padding: 12px 18px; }
    body:has(.app) .topbar-title { display: block; }
    body:has(.app) .main { padding: 26px 20px 54px; }
}
@media (max-width: 560px) {
    body:has(.app) .status-pill, body:has(.app) .topbar form, body:has(.app) .topbar-title { display: none; }
    body:has(.app) .main { padding: 21px 14px 48px; }
    body:has(.app) .page-title { font-size: 25px; }
    body:has(.app) .toolbar { padding: 14px; }
    body:has(.app) .filter-row { margin-left: 0; }
}

/* Forms and records remain usable on narrow screens. */
[hidden]{display:none!important}
.main-col,.main,.card{min-width:0}
.table-wrap{max-width:100%;overflow-x:auto}
.modal{max-width:calc(100vw - 24px);max-height:calc(100dvh - 24px);overflow-y:auto}
@media(max-width:600px){.form-grid{grid-template-columns:1fr!important}.form-field.full{grid-column:1}.page-head,.modal-foot,.toolbar{flex-wrap:wrap;gap:12px}.event-card{max-width:100%;overflow-wrap:anywhere}input,select,textarea{font-size:16px!important}.topbar form{display:block!important}}

#log-motion{font:inherit;color:var(--text-1);background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;max-width:100%}#screen-reports .page-header,#screen-medical .page-header{flex-wrap:wrap;gap:12px}#administration{max-width:820px}

/* Dashboard lists: compact tiles with accessible scrolling. */
body:has(.app) #screen-dashboard .dash-grid { grid-template-columns:minmax(0,2fr) minmax(300px,1fr); align-items:start; }
body:has(.app) #screen-dashboard .dash-grid > div { min-width:0; }
body:has(.app) #screen-dashboard .dash-grid > div > .panel { height:auto; min-height:0; }
body:has(.app) #screen-dashboard .dash-grid .panel-body { max-height:560px; overflow-y:auto; overflow-x:hidden; scrollbar-gutter:stable; scrollbar-width:auto; scrollbar-color:var(--text-3) var(--bg-raised); }
body:has(.app) #dashboard-goats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; height:auto; align-items:stretch; }
body:has(.app) #dashboard-goats .goat-tile { width:100%; min-width:0; height:auto; min-height:270px; margin:0; padding:16px; gap:12px; }
body:has(.app) #dashboard-goats .goat-tile-head { flex-wrap:wrap; gap:8px; }
body:has(.app) #dashboard-goats .goat-tile-name { font-size:18px; overflow-wrap:anywhere; }
body:has(.app) #dashboard-goats .goat-tile-sub { font-size:12px; overflow-wrap:anywhere; }
body:has(.app) #dashboard-goats .goat-tile-stats { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
body:has(.app) #dashboard-goats .goat-tile-stat { min-height:68px; padding:10px; overflow-wrap:anywhere; }
body:has(.app) #dashboard-goats .goat-profile-btn { min-height:40px; padding:10px; white-space:normal; }
body:has(.app) #dashboard-goats .goat-profile-btn .icon { position:static; }
body:has(.app) #dashboard-alerts .chat-message { min-height:0; padding:14px; margin-bottom:12px!important; overflow-wrap:anywhere; }
body:has(.app) #screen-dashboard .dashboard-scroll-hint { margin:0; padding:9px 18px; font-size:12px; color:var(--text-3); border-top:1px solid var(--border); }
@media(max-width:1300px) { body:has(.app) #screen-dashboard .dash-grid { grid-template-columns:1fr; } body:has(.app) #screen-dashboard #dashboard-alerts { max-height:360px; } }
@media(max-width:600px) { body:has(.app) #dashboard-goats { grid-template-columns:1fr; } body:has(.app) #dashboard-goats .goat-tile { min-height:0; } }

/* Keep dashboard panels scrollable without visible scrollbar tracks. */
body:has(.app) #screen-dashboard .dash-grid .panel-body {
    scrollbar-width: none;
    scrollbar-gutter: auto;
    -ms-overflow-style: none;
}
body:has(.app) #screen-dashboard .dash-grid .panel-body::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}
