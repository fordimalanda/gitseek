<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GitSeek</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <header class="sidebar-header">
                <h1>GitSeek</h1>
                <span id="api-status" class="badge">…</span>
            </header>

            <div class="sidebar-toolbar">
                <span id="project-count" class="muted">—</span>
                <button id="refresh-btn" class="btn btn-ghost" title="Rescanner PROJECTS_ROOT">
                    ⟳ Refresh
                </button>
            </div>

            <div id="project-list" class="project-list">
                <p class="muted empty">Chargement…</p>
            </div>

            <footer class="sidebar-footer">
                <div class="bulk-actions">
                    <button id="bulk-commit" class="btn" disabled>Commit</button>
                    <button id="bulk-commit-push" class="btn btn-primary" disabled>Commit & Push</button>
                </div>
                <span id="selection-info" class="muted small">Aucune sélection</span>
            </footer>
        </aside>

        <main class="main">
            <nav id="tabs" class="tabs" role="tablist"></nav>
            <section id="tab-content" class="tab-content">
                <div class="placeholder">
                    <h2>Bienvenue dans GitSeek</h2>
                    <p class="muted">
                        Clique sur un projet dans la sidebar pour l'ouvrir.<br>
                        Coche plusieurs projets puis utilise les boutons du bas pour committer en masse.
                    </p>
                </div>
            </section>
        </main>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>