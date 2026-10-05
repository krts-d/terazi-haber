<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

// Search runs in the reader's browser (assets/search.js) over
// search-index.json, which build.php writes. The words never leave the browser.

$strings = [
    'loading' => Lang::t('search.loading'),
    'failed' => Lang::t('search.failed'),
    'count' => Lang::t('search.count'),
    'one' => Lang::t('search.one'),
    'none' => Lang::t('search.none'),
    'compare' => Lang::t('compare'),
    'labels' => array_combine(LABELS, array_map(fn ($l) => Lang::t("label.$l"), LABELS)),
];

$store = new Store(App::db());
V::header(Lang::t('search.title'), 'search', $store);
?>
<section class="page-head">
  <h1 class="hl hl-page"><?= V::t('search.title') ?></h1>
  <?= V::searchForm('page') ?>
  <p class="section-intro search-note"><?= V::t('search.note', (int)App::config('keep_days')) ?> <a href="<?= V::e(V::link('privacy')) ?>#search"><?= V::t('search.private') ?></a></p>
  <noscript><p class="rail-none"><?= V::t('search.nojs') ?></p></noscript>
  <nav class="filter" id="search-sides" aria-label="<?= V::t('news.filter') ?>" hidden>
    <a href="#" data-side=""><?= V::t('news.all') ?></a>
    <?php foreach (LABELS as $l): ?>
    <a href="#" data-side="<?= $l ?>"><?= V::swatch($l) ?><?= V::t("label.$l") ?></a>
    <?php endforeach; ?>
  </nav>
  <p class="search-count" id="search-status" role="status"></p>
</section>
<div id="search-results" data-index="<?= V::e(V::base() . 'search-index.json?v=' . (int)$store->updatedAt()) ?>" data-strings="<?= V::e(json_encode($strings, JSON_UNESCAPED_UNICODE)) ?>"></div>
<p class="pager" id="search-more-wrap" hidden><button type="button" class="search-more" id="search-more"><?= V::t('search.more') ?></button></p>
<script src="<?= V::e(V::asset('assets/search.js')) ?>" defer></script>
<?php V::footer();
