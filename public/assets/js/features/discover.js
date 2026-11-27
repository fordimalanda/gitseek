import { call } from '../core/api.js';
import { state, emit } from '../core/state.js';
import { toast } from '../ui/toast.js';
import { $ } from '../core/dom.js';

export async function runDiscovery(refresh = false) {
    const statusEl = $('#api-status');
    state.loading = true;
    statusEl.textContent = 'scan…';
    statusEl.className = 'badge';

    try {
        const data = await call('discover', { refresh });
        state.projects = data.projects;
        statusEl.textContent = 'API OK';
        statusEl.className = 'badge ok';

        if (refresh) {
            toast(`${data.count} projet(s) scanné(s)`, 'ok');
        }
    } catch (err) {
        statusEl.textContent = 'API error';
        statusEl.className = 'badge err';
        toast(`Discovery: ${err.message}`, 'err', 6000);
    } finally {
        state.loading = false;
        emit();
    }
}