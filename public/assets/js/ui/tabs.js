import { $, el, clear } from '../core/dom.js';
import { state, emit } from '../core/state.js';
import { renderRepoView } from '../features/repo-view.js';

export function openTab(name) {
    if (!state.openTabs.includes(name)) {
        state.openTabs.push(name);
    }
    state.activeTab = name;
    emit();
}

export function closeTab(name) {
    state.openTabs = state.openTabs.filter((n) => n !== name);
    if (state.activeTab === name) {
        state.activeTab = state.openTabs[state.openTabs.length - 1] ?? null;
    }
    emit();
}

export function renderTabs() {
    const nav = $('#tabs');
    clear(nav);

    for (const name of state.openTabs) {
        const isActive = name === state.activeTab;

        const closeBtn = el('button', {
            class: 'tab-close',
            title: 'Fermer',
            onclick: (e) => { e.stopPropagation(); closeTab(name); },
        }, '×');

        nav.append(
            el('button', {
                class: `tab ${isActive ? 'active' : ''}`,
                onclick: () => openTab(name),
            }, name, closeBtn),
        );
    }

    renderContent();
}

function renderContent() {
    const content = $('#tab-content');

    // Si aucun onglet n'est actif
    if (!state.activeTab) {
        if (content.dataset.activeTab !== '') {
            clear(content);
            content.append(
                el('div', { class: 'placeholder' },
                    el('h2', {}, 'Bienvenue dans GitSeek'),
                    el('p', { class: 'muted' },
                        'Clique sur un projet dans la sidebar pour l\'ouvrir.',
                    ),
                ),
            );
            content.dataset.activeTab = '';
        }
        return;
    }

    // Si l'onglet est actif mais que le projet n'existe plus (supprimé ou erreur)
    const project = state.projects.find((p) => p.name === state.activeTab);
    if (!project) {
        if (content.dataset.activeTab !== 'not-found') {
            clear(content);
            content.append(el('p', { class: 'muted' }, 'Projet introuvable.'));
            content.dataset.activeTab = 'not-found';
        }
        return;
    }

    // On ne recharge la vue QUE si on a changé d'onglet
    if (content.dataset.activeTab !== state.activeTab) {
        clear(content);
        content.dataset.activeTab = state.activeTab; // Marque l'onglet actuellement affiché
        renderRepoView(content, project.name);       // Délègue le rendu au module repo-view
    }
}