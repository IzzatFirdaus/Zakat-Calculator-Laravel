export function cloneTemplate(selector: string): HTMLElement | null {
    const template = document.querySelector<HTMLTemplateElement>(selector);
    const child = template?.content.firstElementChild;

    return child ? (child.cloneNode(true) as HTMLElement) : null;
}
