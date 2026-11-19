document.querySelectorAll('[data-reveal]').forEach((button) => {
    const input = document.getElementById(button.dataset.reveal);
    button.hidden = false;
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(show));
    });
});

// Revalidate private documents restored from browser history after a sign-out.
if (document.body.hasAttribute('data-private')) {
    window.addEventListener('pagehide', () => { document.documentElement.hidden = true; });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
        else document.documentElement.hidden = false;
    });
}
