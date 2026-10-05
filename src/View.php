<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Small HTML helpers shared by the pages. Everything printed goes through e().
 * The pages are rendered once by build.php into plain .html files, so every
 * link goes through link() and every asset through asset().
 */
final class View
{

    public static function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function t(string $key, mixed ...$args): string
    {
        return self::e(Lang::t($key, ...$args));
    }

    public static function outlets(int $n): string
    {
        return $n === 1 ? self::t('outlet') : self::t('outlets', $n);
    }

    public static function sourceName(string $id): string
    {
        return App::sources()[$id]['name'] ?? $id;
    }

    public static function sourceLabel(string $id): ?string
    {
        return App::sources()[$id]['label'] ?? null;
    }

    /** Why an outlet has its label, in the interface language (English if that's missing). */
    public static function sourceAbout(string $id): ?string
    {
        $about = App::sources()[$id]['about'] ?? null;
        if (is_array($about)) {
            $about = $about[Lang::lang()] ?? $about['en'] ?? null;
        }
        return is_string($about) && $about !== '' ? $about : null;
    }

    /**
     * A story's articles grouped by side, then by outlet (outlets in the
     * order they first reported it; each outlet's articles oldest first).
     * Outlets no longer in sources.php are skipped.
     * @return array{gov:array<string,list<array>>, ind:array<string,list<array>>, opp:array<string,list<array>>}
     */
    public static function sides(array $story): array
    {
        $out = ['gov' => [], 'ind' => [], 'opp' => []];
        foreach ($story['articles'] as $a) {
            $label = self::sourceLabel($a['source_id']);
            if ($label !== null) {
                $out[$label][$a['source_id']][] = $a;
            }
        }
        return $out;
    }

    /**
     * Prefix for every link and asset. Empty (relative links) except on the
     * 404 page, which GitHub serves at any missing address, however deep:
     * there build.php sets TERAZI_BASE to the site's path, e.g. "/terazi/".
     */
    public static function base(): string
    {
        return (string)getenv('TERAZI_BASE');
    }

    /**
     * Address of a page of the built site. Every page is a file in the
     * site's top folder: news-gov-2.html, recap-yesterday-multi.html, story-123.html.
     * @param array<string, string|int|null> $q
     */
    public static function link(string $page, array $q = []): string
    {
        $path = match ($page) {
            'front' => '',
            'news' => 'news' . (isset($q['side']) ? '-' . $q['side'] : '') . (($q['page'] ?? 1) > 1 ? '-' . (int)$q['page'] : '') . '.html',
            'recap' => 'recap' . (($q['day'] ?? null) === 'yesterday' ? '-yesterday' : '') . (isset($q['show']) ? '-' . $q['show'] : '') . '.html',
            'story' => 'story-' . (int)$q['id'] . '.html',
            default => $page . '.html',  // blindspots, sources, privacy, search, random
        };
        $url = self::base() . $path;
        return $url === '' ? './' : $url;
    }

    public static function storyUrl(array $story): string
    {
        return self::link('story', ['id' => (int)$story['id']]);
    }

    /** Address of a file in public/ with a version stamp from its contents, so browsers refetch it only when it changes. */
    public static function asset(string $path): string
    {
        $file = dirname(__DIR__) . '/public/' . $path;
        return self::base() . $path . '?v=' . (is_file($file) ? substr(md5_file($file), 0, 8) : '0');
    }

    /** Outbound link to an outlet's article. */
    public static function out(string $url, string $inner, string $class = ''): string
    {
        $c = $class !== '' ? ' class="' . self::e($class) . '"' : '';
        return '<a' . $c . ' href="' . self::e($url) . '" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer">' . $inner . '</a>';
    }

    /**
     * "12 min ago" for a time. The page is built ahead of time, so
     * assets/app.js recalculates it in the reader's browser.
     */
    public static function ago(int $ts): string
    {
        return '<time datetime="' . date('c', $ts) . '" data-ago>' . self::e(Lang::ago($ts)) . '</time>';
    }

    /** A small square marker in the side's ink, with the label for screen readers. */
    public static function swatch(string $label): string
    {
        return '<span class="sw sw-' . self::e($label) . '" aria-hidden="true"></span>';
    }

    /**
     * Coverage bar: three segments sized by outlet count per side.
     * @param array{gov:int,ind:int,opp:int} $c
     */
    public static function bar(array $c, bool $withCounts = true): string
    {
        $aria = [];
        $segs = '';
        foreach (LABELS as $l) {
            $aria[] = Lang::t("label.$l") . ': ' . $c[$l];
            if ($c[$l] > 0) {
                $segs .= '<span class="seg seg-' . $l . '" style="flex-grow:' . $c[$l] . '"></span>';
            }
        }
        $html = '<div class="cov" role="img" aria-label="' . self::e(implode(', ', $aria)) . '"><div class="bar">' . $segs . '</div>';
        if ($withCounts) {
            $html .= '<div class="cov-counts">';
            foreach (LABELS as $l) {
                $html .= '<span class="cc cc-' . $l . ($c[$l] === 0 ? ' is-zero' : '') . '">' . self::swatch($l)
                    . self::t("label.$l.short") . ' <b>' . $c[$l] . '</b></span>';
            }
            $html .= '</div>';
        }
        return $html . '</div>';
    }

    /**
     * The balance-scale figure for a story page. The beam tips toward the
     * political side with more outlets; independents sit on the pivot.
     * @param array{gov:int,ind:int,opp:int} $c
     */
    public static function scale(array $c): string
    {
        $w = 360; $cx = 180; $py = 46; $arm = 132; $h = 190;
        // Positive tilt = the pro-government (left) pan sinks.
        $sum = max(1, $c['gov'] + $c['opp']);
        $deg = max(-11.0, min(11.0, 11.0 * ($c['gov'] - $c['opp']) / $sum));
        $rad = deg2rad($deg);
        $dy = $arm * sin($rad);
        $dx = $arm * (1 - cos($rad));

        // Pans are drawn hanging from a level beam, then moved into place by CSS
        // (custom properties below), which lets the beam settle on page load.
        $pan = function (float $x, string $side, int $n, float $tx, float $ty) use ($py): string {
            $top = $py + 50;
            $s = sprintf('<g class="pan-g" style="--tx:%.2fpx;--ty:%.2fpx">', $tx, $ty);
            $s .= sprintf('<path class="str" d="M%.1f %.1f L%.1f %.1f M%.1f %.1f L%.1f %.1f"/>', $x, $py, $x - 30, $top, $x, $py, $x + 30, $top);
            $s .= sprintf('<path class="pan pan-%s" d="M%.1f %.1f Q%.1f %.1f %.1f %.1f Z"/>', $side, $x - 40, $top, $x, $top + 32, $x + 40, $top);
            $s .= sprintf('<text class="pan-n" x="%.1f" y="%.1f">%d</text>', $x, $top - 8, $n);
            $s .= sprintf('<text class="pan-l" x="%.1f" y="%.1f">%s</text>', $x, $top + 40, self::e(Lang::t("label.$side")));
            return $s . '</g>';
        };

        $svg = '<svg class="scale" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="'
            . self::e(Lang::t('label.gov') . ' ' . $c['gov'] . ', ' . Lang::t('label.ind') . ' ' . $c['ind'] . ', ' . Lang::t('label.opp') . ' ' . $c['opp']) . '">';
        $svg .= '<defs><pattern id="hatch-opp" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">'
            . '<rect class="hatch-bg" width="5" height="5"/><rect class="hatch-ln" width="2.2" height="5"/></pattern>'
            . '<pattern id="dots-ind" width="4" height="4" patternUnits="userSpaceOnUse"><circle class="dot" cx="2" cy="2" r="1.05"/></pattern></defs>';
        $svg .= '<path class="post" d="M' . $cx . ' ' . $py . ' L' . $cx . ' ' . ($h - 10) . ' M' . ($cx - 36) . ' ' . ($h - 10) . ' L' . ($cx + 36) . ' ' . ($h - 10) . '"/>';
        $svg .= sprintf('<line class="beam" style="--deg:%.2fdeg" x1="%d" y1="%d" x2="%d" y2="%d"/>', -$deg, $cx - $arm, $py, $cx + $arm, $py);
        $svg .= '<circle class="pivot" cx="' . $cx . '" cy="' . $py . '" r="5.5"/>';
        $svg .= $pan($cx - $arm, 'gov', $c['gov'], $dx, $dy) . $pan($cx + $arm, 'opp', $c['opp'], -$dx, -$dy);
        $svg .= '<text class="ind-n" x="' . $cx . '" y="' . ($py - 18) . '">' . self::e(Lang::t('label.ind')) . ' ' . $c['ind'] . '</text>';
        return $svg . '</svg>';
    }

    /**
     * Link styled as a button that opens a random story from today.
     * $again: the "another random story" button on a story page, shown by
     * assets/app.js only when the reader got there at random.
     */
    public static function randomButton(string $key = 'random', bool $again = false): string
    {
        $die = '<svg class="die" viewBox="0 0 16 16" aria-hidden="true"><rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/>'
            . '<circle cx="5" cy="5" r="1.2"/><circle cx="8" cy="8" r="1.2"/><circle cx="11" cy="11" r="1.2"/></svg>';
        return '<a class="random" href="' . self::e(self::link('random')) . '" rel="nofollow"' . ($again ? ' data-random-again hidden' : '') . '>'
            . $die . self::t($key) . '</a>';
    }

    /**
     * Search box. Searching happens in the reader's browser (assets/app.js,
     * assets/search.js), and the words travel after "#" in the address, which
     * browsers never send to a server. The input has no name, so even without
     * JavaScript the words aren't sent to GitHub. $where: 'nav' or 'page'.
     */
    public static function searchForm(string $where): string
    {
        $id = "q-$where";
        return '<form class="search search-' . self::e($where) . '" action="' . self::e(self::link('search')) . '" method="get" role="search">'
            . '<label class="vh" for="' . $id . '">' . self::t('search.label') . '</label>'
            . '<input id="' . $id . '" type="search" maxlength="200" placeholder="' . self::t('search.placeholder') . '" autocomplete="off" spellcheck="false">'
            . '<button type="submit">' . self::t('search.button') . '</button></form>';
    }

    /** Articles as a list under day headings (Latest news). Rows come from Store::latest. */
    public static function newsList(array $items): void
    {
        echo '<div class="news">';
        $day = null;
        foreach ($items as $a) {
            $label = self::sourceLabel($a['source_id']);
            $ts = (int)$a['published_at'];
            if (date('Y-m-d', $ts) !== $day) {
                if ($day !== null) {
                    echo "</ol>\n";
                }
                $day = date('Y-m-d', $ts);
                echo '<h2 class="news-day">' . self::e(Lang::longDate($ts)) . "</h2>\n<ol class=\"news-list\">\n";
            }
            ?>
    <li class="news-item">
      <p class="news-src"><?= self::swatch($label) ?><span class="outlet"><?= self::e(self::sourceName($a['source_id'])) ?></span><time datetime="<?= date('c', $ts) ?>"><?= date('H:i', $ts) ?></time></p>
      <h3 class="hl hl-entry"><?= self::out($a['url'], self::e($a['title'])) ?></h3>
      <?php if ($a['summary'] !== ''): ?><p class="entry-sum dek-s"><?= self::e($a['summary']) ?></p><?php endif; ?>
      <?php if ($a['story']): ?>
      <div class="news-story">
        <?= self::bar($a['story']['counts'], false) ?>
        <a href="<?= self::e(self::storyUrl($a['story'])) ?>"><?= self::t('compare', (int)$a['story']['n_sources']) ?></a>
      </div>
      <?php endif; ?>
    </li>
<?php
        }
        echo "</ol>\n</div>\n";
    }

    public static function header(string $title, string $active, ?Store $store = null): void
    {
        $site = (string)App::config('site_name');
        $updated = $store?->updatedAt();
        $now = time();
        $lang = Lang::lang();
        $fullTitle = $title === '' ? $site : "$title – $site";
        // Words app.js uses to keep "12 min ago" current.
        $ago = json_encode(['now' => Lang::t('ago.now'), 'min' => Lang::t('ago.min'), 'hour' => Lang::t('ago.hour'), 'day' => Lang::t('ago.day')], JSON_UNESCAPED_UNICODE);
        $nav = fn (string $page, string $key) => '<a href="' . self::e(self::link($page)) . '"' . ($active === $page ? ' aria-current="page"' : '') . '>' . self::t($key) . '</a>';
        ?>
<!doctype html>
<html lang="<?= self::e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<?php /* GitHub Pages can't send headers, so the browser gets its rules here: load nothing from anywhere but this site. */ ?>
<meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'none'">
<title><?= self::e($fullTitle) ?></title>
<meta name="description" content="<?= self::t('tagline') ?>">
<meta name="color-scheme" content="light dark">
<link rel="preload" href="<?= self::e(self::base()) ?>assets/fonts/newsreader-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= self::e(self::asset('assets/style.css')) ?>">
<link rel="icon" href="<?= self::e(self::base()) ?>favicon.svg" type="image/svg+xml">
<script src="<?= self::e(self::asset('assets/app.js')) ?>" defer></script>
</head>
<body data-updated="<?= (int)$updated ?>" data-ago="<?= self::e($ago) ?>">
<header class="masthead">
  <div class="wrap">
    <div class="dateline">
      <span><?= self::e(Lang::longDate($now)) ?></span>
      <span><?= $updated ? self::t('updated', date('H:i', $updated)) : self::t('never_updated') ?></span>
    </div>
    <a class="nameplate" href="<?= self::e(self::link('front')) ?>"><?= self::e($site) ?></a>
    <p class="tagline"><?= self::t('tagline') ?></p>
    <nav class="sections" aria-label="Sections">
      <?= $nav('front', 'nav.front') ?>
      <?= $nav('news', 'nav.news') ?>
      <?= $nav('recap', 'nav.recap') ?>
      <?= $nav('blindspots', 'nav.blindspots') ?>
      <?= $nav('sources', 'nav.sources') ?>
      <?= self::randomButton() ?>
      <?= $active === 'search' ? '' : self::searchForm('nav') ?>
      <span class="legend" aria-hidden="true">
        <?php foreach (LABELS as $l): ?><span><?= self::swatch($l) ?><?= self::t("label.$l") ?></span><?php endforeach; ?>
      </span>
    </nav>
  </div>
</header>
<main class="wrap">
<?php
    }

    public static function footer(): void
    {
        ?>
</main>
<footer class="colophon">
  <div class="wrap"><p><?= self::t('footer') ?> <a href="<?= self::e(self::link('privacy')) ?>"><?= self::t('privacy.link') ?></a></p></div>
</footer>
<p class="fresh" id="fresh-note" role="status" hidden><?= self::t('fresh') ?> <a href=""><?= self::t('fresh.reload') ?></a></p>
</body>
</html>
<?php
    }

    public static function empty(): void
    {
        echo '<section class="empty"><h1>' . self::t('empty.title') . '</h1><p>' . self::t('empty.body') . '</p></section>';
    }
}
