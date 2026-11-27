const statusEl = document.getElementById('api-status');
const appEl = document.getElementById('app');

async function ping() {
    try {
        const res = await fetch('/api?action=ping', { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            statusEl.textContent = 'API OK';
            statusEl.className = 'badge ok';
            appEl.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        } else {
            statusEl.textContent = 'API error';
            statusEl.className = 'badge err';
            appEl.textContent = data.error ?? 'Unknown error';
        }
    } catch (err) {
        statusEl.textContent = 'offline';
        statusEl.className = 'badge err';
        appEl.textContent = err.message;
    }
}

ping();