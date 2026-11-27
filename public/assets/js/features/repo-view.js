import { call } from '../core/api.js';
import { el } from '../core/dom.js';
import { toast } from '../ui/toast.js';

let currentProject = null;
let isLoading = false;

export async function renderRepoView(container, projectName) {
    if (currentProject === projectName && container.children.length > 0) return; // déjà rendu

    currentProject = projectName;
    isLoading = true;
    container.innerHTML = '<p class="muted">Chargement du statut...</p>';

    try {
        const data = await call('repo-state', { project: projectName });
        isLoading = false;
        
        // Si l'utilisateur a changé d'onglet pendant le chargement, on annule
        if (currentProject !== projectName) return;

        renderView(container, data);
    } catch (err) {
        isLoading = false;
        toast(`Erreur: ${err.message}`, 'err');
        container.innerHTML = `<p class="muted" style="color:var(--err)">❌ ${err.message}</p>`;
    }
}

export function invalidateRepoView() {
    currentProject = null;
}

function renderView(container, data) {
    container.innerHTML = '';

    const wrapper = el('div', { class: 'repo-view' });

    // Header
    wrapper.append(
        el('div', { class: 'repo-header' },
            el('h2', {}, data.project),
            el('div', { class: 'muted small' },
                `Branche : `, el('span', { class: 'branch' }, data.branch || '?')
            )
        )
    );

    // Fichiers modifiés
    const files = Object.entries(data.status);
    if (files.length > 0) {
        const list = el('div', { class: 'file-list' });
        for (const [path, code] of files) {
            const type = getFileType(code);
            list.append(
                el('div', { class: 'file-item' },
                    el('span', { class: `file-status ${type.class}` }, type.label),
                    el('span', {}, path)
                )
            );
        }
        wrapper.append(el('h3', { class: 'small muted' }, 'Fichiers modifiés'), list);
    }

    // Diff
    if (data.diff && data.diff.trim() !== '') {
        const diffBox = el('div', { class: 'diff-view' });
        for (const line of data.diff.split('\n')) {
            let cls = 'diff-line';
            if (line.startsWith('+++') || line.startsWith('---')) cls += ' hdr';
            else if (line.startsWith('@@')) cls += ' hdr';
            else if (line.startsWith('+')) cls += ' add';
            else if (line.startsWith('-')) cls += ' del';
            
            // On échappe le HTML basique
            const safeLine = line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            diffBox.append(el('div', { class: cls }, safeLine || ' '));
        }
        wrapper.append(el('h3', { class: 'small muted' }, 'Modifications'), diffBox);
    } else {
        wrapper.append(
            el('div', { class: 'empty-diff' }, 'Aucune modification en attente.')
        );
    }

    container.append(wrapper);
}

function getFileType(code) {
    if (code.includes('A')) return { label: 'A', class: 'add' };
    if (code.includes('D')) return { label: 'D', class: 'del' };
    if (code.includes('?')) return { label: '?', class: 'unt' };
    return { label: 'M', class: 'mod' };
}