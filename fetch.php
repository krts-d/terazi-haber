<?php
declare(strict_types=1);

/**
 * Fetch all feeds, store new articles and regroup stories.
 *
 *   php fetch.php            normal run (put this in cron / a systemd timer)
 *   php fetch.php --check    test every feed and print what it returned; stores nothing
 *   php fetch.php --source=sozcu   only fetch one outlet
 *   php fetch.php -q         quiet (only errors)
 */

use Terazi\{App, FeedFetcher, FeedParser, Store};

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/src/bootstrap.php';

$opts = getopt('q', ['check', 'source:']);
$quiet = isset($opts['q']);
$check = isset($opts['check']);
$say = function (string $s) use ($quiet): void {
    if (!$quiet) {
        fwrite(STDOUT, $s . PHP_EOL);
    }
};

// Only one run at a time (a slow feed shouldn't make cron runs pile up).
$lock = fopen(sys_get_temp_dir() . '/terazi-fetch-' . md5(__DIR__) . '.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Another fetch is still running; skipping.\n");
    exit(0);
}

$cfg = App::config();
$sources = App::sources();

// The Privacy page has to name the site's owner (KVKK, Law 5651).
$missing = array_values(array_filter(['name', 'email', 'address'], fn ($k) => trim((string)($cfg['operator'][$k] ?? '')) === ''));
if ($missing) {
    $say("Before publishing: fill in 'operator' => " . implode(', ', $missing) . " in config.php (shown on the Privacy page).\n");
}
if (isset($opts['source'])) {
    $only = (string)$opts['source'];
    if (!isset($sources[$only])) {
        fwrite(STDERR, "Unknown or disabled source '$only'. Known: " . implode(', ', array_keys($sources)) . "\n");
        exit(1);
    }
    $sources = [$only => $sources[$only]];
}

$store = new Store(App::db());
$state = $check ? [] : $store->feedState();
$t0 = microtime(true);
$results = FeedFetcher::fetchAll($sources, $state, (int)$cfg['fetch_timeout'], (string)$cfg['user_agent']);

$failed = 0;
$totalNew = 0;
foreach ($sources as $id => $s) {
    $r = $results[$id];
    $name = mb_strimwidth($s['name'], 0, 18);
    $name .= str_repeat(' ', max(0, 18 - mb_strlen($name)));
    $label = str_pad($s['label'], 3);

    if ($r['status'] === 304) {
        $say("  ok    $label  $name  not modified");
        $check || $store->saveFeedState($id, 304, null, null, $r['etag'], $r['last_modified']);
        continue;
    }
    if ($r['body'] === null) {
        $failed++;
        fwrite(STDERR, "  FAIL  $label  $name  {$r['error']}  ({$s['feed']})\n");
        $check || $store->saveFeedState($id, $r['status'], $r['error'], null, null, null);
        continue;
    }
    try {
        $items = FeedParser::parse($r['body'], $s['site']);
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, "  FAIL  $label  $name  {$e->getMessage()}  ({$s['feed']})\n");
        $check || $store->saveFeedState($id, $r['status'], $e->getMessage(), null, null, null);
        continue;
    }

    if ($check) {
        $first = $items[0] ?? null;
        $img = count(array_filter($items, fn ($i) => $i['image'] !== null));
        $say(sprintf('  ok    %s  %s  %3d items, %3d with images. Newest: %s', $label, $name, count($items), $img,
            $first ? date('d M H:i', $first['published_at']) . ' ' . mb_strimwidth($first['title'], 0, 60, '…') : '-'));
        continue;
    }

    // Some feeds still list items older than we keep; don't add them only to delete them again.
    $items = array_values(array_filter($items, fn ($i) => $i['published_at'] >= time() - (int)$cfg['keep_days'] * 86400));
    $new = $store->insertArticles($id, $items);
    $totalNew += $new;
    $store->saveFeedState($id, 200, null, count($items), $r['etag'], $r['last_modified']);
    $say(sprintf('  ok    %s  %s  %3d items, %3d new', $label, $name, count($items), $new));
}

if ($check) {
    $say(sprintf("\n%d of %d feeds working (%.1fs).", count($sources) - $failed, count($sources), microtime(true) - $t0));
    exit($failed ? 2 : 0);
}

// Bars and blindspots lean toward whichever political side has more outlets.
$byLabel = array_count_values(array_column(App::sources(), 'label'));
[$g, $o] = [$byLabel['gov'] ?? 0, $byLabel['opp'] ?? 0];
if ($g && $o && max($g, $o) > 1.5 * min($g, $o)) {
    $say("\nNote: $g pro-government vs $o pro-opposition outlets. Keep the two sides close in size in sources.php,\n"
        . "or the coverage bars will lean toward the bigger one.");
}

$pruned = $store->prune((int)$cfg['keep_days']);
[$stories, $multi] = $store->rebuildStories($cfg);
$say(sprintf("\n%d new articles, %d old ones removed. %d stories, %d covered by 2+ outlets. (%.1fs)",
    $totalNew, $pruned, $stories, $multi, microtime(true) - $t0));
exit($failed === count($sources) ? 2 : 0);
