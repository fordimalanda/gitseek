import { $, el, clear } from '../core/dom.js';
import { state, emit } from '../core/state.js';

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
    clear(content);

    if (!state.activeTab) {
        content.append(
            el('div', { class: 'placeholder' },
                el('h2', {}, 'Bienvenue dans GitSeek'),
                el('p', { class: 'muted' },
                    'Clique sur un projet dans la sidebar pour l\'ouvrir.',
                ),
            ),
        );
        return;
    }

    const project = state.projects.find((p) => p.name === state.activeTab);
    if (!project) {
        content.append(el('p', { class: 'muted' }, 'Projet introuvable.'));
        return;
    }

    content.append(
        el('div', {},
            el('h2', {}, project.name),
            el('p', { class: 'muted' }, project.path),
            el('hr', { style: 'border-color: var(--border); margin: 20px 0;' }),
            el('p', { class: 'muted' },
                'Vue détaillée (status, diff, commit) à venir à l\'étape suivante.',
            ),
        ),
    );
}