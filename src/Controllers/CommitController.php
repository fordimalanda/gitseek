<?php
declare(strict_types=1);

namespace GitSeek\Controllers;

use GitSeek\Git\Repository;
use GitSeek\Git\Runner;
use GitSeek\Support\Env;
use GitSeek\Support\Response;

final class CommitController
{
    public function commit(array $payload): void
    {
        $name    = $payload['project'] ?? '';
        $message = trim($payload['message'] ?? 'Mise à jour via GitSeek');
        $push    = (bool) ($payload['push'] ?? false);

        if ($name === '') Response::error('Paramètre "project" manquant.', 400);

        try {
            $root = rtrim((string) Env::get('PROJECTS_ROOT'), "\\/");
            $path = $root . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
                throw new \RuntimeException("Projet invalide : {$name}");
            }

            // 1. Git Add
            $add = Runner::git(['add', '.'], $path);
            if (!$add['success']) throw new \RuntimeException("git add échoué : " . $add['stderr']);

            // 2. Git Commit
            $commit = Runner::git(['commit', '-m', $message], $path);
            // Si rien à committer, on ne considère pas ça comme une erreur fatale
            if (!$commit['success'] && !str_contains($commit['stdout'] . $commit['stderr'], 'nothing to commit')) {
                throw new \RuntimeException("git commit échoué : " . $commit['stderr']);
            }

            $output = trim($commit['stdout']);

            // 3. Git Push (optionnel)
            if ($push) {
                $pushRes = Runner::git(['push'], $path, Runner::PUSH_TIMEOUT);
                if (!$pushRes['success']) {
                    throw new \RuntimeException("git push échoué (vérifie tes droits/remote) : " . $pushRes['stderr']);
                }
                $output .= "\n" . trim($pushRes['stdout']);
            }

            Response::ok(['message' => 'Succès', 'details' => $output]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}