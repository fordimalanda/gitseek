<?php
declare(strict_types=1);

namespace GitSeek\Git;

/**
 * Secure process runner.
 *
 * This is the ONLY place in GitSeek where external commands are executed.
 * Every call goes through proc_open() with an ARRAY of arguments, which means:
 *   - no shell is ever invoked
 *   - PHP handles argument escaping itself (safe on Unix and Windows)
 *   - shell metacharacters (;, &, |, $, etc.) are treated as literal strings
 *
 * Never introduce shell_exec(), exec(), system(), passthru(), or the backtick
 * operator anywhere else in the codebase. Everything must go through Runner.
 */
final class Runner
{
    public const DEFAULT_TIMEOUT = 30;
    public const PUSH_TIMEOUT = 120;

    /**
     * Run a command without a shell.
     *
     * @param string[] $args    Full command line, e.g. ['git', 'status', '--porcelain']
     * @param string   $cwd     Working directory (must exist)
     * @param string|null $stdin Optional data to write to stdin
     * @param int      $timeout Max seconds before the process is killed
     *
     * @return array{success: bool, stdout: string, stderr: string, code: int}
     */
    public static function run(
        array $args,
        string $cwd,
        ?string $stdin = null,
        int $timeout = self::DEFAULT_TIMEOUT
    ): array {
        if ($args === []) {
            throw new \InvalidArgumentException('Command arguments cannot be empty.');
        }

        if (!is_dir($cwd)) {
            throw new \RuntimeException("Working directory not found: {$cwd}");
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Array form → PHP handles escaping, no shell is spawned.
        $process = @proc_open($args, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new \RuntimeException(
                'Failed to spawn process. Is git installed and on PATH?'
            );
        }

        try {
            if ($stdin !== null && $stdin !== '') {
                fwrite($pipes[0], $stdin);
            }
            fclose($pipes[0]);

            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $stdout    = '';
            $stderr    = '';
            $deadline  = microtime(true) + $timeout;

            while (true) {
                $status = proc_get_status($process);

                // Build the set of pipes that still have data to read.
                $read = [];
                if (!feof($pipes[1])) {
                    $read[] = $pipes[1];
                }
                if (!feof($pipes[2])) {
                    $read[] = $pipes[2];
                }

                if ($read !== []) {
                    $write  = null;
                    $except = null;
                    if (stream_select($read, $write, $except, 0, 100_000) > 0) {
                        foreach ($read as $stream) {
                            $chunk = (string) fread($stream, 8192);
                            if ($stream === $pipes[1]) {
                                $stdout .= $chunk;
                            } else {
                                $stderr .= $chunk;
                            }
                        }
                    }
                }

                if (!$status['running']) {
                    // Drain anything still buffered after the process exited.
                    $stdout .= (string) stream_get_contents($pipes[1]);
                    $stderr .= (string) stream_get_contents($pipes[2]);

                    return [
                        'success' => $status['exitcode'] === 0,
                        'stdout'  => $stdout,
                        'stderr'  => $stderr,
                        'code'    => $status['exitcode'],
                    ];
                }

                if (microtime(true) >= $deadline) {
                    proc_terminate($process, 9);
                    throw new \RuntimeException(sprintf(
                        'Command timed out after %ds: %s',
                        $timeout,
                        implode(' ', $args)
                    ));
                }
            }
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }
            if (is_resource($process)) {
                @proc_close($process);
            }
        }
    }

    /**
     * Convenience wrapper for git commands.
     *
     * Adds safe defaults that we always want:
     *   - core.quotepath=false   → filenames with accents stay readable
     *   - color.ui=never         → plain text output (no ANSI codes)
     *   - advice.detachedHead=false → no noisy advice on detached HEAD
     *
     * @param string[] $gitArgs e.g. ['status', '--porcelain']
     * @return array{success: bool, stdout: string, stderr: string, code: int}
     */
    public static function git(
        array $gitArgs,
        string $cwd,
        int $timeout = self::DEFAULT_TIMEOUT
    ): array {
        $args = array_merge(
            [
                'git',
                '-c', 'core.quotepath=false',
                '-c', 'color.ui=never',
                '-c', 'advice.detachedHead=false',
            ],
            $gitArgs
        );

        return self::run($args, $cwd, null, $timeout);
    }

    /**
     * Quick sanity check: is git available on this machine?
     *
     * @return array{version: string|null, ok: bool}
     */
    public static function checkGit(): array
    {
        try {
            $result = self::run(['git', '--version'], sys_get_temp_dir(), null, 5);
            if ($result['success']) {
                return [
                    'ok'      => true,
                    'version' => trim($result['stdout']),
                ];
            }
            return ['ok' => false, 'version' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'version' => null];
        }
    }
}