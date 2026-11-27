import { $ } from './core/dom.js';
import { state, subscribe } from './core/state.js';
import { renderSidebar } from './ui/sidebar.js';
import { renderTabs } from './ui/tabs.js';
import { runDiscovery } from './features/discover.js';

// Wire render pipeline: any state change re-renders everything.
subscribe(() => {
    renderSidebar();
    renderTabs();
});

// Refresh button
$('#refresh-btn').addEventListener('click', () => runDiscovery(true));

// Bulk action buttons (wired later to real actions)
$('#bulk-commit').addEventListener('click', () => {
    alert(`Commit sur: ${[...state.selected].join(', ')}\n\n(fonctionnalité à venir)`);
});
$('#bulk-commit-push').addEventListener('click', () => {
    alert(`Commit & Push sur: ${[...state.selected].join(', ')}\n\n(fonctionnalité à venir)`);
});

// Boot
runDiscovery(false);