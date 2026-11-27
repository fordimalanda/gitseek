const listeners = new Set();

export const state = {
    projects: [],
    selected: new Set(),
    openTabs: [],
    activeTab: null,
    loading: false,
};

export function subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
}

export function emit() {
    for (const fn of listeners) fn(state);
}