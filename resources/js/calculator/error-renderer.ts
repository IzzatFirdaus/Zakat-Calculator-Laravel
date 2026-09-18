export type FieldErrors = Record<string, string[]>;

const PLAUSIBLE_DECIMAL = /^\d+([.,]\d{1,2})?$/;

export class ErrorRenderer {
    private readonly form: HTMLFormElement;
    private readonly initialDescriptions = new Map<HTMLElement, string>();

    constructor(form: HTMLFormElement) {
        this.form = form;
        form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
            this.initialDescriptions.set(element, element.getAttribute('aria-describedby') ?? '');
        });
    }

    render(errors: FieldErrors, summaryTemplate: HTMLElement | null): void {
        this.clear();

        for (const [field, messages] of Object.entries(errors)) {
            this.markInvalid(field, messages[0] ?? '');
        }

        const summary = summaryTemplate;
        const list = summary?.querySelector('ul');

        if (! summary || ! list) {
            return;
        }

        for (const field of Object.keys(errors)) {
            list.append(this.summaryItem(field));
        }

        this.form.before(summary);
        summary.focus();
    }

    clear(): void {
        document.querySelectorAll('[data-validation-summary]').forEach((element) => element.remove());
        this.form.querySelectorAll('[data-field-error]').forEach((element) => element.remove());

        for (const [element, describedBy] of this.initialDescriptions) {
            element.setAttribute('aria-describedby', describedBy);
        }

        this.form.querySelectorAll<HTMLElement>('[aria-describedby]').forEach((element) => {
            if (! this.initialDescriptions.has(element)) {
                element.removeAttribute('aria-describedby');
            }
        });

        this.form.querySelectorAll<HTMLElement>('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    }

    private controlFor(container: HTMLElement): HTMLElement | null {
        return container instanceof HTMLFieldSetElement
            ? container.querySelector<HTMLElement>('input')
            : container.querySelector<HTMLElement>('input, select, textarea');
    }

    private markInvalid(field: string, message: string): void {
        const container = this.form.querySelector<HTMLElement>(`[data-field="${field}"]`);

        if (! container) {
            return;
        }

        const control = this.controlFor(container);

        if (control) {
            const describedBy = new Set((control.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean));
            describedBy.add(`${field}-error`);
            control.setAttribute('aria-invalid', 'true');
            control.setAttribute('aria-describedby', [...describedBy].join(' '));
            control.addEventListener('input', () => this.dismissIfPlausible(field, control));
        }

        const error = document.createElement('p');
        error.id = `${field}-error`;
        error.className = 'field__error';
        error.setAttribute('role', 'alert');
        error.dataset.fieldError = '';
        error.textContent = message;
        container.append(error);
    }

    private dismissIfPlausible(field: string, control: HTMLElement): void {
        const input = control instanceof HTMLInputElement ? control : null;

        if (! input || ! PLAUSIBLE_DECIMAL.test(input.value.trim())) {
            return;
        }

        document.getElementById(`${field}-error`)?.remove();
        control.removeAttribute('aria-invalid');
        const describedBy = (control.getAttribute('aria-describedby') ?? '')
            .split(/\s+/)
            .filter((id) => id !== `${field}-error`)
            .join(' ');
        describedBy ? control.setAttribute('aria-describedby', describedBy) : control.removeAttribute('aria-describedby');
    }

    private summaryItem(field: string): HTMLLIElement {
        const container = this.form.querySelector<HTMLElement>(`[data-field="${field}"]`);
        const label = container?.querySelector('legend, label')?.textContent?.trim() ?? field;
        const link = document.createElement('a');
        link.href = `#${field}`;
        link.textContent = label;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const control = container ? this.controlFor(container) : null;
            (control ?? container)?.focus();
        });

        const item = document.createElement('li');
        item.append(link);

        return item;
    }
}
