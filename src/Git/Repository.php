<?php
declare(strict_types=1);

namespace GitSeek\Git;

final class Repository
{
    public function __construct(public readonly string $path)
    {
        if (!is_dir($this->path)) {
            throw new \RuntimeException("Repository path not found: {$this->path}");
        }
        if (!is_dir($this->path . DIRECTORY_SEPARATOR . '.git')) {
            throw new \RuntimeException("Not a git repository: {$this->path}");
        }
    }

    public function branch(): ?string
    {
        $r = Runner::git(['rev-parse', '--abbrev-ref', 'HEAD'], $this->path);
        return $r['success'] ? trim($r['stdout']) : null;
    }

    public function isDetached(): bool
    {
        return $this->branch() === 'HEAD';
    }

    /** @return array{owner: string, repo: string, url: string}|null */
    public function remote(): ?array
    {
        $r = Runner::git(['remote', 'get-url', 'origin'], $this->path);
        if (!$r['success'] || trim($r['stdout']) === '') {
            return null;
        }
        return self::parseRemote(trim($r['stdout']));
    }

    /** @return array<string, string> status code => path */
    public function statusPorcelain(): array
    {
        $r = Runner::git(['status', '--porcelain=v1'], $this->path);
        if (!$r['success']) {
            return [];
        }

        $result = [];
        foreach (explode("\n", $r['stdout']) as $line) {
            if (trim($line) === '') continue;
            $code = substr($line, 0, 2);
            $path = substr($line, 3);
            $result[$path] = $code;
        }
        return $result;
    }

    /** @return array{ahead: int, behind: int, hasUpstream: bool} */
    public function aheadBehind(): array
    {
        $r = Runner::git(['rev-parse', '--abbrev-ref', '@{u}'], $this->path);
        if (!$r['success']) {
            return ['ahead' => 0, 'behind' => 0, 'hasUpstream' => false];
        }

        $r = Runner::git(['rev-list', '--count', '--left-right', '@{u}...HEAD'], $this->path);
        if (!$r['success']) {
            return ['ahead' => 0, 'behind' => 0, 'hasUpstream' => true];
        }

        // Output: "behind\tahead"
        $parts = preg_split('/\s+/', trim($r['stdout'])) ?: [];
        return [
            'behind'      => (int) ($parts[0] ?? 0),
            'ahead'       => (int) ($parts[1] ?? 0),
            'hasUpstream' => true,
        ];
    }

    /** @return array{owner: string, repo: string, url: string}|null */
    public static function parseRemote(string $url): ?array
    {
        // git@github.com:owner/repo.git
        if (preg_match('#^git@([^:]+):([^/]+)/(.+?)(?:\.git)?$#', $url, $m) === 1) {
            return ['owner' => $m[2], 'repo' => $m[3], 'url' => $url];
        }
        // https://github.com/owner/repo.git
        if (preg_match('#^https?://[^/]+/([^/]+)/(.+?)(?:\.git)?$#', $url, $m) === 1) {
            return ['owner' => $m[1], 'repo' => $m[2], 'url' => $url];
        }
        return null;
    }
}