document.addEventListener('DOMContentLoaded', () => {
    initCalculatorForm();
    initThemeToggle();
});

function initCalculatorForm(): void {
    const form = document.querySelector<HTMLFormElement>('[data-calculator-form]');
    const resultRegion = document.querySelector<HTMLElement>('[data-calculator-result]');

    if (!form || !resultRegion) {
        return;
    }

    const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');
    const submitLabel = submitButton?.textContent ?? '';
    const templates = {
        loading: document.querySelector<HTMLTemplateElement>('[data-result-loading-template]'),
        networkError: document.querySelector<HTMLTemplateElement>('[data-result-network-error-template]'),
        unexpectedError: document.querySelector<HTMLTemplateElement>('[data-result-unexpected-error-template]'),
        validationSummary: document.querySelector<HTMLTemplateElement>('[data-validation-summary-template]'),
    };
    const initialDescriptions = snapshotDescriptions(form);

    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        void submit(event);
    });

    async function submit(event: Event): Promise<void> {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        setSubmitting(true);

        const previousResult = resultRegion.innerHTML;
        renderTemplate(templates.loading);
        resultRegion.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: buildRequestBody(form),
            });

            if (response.ok) {
                const html = await response.text();
                clearValidationErrors();
                resultRegion.innerHTML = html;
                resultRegion.querySelector<HTMLElement>('[data-result-heading]')?.focus();
            } else if (response.status === 422) {
                renderValidationErrors(await readValidationErrors(response));
                resultRegion.innerHTML = previousResult;
            } else {
                renderTemplate(templates.unexpectedError);
            }
        } catch {
            renderTemplate(templates.networkError);
        } finally {
            isSubmitting = false;
            setSubmitting(false);
            resultRegion.setAttribute('aria-busy', 'false');
        }
    }

    function setSubmitting(isBusy: boolean): void {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = isBusy;
        submitButton.textContent = isBusy
            ? submitButton.dataset.submittingText ?? submitLabel
            : submitButton.dataset.submitText ?? submitLabel;
    }

    function renderTemplate(template: HTMLTemplateElement | null): void {
        if (!template?.content.firstElementChild) {
            return;
        }

        resultRegion.replaceChildren(template.content.cloneNode(true));
    }

    function renderValidationErrors(errors: Record<string, string[]>): void {
        clearValidationErrors();

        const fields = Object.keys(errors);

        for (const field of fields) {
            markFieldInvalid(field, errors[field][0] ?? '');
        }

        const summary = templates.validationSummary?.content.firstElementChild?.cloneNode(true) as HTMLElement | undefined;
        const list = summary?.querySelector('ul');

        if (!summary || !list) {
            return;
        }

        for (const field of fields) {
            list.append(buildSummaryItem(field));
        }

        form.before(summary);
        summary.focus();
    }

    function markFieldInvalid(field: string, message: string): void {
        const container = form.querySelector<HTMLElement>(`[data-field="${field}"]`);

        if (!container) {
            return;
        }

        const control = container instanceof HTMLFieldSetElement
            ? container
            : container.querySelector<HTMLElement>('input, select, textarea');

        if (control) {
            const describedBy = new Set((control.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
            describedBy.add(`${field}-error`);
            control.setAttribute('aria-invalid', 'true');
            control.setAttribute('aria-describedby', [...describedBy].join(' '));
        }

        const error = document.createElement('p');
        error.id = `${field}-error`;
        error.className = 'field__error';
        error.setAttribute('role', 'alert');
        error.dataset.fieldError = '';
        error.textContent = message;

        container.append(error);
    }

    function buildSummaryItem(field: string): HTMLLIElement {
        const container = form.querySelector<HTMLElement>(`[data-field="${field}"]`);
        const label = container?.querySelector('legend, label')?.textContent?.trim() ?? field;

        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = `#${field}`;
        link.textContent = label;
        item.append(link);

        return item;
    }

    function clearValidationErrors(): void {
        document.querySelectorAll('[data-validation-summary]').forEach((element) => element.remove());
        form.querySelectorAll('[data-field-error]').forEach((element) => element.remove());

        for (const [element, describedBy] of initialDescriptions) {
            element.setAttribute('aria-describedby', describedBy);
        }

        form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
            if (!initialDescriptions.has(element)) {
                element.removeAttribute('aria-describedby');
            }
        });

        form.querySelectorAll<HTMLElement>('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    }
}

function buildRequestBody(form: HTMLFormElement): URLSearchParams {
    const body = new URLSearchParams();

    for (const [key, value] of new FormData(form)) {
        if (typeof value === 'string') {
            body.append(key, value);
        }
    }

    return body;
}

async function readValidationErrors(response: Response): Promise<Record<string, string[]>> {
    const payload = await response.json() as { errors?: Record<string, string[]> };

    return payload.errors ?? {};
}

function snapshotDescriptions(form: HTMLFormElement): Map<HTMLElement, string> {
    const descriptions = new Map<HTMLElement, string>();

    form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
        descriptions.set(element, element.getAttribute('aria-describedby') ?? '');
    });

    return descriptions;
}

function initThemeToggle(): void {
    const toggle = document.querySelector<HTMLButtonElement>('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    toggle.hidden = false;
    toggle.setAttribute('aria-pressed', String(document.documentElement.classList.contains('dark')));

    toggle.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        toggle.setAttribute('aria-pressed', String(isDark));
    });
}
