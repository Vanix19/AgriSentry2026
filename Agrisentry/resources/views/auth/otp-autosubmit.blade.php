<script>
(() => {
    const form = document.getElementById('otp-form');
    const input = document.getElementById('otp');
    if (!form || !input) return;
    let submitting = false;
    const button = form.querySelector('button');
    const originalLabel = button.textContent;
    form.addEventListener('submit', (event) => {
        if (submitting) { event.preventDefault(); return; }
        submitting = true;
        button.textContent = 'Verifying...';
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');
    });
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, 6);
        if (input.value.length === 6 && !submitting) form.requestSubmit();
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        button.disabled = false;
        button.textContent = originalLabel;
        form.removeAttribute('aria-busy');
    });
})();
</script>
