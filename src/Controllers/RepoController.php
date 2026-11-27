<?php
declare(strict_types=1);

namespace GitSeek\Controllers;

use GitSeek\Git\Repository;
use GitSeek\Support\Env;
use GitSeek\Support\Response;

final class RepoController
{
    public function state(array $payload): void
    {
        $name = $payload['project'] ?? '';
        if ($name === '') {
            Response::error('Paramètre "project" manquant.', 400);
        }

        try {
            $path = $this->resolvePath($name);
            $repo = new Repository($path);

            Response::ok([
                'project' => $name,
                'branch'  => $repo->branch(),
                'status'  => $repo->statusPorcelain(),
                'diff'    => $repo->diff(),
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function resolvePath(string $name): string
    {
        $root = (string) Env::get('PROJECTS_ROOT', '');
        $root = rtrim($root, "\\/");
        $path = $root . DIRECTORY_SEPARATOR . $name;

        if (!is_dir($path) || !is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            throw new \RuntimeException("Projet introuvable ou invalide : {$name}");
        }

        return $path;
    }
}