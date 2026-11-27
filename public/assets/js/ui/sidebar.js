import { $, el, clear } from '../core/dom.js';
import { state, emit } from '../core/state.js';
import { openTab } from './tabs.js';

export function renderSidebar() {
    const list = $('#project-list');
    clear(list);

    if (state.projects.length === 0) {
        list.append(el('p', { class: 'muted empty' }, 'Aucun projet trouvé dans PROJECTS_ROOT.'));
        updateBulkBar();
        return;
    }

    for (const project of state.projects) {
        list.append(renderProject(project));
    }

    updateBulkBar();
}

function renderProject(project) {
    const isActive = state.activeTab === project.name;
    const isSelected = state.selected.has(project.name);

    const checkbox = el('input', {
        type: 'checkbox',
        checked: isSelected,
        onclick: (e) => {
            e.stopPropagation();
            toggleSelection(project.name);
        },
    });

    const branchBadge = project.branch
        ? el('span', { class: 'branch' }, project.branch)
        : el('span', { class: 'branch' }, '?');

    const stateBadge = describeState(project);

    const item = el('div', {
        class: `project-item ${isActive ? 'active' : ''}`,
        dataset: { project: project.name },
        onclick: () => openTab(project.name),
    },
        checkbox,
        el('div', { class: 'project-body' },
            el('div', { class: 'project-name', title: project.path }, project.name),
            el('div', { class: 'project-meta' }, branchBadge, stateBadge),
        ),
        el('span', {}),
    );

    return item;
}

function describeState(project) {
    if (project.error) {
        return el('span', { class: 'project-state', style: 'color: var(--err)' }, '❌ erreur');
    }
    if (project.dirty > 0) {
        return el('span', { class: 'project-state', style: 'color: var(--err)' }, `● ${project.dirty}`);
    }
    if (project.untracked > 0) {
        return el('span', { class: 'project-state', style: 'color: var(--warn)' }, `● ${project.untracked} non-track`);
    }
    if (project.ahead > 0) {
        return el('span', { class: 'project-state', style: 'color: var(--accent)' }, `↑${project.ahead}`);
    }
    return el('span', { class: 'project-state', style: 'color: var(--ok)' }, '🟢');
}

function toggleSelection(name) {
    if (state.selected.has(name)) state.selected.delete(name);
    else state.selected.add(name);
    emit();
}

export function updateBulkBar() {
    const count = state.selected.size;
    const commitBtn = $('#bulk-commit');
    const pushBtn   = $('#bulk-commit-push');
    const info      = $('#selection-info');

    commitBtn.disabled = count === 0;
    pushBtn.disabled   = count === 0;
    info.textContent   = count === 0
        ? 'Aucune sélection'
        : `${count} projet${count > 1 ? 's' : ''} sélectionné${count > 1 ? 's' : ''}`;

    $('#project-count').textContent =
        state.projects.length + ' projet' + (state.projects.length > 1 ? 's' : '');
}