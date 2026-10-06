<?php
declare(strict_types=1);

/**
 * Builds the whole site as plain files for GitHub Pages:
 *
 *   php build.php [folder]     default: _site
 *
 * Every page template in public/ is rendered by render.php and saved as an
 * .html file; assets are copied; search-index.json (for search in the
 * browser) and updated.json (for the "new stories" check) are written.
 * TERAZI_SITE_BASE is the site's path on GitHub, e.g. "/terazi/" (ci.sh
 * sets it): the 404 page's links use it so they work at any depth.
 */

use Terazi\{App, Lang, Store, View};
use const Terazi\LABELS;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/src/bootstrap.php';

$out = rtrim($argv[1] ?? __DIR__ . '/_site', '/');
$t0 = microtime(true);
$store = new Store(App::db());

// ── Which pages to build: file => [template, parameters, link base] ──
$pages = [
    'index.html' => ['index.php', []],
    'blindspots.html' => ['blindspots.php', []],
    'sources.html' => ['sources.php', []],
    'privacy.html' => ['privacy.php', []],
    'search.html' => ['search.php', []],
    'random.html' => ['random.php', []],
    '404.html' => ['404.php', [], getenv('TERAZI_SITE_BASE') ?: '/'],
];
foreach ([null, ...LABELS] as $side) {
    $n = min((int)App::config('news_pages'), max(1, (int)ceil($store->countLatest($side) / 40)));
    for ($p = 1; $p <= $n; $p++) {
        $pages[View::link('news', ['side' => $side, 'page' => $p])] = ['news.php', array_filter(['side' => $side, 'page' => $p > 1 ? $p : null])];
    }
}
foreach ([null, 'yesterday'] as $day) {
    foreach ([null, 'outlets', 'multi', 'blind'] as $show) {
        $pages[View::link('recap', ['day' => $day, 'show' => $show])] = ['recap.php', array_filter(['day' => $day, 'show' => $show])];
    }
}
foreach ($store->multiStoryIds() as $id) {
    $pages[View::link('story', ['id' => $id])] = ['story.php', ['id' => $id]];
}

// ── Start from an empty folder ──
$rm = function (string $dir) use (&$rm): void {
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
        is_dir("$dir/$f") && !is_link("$dir/$f") ? $rm("$dir/$f") : unlink("$dir/$f");
    }
    rmdir($dir);
};
if (is_dir($out)) {
    $rm($out);
}
mkdir($out, 0775, true);
$tmp = sys_get_temp_dir() . '/terazi-build-' . getmypid();
@mkdir($tmp);

// ── Render, a few pages at a time ──
$jobs = $pages;
$running = [];
$failed = [];
$parallel = 6;
while ($jobs || $running) {
    while ($jobs && count($running) < $parallel) {
        $file = (string)array_key_first($jobs);
        [$template, $params, $base] = $jobs[$file] + [2 => ''];
        unset($jobs[$file]);
        $err = "$tmp/" . count($running) . '-' . md5($file) . '.err';
        $proc = proc_open(
            [PHP_BINARY, __DIR__ . '/render.php', $template, json_encode($params)],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', "$out/$file", 'w'], 2 => ['file', $err, 'w']],
            $pipes,
            __DIR__,
            ['TERAZI_BASE' => $base] + getenv(),
        );
        $running[$file] = [$proc, $err];
    }
    foreach ($running as $file => [$proc, $err]) {
        $status = proc_get_status($proc);
        if ($status['running']) {
            continue;
        }
        proc_close($proc);
        $messages = trim((string)@file_get_contents($err));
        @unlink($err);
        if ($status['exitcode'] !== 0) {
            $failed[] = $file;
            fwrite(STDERR, "FAILED  $file\n" . ($messages !== '' ? "$messages\n" : ''));
        } elseif ($messages !== '') {
            fwrite(STDERR, "warning $file\n$messages\n");
        }
        unset($running[$file]);
    }
    usleep(2000);
}
@rmdir($tmp);
if ($failed) {
    fwrite(STDERR, count($failed) . " page(s) failed; the site was not built.\n");
    exit(1);
}

// ── Assets ──
$copy = function (string $from, string $to) use (&$copy): void {
    if (is_dir($from)) {
        @mkdir($to, 0775, true);
        foreach (array_diff(scandir($from) ?: [], ['.', '..']) as $f) {
            $copy("$from/$f", "$to/$f");
        }
    } else {
        copy($from, $to);
    }
};
$copy(__DIR__ . '/public/assets', "$out/assets");
copy(__DIR__ . '/public/favicon.svg', "$out/favicon.svg");

// ── Data for the browser ──
$json = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
file_put_contents("$out/updated.json", $json(['updated' => $store->updatedAt() ?? 0]));

// Search index: every stored headline, newest first, as
// [outlet, day, time, headline, summary, link, story id or 0, unix time];
// days are short dates ("6 Eki"), stories [gov, ind, opp, outlets, headline].
$days = [];
$articles = [];
foreach ($store->searchRows() as $r) {
    $ts = (int)$r['published_at'];
    $day = date('Y-m-d', $ts);
    $days[$day] ??= Lang::shortDate($ts);
    $articles[] = [$r['source_id'], $day, date('H:i', $ts), $r['title'], mb_strimwidth($r['summary'], 0, 240, '…'), $r['url'], (int)$r['story_id'], $ts];
}
$stories = [];
foreach ($store->stories(2, PHP_INT_MAX) as $s) {
    $stories[(int)$s['id']] = [$s['counts']['gov'], $s['counts']['ind'], $s['counts']['opp'], (int)$s['n_sources'], $s['rep']['title']];
}
file_put_contents("$out/search-index.json", $json([
    'built' => time(),
    'story' => View::link('story', ['id' => 0]) === 'story-0.html' ? 'story-{id}.html' : throw new LogicException('story link pattern changed'),
    'sources' => array_map(fn ($s) => [$s['name'], $s['label']], App::sources()),
    'stories' => (object)$stories,
    'days' => (object)$days,
    'articles' => $articles,
]));

printf("Built %d pages, %d headlines in the search index, into %s (%.1fs)\n",
    count($pages), count($articles), $out, microtime(true) - $t0);
