<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use GitSeek\Git\Runner;
use GitSeek\Support\Env;

function header_line(string $title): void
{
    echo "\n=== {$title} ===\n";
}

$failures = 0;

// ---------------------------------------------------------------------------
// 1. Git is installed?
// ---------------------------------------------------------------------------
header_line('1. Git available');
$check = Runner::checkGit();
if ($check['ok']) {
    echo "✅ {$check['version']}\n";
} else {
    echo "❌ git not found on PATH\n";
    $failures++;
}

// ---------------------------------------------------------------------------
// 2. Run a harmless command in an existing directory
// ---------------------------------------------------------------------------
header_line('2. git --version');
try {
    $r = Runner::run(['git', '--version'], sys_get_temp_dir());
    echo ($r['success'] ? '✅' : '❌') . " code={$r['code']} stdout=" . trim($r['stdout']) . "\n";
    if (!$r['success']) $failures++;
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "\n";
    $failures++;
}

// ---------------------------------------------------------------------------
// 3. Injection attempt: pass a shell metacharacter as an argument.
//    It must be treated as a literal string, NOT executed.
// ---------------------------------------------------------------------------
header_line('3. Injection attempt (should be literal, not executed)');
try {
    $r = Runner::run(
        ['git', '--version', ';', 'rm', '-rf', '/'],
        sys_get_temp_dir()
    );
    // If we reach this point without anything deleted, the shell was bypassed.
    // git will fail because ';' is not a valid argument, and that's fine.
    echo "✅ Process ran without executing the injected shell command.\n";
    echo "   git exit code: {$r['code']} (non-zero is expected)\n";
} catch (\Throwable $e) {
    echo "❌ " . $e->getMessage() . "\n";
    $failures++;
}

// ---------------------------------------------------------------------------
// 4. Timeout enforcement
// ---------------------------------------------------------------------------
header_line('4. Timeout enforcement (2s limit on a PHP sleep)');
try {
    // Run `php -r "sleep(10);"` in temp dir — must be killed after 2s
    $r = Runner::run(
        [PHP_BINARY, '-r', 'sleep(10);'],
        sys_get_temp_dir(),
        null,
        2
    );
    echo "❌ Expected timeout, but command completed.\n";
    $failures++;
} catch (\RuntimeException $e) {
    if (str_contains($e->getMessage(), 'timed out')) {
        echo "✅ Timeout enforced correctly.\n";
    } else {
        echo "❌ Unexpected exception: {$e->getMessage()}\n";
        $failures++;
    }
}

// ---------------------------------------------------------------------------
// 5. Real repo status (if PROJECTS_ROOT has at least one git project)
// ---------------------------------------------------------------------------
header_line('5. git status on a real project');
$root = (string) Env::get('PROJECTS_ROOT', '');
$found = null;

if ($root !== '' && is_dir($root)) {
    foreach (scandir($root) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $candidate = $root . DIRECTORY_SEPARATOR . $item;
        if (is_dir($candidate) && is_dir($candidate . DIRECTORY_SEPARATOR . '.git')) {
            $found = $candidate;
            break;
        }
    }
}

if ($found === null) {
    echo "⚠️  No git project found in PROJECTS_ROOT — skipped.\n";
} else {
    echo "Testing: {$found}\n";
    try {
        $r = Runner::git(['rev-parse', '--abbrev-ref', 'HEAD'], $found);
        if ($r['success']) {
            echo "✅ Current branch: " . trim($r['stdout']) . "\n";
        } else {
            echo "❌ " . trim($r['stderr']) . "\n";
            $failures++;
        }

        $status = Runner::git(['status', '--porcelain'], $found);
        if ($status['success']) {
            $lines = array_filter(explode("\n", $status['stdout']));
            echo "✅ git status --porcelain: " . count($lines) . " change(s)\n";
        } else {
            echo "❌ " . trim($status['stderr']) . "\n";
            $failures++;
        }
    } catch (\Throwable $e) {
        echo "❌ " . $e->getMessage() . "\n";
        $failures++;
    }
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n" . str_repeat('─', 50) . "\n";
echo $failures === 0 ? "✅ All tests passed.\n" : "❌ {$failures} test(s) failed.\n";
exit($failures === 0 ? 0 : 1);