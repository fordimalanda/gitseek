export const $  = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

export function el(tag, attrs = {}, ...children) {
    const node = document.createElement(tag);

    for (const [key, value] of Object.entries(attrs)) {
        if (key === 'class')      node.className = value;
        else if (key === 'dataset') Object.assign(node.dataset, value);
        else if (key.startsWith('on') && typeof value === 'function') {
            node.addEventListener(key.slice(2).toLowerCase(), value);
        }
        else if (value !== false && value !== null && value !== undefined) {
            node.setAttribute(key, value === true ? '' : value);
        }
    }

    for (const child of children) {
        if (child == null) continue;
        node.append(child instanceof Node ? child : document.createTextNode(child));
    }

    return node;
}

export function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
}