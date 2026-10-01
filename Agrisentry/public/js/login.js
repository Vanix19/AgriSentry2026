(() => {
    const form = document.getElementById('login-form');
    if (!form || !window.fetch || !window.AbortController) return;
    const button = form.querySelector('button[type="submit"]');
    const feedback = document.getElementById('login-feedback');
    let pending = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending) return;
        pending = true;
        button.disabled = true;
        button.textContent = 'Sending verification code…';
        form.setAttribute('aria-busy', 'true');
        feedback.hidden = false;
        feedback.textContent = 'Checking your account and sending your OTP. Please wait.';
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 25000);
        let navigating = false;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), signal: controller.signal,
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.redirect) {
                const message = response.status === 419 ? 'Your session expired. Refresh this page and try again.'
                    : response.status === 429 ? 'Too many attempts. Please wait a minute before trying again.'
                    : response.status >= 500 ? 'The server is temporarily unavailable. Please try again shortly.'
                    : Object.values(data?.errors || {}).flat()[0] || 'Could not sign in. Please try again.';
                throw new Error(message);
            }
            feedback.textContent = 'Code sent. Opening verification…';
            window.location.assign(data.redirect);
            navigating = true;
        } catch (error) {
            feedback.textContent = controller.signal.aborted
                ? 'The server took too long. An OTP may still arrive; use the latest code after trying again.'
                : error.message || 'Could not reach the server. Please try again.';
        } finally {
            clearTimeout(timer);
            if (!navigating) {
                pending = false;
                button.disabled = false;
                button.textContent = 'Continue';
                form.removeAttribute('aria-busy');
            }
        }
    });
    window.addEventListener('pageshow', () => {
        pending = false;
        button.disabled = false;
        button.textContent = 'Continue';
        form.removeAttribute('aria-busy');
        feedback.hidden = true;
    });
})();
