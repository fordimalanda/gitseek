<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use GitSeek\Git\Discovery;
use GitSeek\Support\Cache;

Cache::forget('discovery');

echo "=== Discovery scan ===\n";
$result = Discovery::scan(useCache: false);

echo "Root: {$result['root']}\n";
echo "Projects: {$result['count']}\n\n";

foreach ($result['projects'] as $p) {
    $remote = $p['remote'] ? "{$p['remote']['owner']}/{$p['remote']['repo']}" : '∅';
    $state  = match (true) {
        $p['error'] !== null  => "❌ {$p['error']}",
        $p['dirty'] > 0       => "🔴 {$p['dirty']} modif, {$p['untracked']} non-track",
        $p['untracked'] > 0   => "🟡 {$p['untracked']} non-track",
        default               => '🟢 clean',
    };
    printf(
        "- %-20s [%-10s] %-30s upstream=%s ahead=%d behind=%d  %s\n",
        $p['name'],
        $p['branch'] ?? '?',
        $remote,
        $p['hasUpstream'] ? 'yes' : 'no',
        $p['ahead'],
        $p['behind'],
        $state
    );
}

// Second call should hit cache
$cached = Discovery::scan();
echo "\nCache hit: " . ($cached['cached'] ? 'yes ✅' : 'no ❌') . "\n";