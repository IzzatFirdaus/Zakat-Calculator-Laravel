import { cloneTemplate } from './dom';
import { ErrorRenderer, type FieldErrors } from './error-renderer';

export function initCalculatorForm(): void {
    const form = document.querySelector<HTMLFormElement>('[data-calculator-form]');
    const resultRegion = document.querySelector<HTMLElement>('[data-calculator-result]');

    if (! form || ! resultRegion) {
        return;
    }

    const renderer = new ErrorRenderer(form);
    const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');
    const submitLabel = submitButton?.textContent ?? '';
    const prefix = document.querySelector<HTMLElement>('[data-currency-prefix]');
    const currencySelect = form.querySelector<HTMLSelectElement>('select[name="currency"]');
    const initialResultHtml = resultRegion.innerHTML;
    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        void submit(event);
    });

    currencySelect?.addEventListener('change', () => {
        if (prefix) {
            prefix.textContent = currencySelect.value;
        }
    });

    document.querySelector('[data-calculator-reset]')?.addEventListener('click', (event) => {
        event.preventDefault();
        renderer.clear();
        form.reset();
        resultRegion.innerHTML = initialResultHtml;

        if (prefix && currencySelect) {
            prefix.textContent = currencySelect.value;
        }
    });

    async function submit(event: Event): Promise<void> {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        setSubmitting(true);

        const previousResult = resultRegion.innerHTML;
        renderTemplate('[data-result-loading-template]');
        resultRegion.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: buildRequestBody(form),
            });

            if (response.ok) {
                renderer.clear();
                resultRegion.innerHTML = await response.text();
                handoff();
            } else if (response.status === 422) {
                renderer.render(await readValidationErrors(response), cloneTemplate('[data-validation-summary-template]'));
                resultRegion.innerHTML = previousResult;
            } else {
                renderTemplate('[data-result-unexpected-error-template]');
            }
        } catch {
            renderTemplate('[data-result-network-error-template]');
        } finally {
            isSubmitting = false;
            setSubmitting(false);
            resultRegion.setAttribute('aria-busy', 'false');
        }
    }

    function handoff(): void {
        const rect = resultRegion.getBoundingClientRect();

        if (rect.top < 0 || rect.bottom > window.innerHeight) {
            resultRegion.scrollIntoView({ block: 'nearest' });
        }

        resultRegion.querySelector<HTMLElement>('[data-result-heading]')?.focus();
    }

    function setSubmitting(isBusy: boolean): void {
        if (! submitButton) {
            return;
        }

        submitButton.disabled = isBusy;
        submitButton.textContent = isBusy
            ? submitButton.dataset.submittingText ?? submitLabel
            : submitButton.dataset.submitText ?? submitLabel;
    }

    function renderTemplate(selector: string): void {
        const node = cloneTemplate(selector);

        if (node) {
            resultRegion.replaceChildren(node);
        }
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

async function readValidationErrors(response: Response): Promise<FieldErrors> {
    const payload = await response.json() as { errors?: FieldErrors };

    return payload.errors ?? {};
}
