<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../src/bootstrap.php';

use GitSeek\Controllers\RepoController;

echo "--- Test de RepoController ---\n";

try {
    $controller = new RepoController();
    $controller->state(['project' => 'demo-projet']);
} catch (\Throwable $e) {
    echo "ERREUR FATALE : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . " ligne " . $e->getLine() . "\n";
}