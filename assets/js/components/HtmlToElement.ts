/**
* @packageDocumentation
* @module Components
*/

export default function HtmlToElement(html: string): HTMLElement {
    const template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;
    return template.content.firstChild as HTMLElement;
};
