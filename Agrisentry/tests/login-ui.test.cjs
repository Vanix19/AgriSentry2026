const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../public/js/login.js'), 'utf8');

function setup(fetch) {
    const button = { textContent: 'Continue', disabled: false };
    const feedback = { hidden: true, textContent: '' };
    let submit, timeout, redirect;
    const form = {
        action: '/login', querySelector: () => button,
        addEventListener: (_, handler) => { submit = handler; },
        setAttribute() {}, removeAttribute() {},
    };
    vm.runInNewContext(source, {
        document: { getElementById: id => id === 'login-form' ? form : feedback },
        window: { fetch, AbortController, location: { assign: url => { redirect = url; } }, addEventListener() {} },
        fetch, AbortController, FormData: class {},
        setTimeout: handler => { timeout = handler; return 1; }, clearTimeout() {},
    });
    return { button, feedback, submit: () => submit({ preventDefault() {} }), expire: () => timeout(), redirect: () => redirect };
}

test('login shows progress immediately and prevents duplicate submissions', async () => {
    let resolve, requests = 0;
    const ui = setup(() => { requests++; return new Promise(done => { resolve = done; }); });
    const first = ui.submit();
    assert.equal(ui.button.disabled, true);
    assert.match(ui.button.textContent, /Sending/);
    await ui.submit();
    assert.equal(requests, 1);
    resolve({ ok: true, json: async () => ({ redirect: '/login/otp' }) });
    await first;
    assert.equal(ui.redirect(), '/login/otp');
});

test('validation failures enable correction and retry', async () => {
    const ui = setup(async () => ({ ok: false, status: 422, json: async () => ({ errors: { username: ['Invalid username or password.'] } }) }));
    await ui.submit();
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.feedback.textContent, 'Invalid username or password.');
    assert.equal(ui.redirect(), undefined);
});

test('stalled requests time out and restore Continue', async () => {
    const ui = setup((_, options) => new Promise((resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(new Error('aborted')));
    }));
    const request = ui.submit();
    ui.expire();
    await request;
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.button.textContent, 'Continue');
    assert.match(ui.feedback.textContent, /took too long/);
});
