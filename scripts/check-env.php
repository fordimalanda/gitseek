<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use GitSeek\Support\Env;

$keys = [
    'PROJECTS_ROOT',
    'GITHUB_TOKEN',
    'GITHUB_DEFAULT_BRANCH',
    'DEEPSEEK_API_KEY',
    'DEEPSEEK_MODEL',
    'CACHE_TTL',
    'LOG_LEVEL',
];

$hasError = false;

foreach ($keys as $key) {
    $value = Env::get($key);

    if ($value === null || $value === '') {
        echo "[MISSING] {$key}\n";
        $hasError = true;
        continue;
    }

    // Mask secrets
    if (in_array($key, ['GITHUB_TOKEN', 'DEEPSEEK_API_KEY'], true)) {
        $preview = substr((string) $value, 0, 8) . '…' . substr((string) $value, -4);
        echo "[OK] {$key} = {$preview}\n";
    } else {
        echo "[OK] {$key} = {$value}\n";
    }
}

// Extra checks
$root = Env::get('PROJECTS_ROOT');
if ($root && !is_dir($root)) {
    echo "[ERROR] PROJECTS_ROOT n'existe pas : {$root}\n";
    $hasError = true;
} elseif ($root) {
    $count = count(glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: []);
    echo "[INFO] {$count} sous-dossier(s) détecté(s) dans PROJECTS_ROOT\n";
}

$token = (string) Env::get('GITHUB_TOKEN', '');
if ($token && !str_starts_with($token, 'ghp_') && !str_starts_with($token, 'github_pat_')) {
    echo "[WARN] GITHUB_TOKEN ne commence pas par 'ghp_' ou 'github_pat_'\n";
}

$dsKey = (string) Env::get('DEEPSEEK_API_KEY', '');
if ($dsKey && !str_starts_with($dsKey, 'sk-')) {
    echo "[WARN] DEEPSEEK_API_KEY ne commence pas par 'sk-'\n";
}

echo $hasError ? "\n❌ Des erreurs subsistent.\n" : "\n✅ Config OK.\n";
exit($hasError ? 1 : 0);