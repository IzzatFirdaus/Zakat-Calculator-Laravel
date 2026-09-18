export function initThemeToggle(): void {
    const toggle = document.querySelector<HTMLButtonElement>('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    toggle.hidden = false;
    toggle.setAttribute('aria-pressed', String(document.documentElement.classList.contains('dark')));

    toggle.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        toggle.setAttribute('aria-pressed', String(isDark));
    });
}
