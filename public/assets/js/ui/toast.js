const container = () => document.getElementById('toast-container');

export function toast(message, kind = 'ok', duration = 3500) {
    const node = document.createElement('div');
    node.className = `toast ${kind}`;
    node.textContent = message;
    container().appendChild(node);

    setTimeout(() => {
        node.style.opacity = '0';
        node.style.transition = 'opacity 0.2s';
        setTimeout(() => node.remove(), 200);
    }, duration);
}