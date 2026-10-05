<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

const PER_PAGE = 40;

$store = new Store(App::db());
$side = in_array($_GET['side'] ?? '', LABELS, true) ? $_GET['side'] : null;
$page = max(1, (int)filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
// Ask for one extra row to know whether there is an older page. Only
// news_pages pages are built for each side; past that, search finds the rest.
$items = $store->latest(PER_PAGE + 1, ($page - 1) * PER_PAGE, $side);
$hasMore = count($items) > PER_PAGE;
$hasOlder = $hasMore && $page < (int)App::config('news_pages');
$items = array_slice($items, 0, PER_PAGE);

V::header(Lang::t('news.title'), 'news', $store);
?>
<section class="page-head">
  <h1 class="hl hl-page"><?= V::t('news.title') ?></h1>
  <p class="dek"><?= V::t('news.intro') ?></p>
  <nav class="filter" aria-label="<?= V::t('news.filter') ?>">
    <a href="<?= V::e(V::link('news')) ?>"<?= $side === null ? ' aria-current="page"' : '' ?>><?= V::t('news.all') ?></a>
    <?php foreach (LABELS as $l): ?>
    <a href="<?= V::e(V::link('news', ['side' => $l])) ?>"<?= $side === $l ? ' aria-current="page"' : '' ?>><?= V::swatch($l) ?><?= V::t("label.$l") ?></a>
    <?php endforeach; ?>
  </nav>
</section>
<?php if (!$items): ?>
  <p class="rail-none"><?= V::t('news.none') ?></p>
<?php else: ?>
<?php V::newsList($items); ?>
<?php if ($page > 1 || $hasOlder): ?>
<nav class="pager" aria-label="<?= V::t('news.pages') ?>">
  <?php if ($page > 1): ?><a href="<?= V::e(V::link('news', ['side' => $side, 'page' => $page - 1])) ?>"><?= V::t('news.newer') ?></a><?php endif; ?>
  <?php if ($hasOlder): ?><a class="pager-older" href="<?= V::e(V::link('news', ['side' => $side, 'page' => $page + 1])) ?>"><?= V::t('news.older') ?></a><?php endif; ?>
</nav>
<?php endif; ?>
<?php if ($hasMore && !$hasOlder): ?>
  <p class="section-intro news-end"><?= V::t('news.more_in_search') ?> <a href="<?= V::e(V::link('search')) ?>"><?= V::t('search.title') ?></a></p>
<?php endif; ?>
<?php endif; ?>
<?php V::footer();
