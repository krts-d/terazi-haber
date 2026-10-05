<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

const TOP = 10;

$store = new Store(App::db());
$yesterday = ($_GET['day'] ?? '') === 'yesterday';
$from = strtotime($yesterday ? 'yesterday' : 'today');
$to = strtotime('+1 day', $from);

$stories = $store->storiesBetween($from, $to);
$blind = [];
foreach ($stories as $s) {
    if ((int)$s['n_sources'] >= (int)App::config('blindspot_min_sources') && ($side = Store::blindSide($s['counts'])) !== null) {
        $blind[] = $s + ['blind_side' => $side];
    }
}
$perOutlet = $store->articleCounts($from, $to);
$nArticles = array_sum($perOutlet);
$nOutlets = count(array_filter($perOutlet));
$multiStories = array_values(array_filter($stories, fn ($s) => (int)$s['n_sources'] > 1));
$top = array_slice($multiStories, 0, TOP);

// Which stat's list is open (show=outlets|multi|blind, built as its own
// page: recap-multi.html); clicking it again closes it.
$show = in_array($_GET['show'] ?? '', ['outlets', 'multi', 'blind'], true) ? $_GET['show'] : null;
$statUrl = function (string $panel) use ($yesterday, $show): string {
    return V::link('recap', ['day' => $yesterday ? 'yesterday' : null, 'show' => $panel === $show ? null : $panel])
        . ($panel === $show ? '' : '#recap-panel');
};
$stat = function (string $panel, int $n, string $text) use ($statUrl, $show): string {
    $open = $panel === $show;
    return '<a class="stat' . ($open ? ' is-open' : '') . '" href="' . V::e($statUrl($panel)) . '" aria-expanded="' . ($open ? 'true' : 'false') . '"'
        . ($open ? ' aria-controls="recap-panel"' : '') . '><b>' . $n . '</b> ' . $text . '</a>';
};
/** Names of the outlets that covered a story, in the order they reported it. */
$outletNames = function (array $story): string {
    $names = [];
    foreach ($story['articles'] as $a) {
        if (V::sourceLabel($a['source_id']) !== null) {
            $names[$a['source_id']] = V::sourceName($a['source_id']);
        }
    }
    return implode(', ', $names);
};

V::header(Lang::t('recap.title'), 'recap', $store);
?>
<section class="page-head">
  <p class="recap-date"><?= V::e(Lang::longDate($from)) ?></p>
  <h1 class="hl hl-page"><?= V::t('recap.title') ?></h1>
  <p class="dek"><?= V::t('recap.intro') ?></p>
  <div class="recap-tools">
    <nav class="filter" aria-label="<?= V::t('recap.day') ?>">
      <a href="<?= V::e(V::link('recap')) ?>"<?= !$yesterday ? ' aria-current="page"' : '' ?>><?= V::t('recap.today') ?></a>
      <a href="<?= V::e(V::link('recap', ['day' => 'yesterday'])) ?>"<?= $yesterday ? ' aria-current="page"' : '' ?>><?= V::t('recap.yesterday') ?></a>
    </nav>
    <?= V::randomButton() ?>
  </div>
  <?php if ($nArticles > 0): ?>
  <p class="recap-stats">
    <?= $stat('outlets', $nArticles, V::t('recap.stat.articles', $nOutlets)) ?>
    <?= $stat('multi', count($multiStories), V::t('recap.stat.multi')) ?>
    <?= $stat('blind', count($blind), V::t('recap.stat.blind')) ?>
  </p>
  <?php endif; ?>
</section>

<?php if ($show !== null && $nArticles > 0): ?>
<section class="recap-panel" id="recap-panel" aria-labelledby="recap-panel-h">
  <div class="recap-panel-head">
    <h2 class="section-h" id="recap-panel-h"><?= V::t("recap.panel.$show") ?></h2>
    <a class="recap-close" href="<?= V::e($statUrl($show)) ?>"><?= V::t('recap.close') ?></a>
  </div>

  <?php if ($show === 'outlets'):
      $max = max(1, max($perOutlet));
      $bySide = ['gov' => [], 'ind' => [], 'opp' => []];
      foreach ($perOutlet as $sid => $n) {
          $bySide[V::sourceLabel($sid)][$sid] = $n;
      } ?>
  <div class="compare">
    <?php foreach (LABELS as $l): ?>
    <section class="col col-<?= $l ?>" aria-labelledby="po-<?= $l ?>">
      <h3 class="col-h" id="po-<?= $l ?>"><?= V::swatch($l) ?><?= V::t("label.$l") ?> <span class="col-n"><?= array_sum($bySide[$l]) ?></span></h3>
      <ul class="po-list">
        <?php foreach ($bySide[$l] as $sid => $n): ?>
        <li class="po<?= $n === 0 ? ' is-zero' : '' ?>">
          <span class="po-name"><?= V::e(V::sourceName($sid)) ?></span>
          <span class="po-n"><?= V::t($n === 1 ? 'article' : 'articles', $n) ?></span>
          <span class="po-bar"><span class="seg seg-<?= $l ?>" style="width:<?= round(100 * $n / $max, 1) ?>%"></span></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endforeach; ?>
  </div>

  <?php elseif ($show === 'multi'): ?>
  <ol class="ps-list">
    <?php foreach ($multiStories as $s): ?>
    <li class="ps">
      <h3 class="hl hl-s"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
      <?= V::bar($s['counts'], false) ?>
      <p class="ps-outlets"><span><?= V::outlets((int)$s['n_sources']) ?>:</span> <?= V::e($outletNames($s)) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>

  <?php elseif (!$blind): ?>
  <p class="rail-none"><?= V::t('blindspots.none') ?></p>

  <?php else: ?>
  <div class="blind-cols">
    <?php foreach (['gov', 'opp'] as $side):
        $list = array_filter($blind, fn ($s) => $s['blind_side'] === $side); ?>
    <section class="blind-col" aria-labelledby="pb-<?= $side ?>">
      <h3 class="col-h" id="pb-<?= $side ?>"><?= V::swatch($side) ?><?= V::t("blindspots.only.$side") ?> <span class="col-n"><?= count($list) ?></span></h3>
      <?php foreach ($list as $s): ?>
      <article class="blind">
        <h4 class="hl hl-s"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h4>
        <?= V::bar($s['counts'], false) ?>
        <p class="ps-outlets"><?= V::e($outletNames($s)) ?></p>
      </article>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!$top): ?>
  <p class="rail-none"><?= V::t('recap.none') ?></p>
<?php else: ?>
<section class="recap" aria-labelledby="recap-top">
  <h2 class="section-h" id="recap-top"><?= V::t('recap.top') ?></h2>
  <ol class="recap-list">
  <?php foreach ($top as $i => $s): ?>
    <li class="recap-item<?= $i < 3 ? ' is-major' : '' ?>">
      <span class="recap-n" aria-hidden="true"><?= $i + 1 ?></span>
      <div class="recap-body">
        <h3 class="hl <?= $i < 3 ? 'hl-m' : 'hl-s' ?>"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
        <?php if ($i < 3 && $s['rep']['summary'] !== ''): ?><p class="dek dek-s"><?= V::e($s['rep']['summary']) ?></p><?php endif; ?>
        <?= V::bar($s['counts']) ?>
        <p class="meta"><span><?= V::outlets((int)$s['n_sources']) ?></span><span><?= V::t('articles', (int)$s['n_articles']) ?></span><span><?= V::t('story.first', Lang::shortDateTime((int)$s['first_seen'])) ?></span></p>
      </div>
    </li>
  <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<?php if ($blind): ?>
<section class="recap" aria-labelledby="recap-blind">
  <h2 class="section-h" id="recap-blind"><?= V::t('recap.blind') ?></h2>
  <p class="section-intro"><?= V::t('blindspots.intro') ?></p>
  <div class="recap-blind">
  <?php foreach (array_slice($blind, 0, 6) as $s): ?>
    <article class="blind">
      <p class="blind-who"><?= V::swatch($s['blind_side']) ?><?= V::t('blindspots.only.' . $s['blind_side']) ?></p>
      <h3 class="hl hl-s"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
      <?= V::bar($s['counts'], false) ?>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php V::footer();
