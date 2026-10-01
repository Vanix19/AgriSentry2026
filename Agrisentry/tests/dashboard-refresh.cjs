const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const template = fs.readFileSync(require('node:path').join(__dirname, '../resources/views/agrisentry.blade.php'), 'utf8');
const requestCode = template.slice(template.indexOf('async function fetchAPI('), template.indexOf('const FEATURE_PERMISSIONS ='));
const refreshCode = template.slice(template.indexOf('let dataLoadInProgress ='), template.indexOf('/* ============ TEMPERATURE / STATUS HELPERS'));
const response = (status, body) => ({status, ok: status >= 200 && status < 300, json: async () => { if (body === null) throw Error('HTML gateway error'); return body; }});

(async () => {
    let calls = 0;
    const context = vm.createContext({API_BASE: '/api', CSRF_TOKEN: 'test', AbortSignal,
        setTimeout: callback => callback(), window: {location: {reload() {}}},
        fetch: async () => ++calls === 1 ? response(502, null) : response(200, {goats: []})});
    vm.runInContext(requestCode, context);
    assert.equal(JSON.stringify(await vm.runInContext("fetchAPI('/goats')", context)), '{"goats":[]}');
    assert.equal(calls, 2);
    calls = 0;
    context.fetch = async () => { calls++; return response(502, null); };
    await assert.rejects(vm.runInContext("fetchAPI('/medical-records', {method: 'POST'})", context), /temporarily unavailable/);
    assert.equal(calls, 1, 'Never replay a write after a gateway failure');
    calls = 0;
    context.fetch = async () => { calls++; return response(401, {message: 'Unauthenticated'}); };
    await assert.rejects(vm.runInContext("fetchAPI('/goats')", context), /Session expired/);
    assert.equal(calls, 1);
    assert.equal(context.window.location.href, '/login');

    let active = 0, peak = 0, error = '';
    const syncLabel = {};
    const state = vm.createContext({
        goats: [{id: 1}], healthLogs: [{id: 5, goat_id: 1}], alerts: [], medicalRecords: [{id: 1}], collars: [{id: 1}],
        FEATURE_PERMISSIONS: {'goats.read': true},
        allowedRead: async feature => {
            peak = Math.max(peak, ++active);
            await new Promise(resolve => setImmediate(resolve));
            active--;
            if (feature === 'goats' || feature === 'health-logs') throw Error('Gateway unavailable');
            return feature === 'medical-records' ? {medical_records: [{id: 2}]} : {};
        },
        setError: message => {error = message;}, clearError() {error = '';}, updateStats() {},
        renderDashboardGoats() {}, renderAnimals() {}, populateGoatSelect() {}, renderHealthLogs() {},
        renderAlerts() {}, renderMedicalRecords() {}, renderCollars() {},
        document: {getElementById: () => syncLabel},
    });
    vm.runInContext(refreshCode, state);
    await vm.runInContext('loadData()', state);
    assert.ok(peak <= 2);
    assert.equal(state.goats[0].id, 1);
    assert.equal(state.healthLogs[0].id, 5);
    assert.equal(state.medicalRecords[0].id, 2);
    assert.match(error, /animals, health logs/);
    assert.match(syncLabel.textContent, /Partial refresh/);
    console.log('Passed: read retries, no write replay, login redirect, partial refresh preservation, two-request limit.');
})().catch(error => {console.error(error); process.exitCode = 1;});
