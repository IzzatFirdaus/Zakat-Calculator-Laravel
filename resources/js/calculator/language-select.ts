export function initLanguageSelect(): void {
    const form = document.querySelector<HTMLFormElement>('[data-language-form]');
    const select = form?.querySelector<HTMLSelectElement>('[data-language-select]');
    const submit = form?.querySelector<HTMLButtonElement>('[data-language-submit]');

    if (! form || ! select) {
        return;
    }

    // The submit button is the no-JS path; it is only safe to hide once the
    // change handler can carry the request instead.
    select.addEventListener('change', () => {
        form.requestSubmit();
    });

    if (submit) {
        submit.hidden = true;
    }
}
