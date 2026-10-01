<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
@include('partials.language')

<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AgriSentry — Manage Users</title>
<link rel="icon" href="/images/anuvimco-logo.png">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<script>try {document.documentElement.dataset.theme=localStorage.getItem("agrisentry-theme")||"light";}catch(e){}</script>
<style>
@include('partials.design-system')
dialog.contact-dialog{margin:auto;width:min(520px,calc(100vw - 32px));border:1px solid var(--border);border-radius:var(--radius);padding:24px;background:var(--bg-card);color:var(--text-0);max-height:90dvh;overflow:auto}dialog.contact-dialog::backdrop{background:rgba(0,0,0,.5)}.contact-dialog .form-field{margin:16px 0}.contact-dialog h2{font-size:20px}.topbar{flex-wrap:wrap;gap:10px}
@media(max-width:760px){.dash-grid{grid-template-columns:1fr!important}}
</style>
</head>
<body>

<svg style="display:none" aria-hidden="true">
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><polyline points="9 12 11 14 15 10"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><polyline points="4 7 20 7"/><path d="M6 7l1 13h10l1-13"/><path d="M9.5 7V4.5h5V7"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="14 6 20 12 14 18"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><polyline points="4 12 9.5 18 20 6"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3.5L22 20H2L12 3.5z"/><line x1="12" y1="10" x2="12" y2="14.5"/><circle cx="12" cy="17.3" r="0.4" fill="currentColor"/></symbol>
</svg>

<div class="toast-stack" id="toast-stack"></div>

<div class="app">
    <div class="main-col" style="margin-left:0">
        <header class="topbar">
            <div class="topbar-title">Manage Users</div>
            <a class="btn btn-sm" href="/settings">Settings</a><a class="btn btn-sm" href="/admin/access">Access control &amp; activity logs</a><div class="topbar-spacer"></div>
            <a href="/agrisentry" class="btn btn-sm"><svg class="icon"><use href="#i-arrow"/></svg> Back to Dashboard</a>
        </header>

        <main class="main">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Users &amp; Roles</h1>
                    <div class="page-sub">Admin, Cooperative Staff, and Caretaker accounts. Caretakers require at least one SMS phone number.</div>
                </div>
            </div>

            <div class="dash-grid" style="grid-template-columns:1fr 1.4fr">
                <div>
                    <div class="panel">
                        <div class="panel-head"><h2 class="panel-title">Add User</h2></div>
                        <div class="panel-body">
                            <form id="form-add-user" onsubmit="return submitAddUser(event)">
                                <div class="form-field full" style="margin-bottom:12px">
                                    <label for="new-user-name">Name <span class="req">*</span></label>
                                    <input type="text" id="new-user-name" name="name" required placeholder="e.g. Juan Dela Cruz">
                                </div>
                                <div class="form-field full" style="margin-bottom:12px">
                                    <label for="new-user-username">Username <span class="req">*</span></label>
                                    <input type="text" id="new-user-username" name="username" required placeholder="e.g. jdelacruz">
                                </div>
                                <div class="form-field full" style="margin-bottom:12px">
                                    <label for="new-user-email">Registered email (Gmail)</label><input type="email" id="new-user-email" name="email" required autocomplete="email">
                                    <label for="new-user-password">Password <span class="req">*</span></label>
                                    <input type="password" id="new-user-password" name="password" required minlength="8" placeholder="Min. 8 characters">
                                </div>
                                <div class="form-field full" style="margin-bottom:12px">
                                    <label for="new-user-role">Role <span class="req">*</span></label>
                                    <select name="role" id="new-user-role" required onchange="onRoleChange()">
                                        <option value="Caretaker">Caretaker</option>
                                        @if (auth()->user()->role === 'Admin')
                                            <option value="Staff">Cooperative Staff</option>
                                            <option value="Admin">Admin</option>
                                        @endif
                                    </select>
                                </div>

                                <fieldset class="form-field full" style="margin-bottom:6px;border:0">
                                    <legend style="font-size:11.5px;font-weight:600;color:var(--text-2);font-family:var(--mono);padding:0">Phone Numbers <span class="req" id="phone-required-mark">*</span></legend>
                                    <div class="form-hint" id="phone-hint" style="margin-bottom:8px">Required for Caretaker accounts (used for SMS alerts). Add more than one if the caretaker uses multiple SIM cards.</div>
                                    <div id="phone-rows"></div>
                                    <button type="button" class="btn btn-sm" onclick="addPhoneRow()"><svg class="icon"><use href="#i-plus"/></svg> Add Phone Number</button>
                                </fieldset>

                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:14px" id="btn-submit-user">
                                    <svg class="icon"><use href="#i-plus"/></svg> Create User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="panel">
                        <div class="panel-head"><h2 class="panel-title">All Users</h2></div>
                        <div class="panel-body tight">
                            <div class="table-wrap">
                                <table class="tbl">
                                    <thead>
                                        <tr><th scope="col" style="padding-left:19px">Name</th><th scope="col">Account &amp; recovery email</th><th scope="col">Role</th><th scope="col">Phone Numbers</th><th scope="col" style="padding-right:19px">Actions</th></tr>
                                    </thead>
                                    <tbody id="users-tbody"><tr><td colspan="5" style="padding-left:19px"><div class="skel"></div></td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<dialog id="contact-dialog" class="contact-dialog" aria-labelledby="contact-title"><form id="contact-form"><h2 id="contact-title">Recovery contact</h2><p class="page-sub">Update the email address and optionally add a registered phone.</p><input type="hidden" id="contact-id"><div class="form-field"><label for="contact-email">Recovery email</label><input id="contact-email" type="email" required></div><div class="form-field"><label for="contact-phone">Additional phone number (optional)</label><input id="contact-phone" type="tel" placeholder="e.g. +639171234567"></div><p id="contact-error" role="alert" style="color:var(--red)"></p><div class="btn-row"><button class="btn" type="button" onclick="document.getElementById('contact-dialog').close()">Cancel</button><button class="btn btn-primary" id="contact-save">Save contact</button></div></form></dialog>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;
const CURRENT_ROLE = @json(auth()->user()->role);
let phoneRowCount = 0;
let loadedUsers = [];

function toast(message, type = "info") {
    const stack = document.getElementById("toast-stack");
    const el = document.createElement("div");
    el.className = "toast " + type;
    const iconId = type === "success" ? "i-check" : type === "error" ? "i-alert" : "i-check";
    el.innerHTML = `<svg class="icon toast-icon"><use href="#${iconId}"/></svg><div>${message}</div>`;
    stack.appendChild(el);
    setTimeout(() => { el.style.opacity = "0"; el.style.transition = "opacity .25s"; setTimeout(() => el.remove(), 250); }, 4000);
}

function escapeHtml(str) {
    return String(str ?? "").replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]));
}

async function fetchAPI(endpoint, options = {}) {
    options.headers = { ...(options.headers || {}), "X-CSRF-TOKEN": CSRF_TOKEN, "X-Requested-With": "XMLHttpRequest" };
    const response = await fetch(`/api${endpoint}`, options);
    if (response.status === 401) { window.location.href = "/login"; throw new Error("Session expired."); }
    if (response.status === 419) { window.location.reload(); throw new Error("CSRF token expired."); }
    const data = await response.json().catch(() => ({}));
    if (!response.ok) { const err = new Error(data.message || "Request failed"); err.data = data; throw err; }
    return data;
}

function addPhoneRow(value = "") {
    phoneRowCount++;
    const id = `phone-row-${phoneRowCount}`;
    const wrap = document.getElementById("phone-rows");
    const row = document.createElement("div");
    row.className = "phone-row";
    row.id = id;
    row.innerHTML = `
        <input type="text" aria-label="Phone number" placeholder="e.g. 09171234567" value="${escapeHtml(value)}">
        <button type="button" class="phone-remove" aria-label="Remove this phone number" onclick="document.getElementById('${id}').remove()"><svg class="icon"><use href="#i-x"/></svg></button>
    `;
    wrap.appendChild(row);
}

function onRoleChange() {
    const isCaretaker = document.getElementById("new-user-role").value === "Caretaker";
    document.getElementById("phone-required-mark").style.display = isCaretaker ? "inline" : "none";
    if (isCaretaker && document.getElementById("phone-rows").children.length === 0) addPhoneRow();
}

addPhoneRow();

async function submitAddUser(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById("btn-submit-user");

    const phoneNumbers = Array.from(document.querySelectorAll("#phone-rows input"))
        .map(i => i.value.trim())
        .filter(Boolean)
        .map(phone_number => ({ phone_number }));

    const payload = {
        name: form.name.value,
        username: form.username.value,
        email: form.email.value,
        password: form.password.value,
        role: form.role.value,
        phone_numbers: phoneNumbers,
    };

    btn.disabled = true;
    try {
        await fetchAPI("/users", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify(payload),
        });
        toast(`${payload.name} was added as ${payload.role}.`, "success");
        form.reset();
        document.getElementById("phone-rows").innerHTML = "";
        addPhoneRow();
        loadUsers();
    } catch (e) {
        const msg = e.data?.errors ? Object.values(e.data.errors).flat().join(" ") : e.message;
        toast(msg || "Could not create user.", "error");
    } finally {
        btn.disabled = false;
    }
    return false;
}

function roleChipClass(role) {
    if (role === "Admin") return "role-admin";
    if (role === "Staff") return "role-staff";
    return "role-caretaker";
}

function roleLabel(role) {
    if (role === "Staff") return "Cooperative Staff";
    return role;
}

async function loadUsers() {
    try {
        const data = await fetchAPI("/users");
        const users = data.users || [];
        loadedUsers=users;
        const table = document.getElementById("users-tbody");

        if (users.length === 0) {
            table.innerHTML = `<tr><td colspan="5" style="padding-left:19px"><div class="empty">No users yet.</div></td></tr>`;
            return;
        }

        table.innerHTML = users.map(user => `
            <tr>
                <td translate="no" style="padding-left:19px;font-weight:600">${escapeHtml(user.name)}</td>
                <td><div style="font-weight:600">${escapeHtml(user.username)}</div><div style="color:var(--text-3);font-size:13px;margin-top:5px;overflow-wrap:anywhere">${user.email && !user.email.endsWith('.local') ? escapeHtml(user.email) : 'Recovery email not set'}</div></td>
                <td><span class="role-chip ${roleChipClass(user.role)}">${escapeHtml(roleLabel(user.role))}</span></td>
                <td>${[...new Map((user.phone_numbers || []).map(p => [p.phone_number.trim(), p])).values()].map(p => `<span class="tag" style="margin-right:4px">${escapeHtml(p.phone_number)}</span>`).join("") || '<span style="color:var(--text-3);font-size:12px">None</span>'}</td>
                <td style="padding-right:19px"><div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><button type="button" class="btn btn-sm" onclick="editContact(${user.id})">Edit contact</button>
                    ${CURRENT_ROLE === "Admin" ? `<button type="button" class="row-btn" aria-label="Delete ${escapeHtml(user.name)}" onclick="deleteUser(${user.id})"><svg class="icon" style="width:11px;height:11px"><use href="#i-trash"/></svg></button>` : ""}
                </div></td>
            </tr>
        `).join("");
    } catch (e) {
        toast("Could not load users.", "error");
    }
}

async function deleteUser(id) {
    const name = loadedUsers.find(user => user.id === id)?.name || "this user";
    if (!confirm(`Remove ${name}'s account?`)) return;
    try {
        await fetchAPI(`/users/${id}`, { method: "DELETE" });
        toast(`${name} removed.`, "success");
        loadUsers();
    } catch (e) {
        toast(e.message || "Could not remove user.", "error");
    }
}

function editContact(id) {
    const user=loadedUsers.find(user=>user.id===id);if(!user)return;
    document.getElementById('contact-id').value=id;
    document.getElementById('contact-email').value=user.email || '';
    document.getElementById('contact-phone').value='';
    document.getElementById('contact-error').textContent='';
    document.getElementById('contact-dialog').showModal();
}
document.getElementById('contact-form').onsubmit=async event=>{
    event.preventDefault();const button=document.getElementById('contact-save');button.disabled=true;
    const id=document.getElementById('contact-id').value,email=document.getElementById('contact-email').value,phone=document.getElementById('contact-phone').value.trim();
    try {
        await fetchAPI('/users/'+id,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({email})});
        if(phone) await fetchAPI('/users/'+id+'/phone-numbers',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({phone_number:phone})});
        document.getElementById('contact-dialog').close();toast('Recovery contact saved.','success');loadUsers();
    } catch(e){document.getElementById('contact-error').textContent=e.data?.errors?Object.values(e.data.errors).flat().join(' '):e.message;}finally{button.disabled=false;}
};
loadUsers();
</script>


</body>
</html>
