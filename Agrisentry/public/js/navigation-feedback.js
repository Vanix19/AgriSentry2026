(() => {
    const pending = new Map();
    document.addEventListener('submit', event => {
        // Existing AJAX handlers own their loading and error states.
        queueMicrotask(() => {
            if (event.defaultPrevented || event.target.id === 'otp-form') return;
            const button = event.submitter;
            if (!button || button.name) return;
            pending.set(button, button.textContent);
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Please wait…';
        });
    });
    window.addEventListener('pageshow', () => {
        pending.forEach((label, button) => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.textContent = label;
        });
        pending.clear();
    });
})();
