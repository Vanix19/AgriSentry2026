@extends('auth.account-layout')
@section('content')
<h1>Access control &amp; activity logs</h1>
<p>Choose which features Cooperative Staff and Caretakers can view and change.</p>
<p id="status" role="status"></p><div id="permissions"></div>
<h2>User activity</h2><label for="user-filter">User</label><select id="user-filter"><option value="">All users</option></select>
<div class="table-wrap"><table><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record / path</th><th>IP address</th></tr></thead><tbody id="logs"></tbody></table></div>
<button class="btn" id="previous">Previous</button> <span id="page"></span> <button class="btn" id="next">Next</button>
<script>
const featureLabels = {dashboard:'Dashboard',goats:'Goat records','health-logs':'Health logs',alerts:'Alerts','medical-records':'Medical records',collars:'Collar management',reports:'Report analytics','gemini-advice':'AI veterinary advice'};
const statusEl = document.getElementById('status');
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(path, options = {}) {
    const response = await fetch('/api/'+path, {...options, headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}});
    const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Request failed'); return data;
}
async function loadPermissions() {
    const data = await api('access-control');
    for (const [role, permissions] of Object.entries(data.roles)) {
        const form = document.createElement('form');
        form.innerHTML = `<h2>${role === 'Staff' ? 'Cooperative Staff' : 'Caretaker'}</h2>` + data.features.map(feature => `<fieldset><legend>${esc(featureLabels[feature] || feature)}</legend>${(['dashboard','reports'].includes(feature)?['read']:['read','write']).map(action => `<label><input type="checkbox" name="${feature}.${action}" ${permissions[feature+'.'+action] ? 'checked' : ''}> ${action === 'read' ? 'View' : 'Create / change'}</label>`).join('')}</fieldset>`).join('') + '<button class="btn btn-primary">Save permissions</button>';
        form.onsubmit = async event => {
            event.preventDefault(); const button = form.querySelector('button'); button.disabled = true;
            try { const permissions = {...data.roles[role], ...Object.fromEntries([...form.querySelectorAll('input')].map(input => [input.name,input.checked]))}; await api('access-control',{method:'PUT',body:JSON.stringify({role,permissions})}); statusEl.textContent='Permissions saved.'; await loadLogs(); }
            catch(e) {statusEl.textContent=e.message;} finally {button.disabled=false;}
        };
        document.getElementById('permissions').appendChild(form);
    }
}
let page=1;
async function loadLogs() {
    try {
        const data=await api('activity-logs?page='+page+'&user_id='+encodeURIComponent(document.getElementById('user-filter').value));
        document.getElementById('logs').innerHTML=data.data.map(log=>`<tr><td>${esc(log.created_at)}</td><td>${esc(log.username)}</td><td>${esc(log.action)}</td><td>${esc(log.path)}</td><td>${esc(log.ip_address)}</td></tr>`).join('') || '<tr><td colspan="5">No activity recorded yet.</td></tr>';
        document.getElementById('page').textContent=`Page ${data.current_page} of ${data.last_page}`;
        document.getElementById('previous').disabled=page<=1; document.getElementById('next').disabled=!data.next_page_url;
    } catch(e) {statusEl.textContent=e.message;}
}
document.getElementById('previous').onclick=()=>{page--;loadLogs();}; document.getElementById('next').onclick=()=>{page++;loadLogs();};
document.getElementById('user-filter').onchange=()=>{page=1;loadLogs();};
api('users').then(data=>{for(const user of data.users) document.getElementById('user-filter').add(new Option(user.name+' ('+user.username+')',user.id));}).catch(e=>statusEl.textContent=e.message);
loadPermissions().catch(e=>statusEl.textContent=e.message);loadLogs();
</script>
@endsection
