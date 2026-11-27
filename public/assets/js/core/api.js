import { $ } from './core/dom.js';
import { state, subscribe } from './core/state.js';
import { renderSidebar } from './ui/sidebar.js';
import { renderTabs } from './ui/tabs.js';
import { runDiscovery } from './features/discover.js';
import { call } from './core/api.js';
import { toast } from './ui/toast.js';
import { invalidateRepoView } from './features/repo-view.js';

// Wire render pipeline
subscribe(() => {
    renderSidebar();
    renderTabs();
});

// Refresh button
$('#refresh-btn').addEventListener('click', () => runDiscovery(true));

// Fonction pour committer les projets sélectionnés
async function doCommit(push) {
    const projects = [...state.selected];
    if (projects.length === 0) return;

    const btnCommit = $('#bulk-commit');
    const btnPush = $('#bulk-commit-push');
    
    // Désactiver les boutons pendant l'opération
    btnCommit.disabled = true;
    btnPush.disabled = true;
    
    toast(`Commit de ${projects.length} projet(s) en cours...`, 'warn');

    // On exécute les requêtes en parallèle (Promise.all)
    const results = await Promise.allSettled(
        projects.map(p => call('commit', { project: p, push }))
    );

    let successCount = 0;
    let errors = [];

    results.forEach((res, i) => {
        if (res.status === 'fulfilled') {
            successCount++;
        } else {
            errors.push(`${projects[i]} : ${res.reason.message}`);
        }
    });

    if (successCount > 0) {
        toast(`✅ ${successCount} projet(s) commité(s) avec succès.`, 'ok');
        state.selected.clear();
        
        // On force le rechargement de la vue (pour effacer le diff)
        invalidateRepoView();
        
        // On rescanne les projets pour mettre à jour les badges (dirty -> clean)
        await runDiscovery(true); 
    }
    
    if (errors.length > 0) {
        toast(`❌ Erreurs : ${errors.join(' | ')}`, 'err', 8000);
    }

    // Réactiver les boutons
    btnCommit.disabled = false;
    btnPush.disabled = false;
}

// Bind des boutons
$('#bulk-commit').addEventListener('click', () => doCommit(false));
$('#bulk-commit-push').addEventListener('click', () => doCommit(true));

// Boot
runDiscovery(false);