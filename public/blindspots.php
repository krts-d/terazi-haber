<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};

$store = new Store(App::db());
$all = $store->blindspots((int)App::config('blindspot_min_sources'), 60);
$groups = ['gov' => [], 'opp' => []];
foreach ($all as $s) {
    $groups[$s['blind_side']][] = $s;
}

V::header(Lang::t('blindspots.title'), 'blindspots', $store);
?>
<section class="page-head">
  <h1 class="hl hl-page"><?= V::t('blindspots.title') ?></h1>
  <p class="dek"><?= V::t('blindspots.intro') ?></p>
</section>
<?php if (!$all): ?>
  <p class="rail-none"><?= V::t('blindspots.none') ?></p>
<?php else: ?>
<div class="blind-cols">
  <?php foreach ($groups as $side => $list): ?>
  <section class="blind-col" aria-labelledby="bc-<?= $side ?>">
    <h2 class="col-h" id="bc-<?= $side ?>"><?= V::swatch($side) ?><?= V::t("blindspots.only.$side") ?> <span class="col-n"><?= count($list) ?></span></h2>
    <?php foreach ($list as $s): ?>
    <article class="blind">
      <h3 class="hl hl-m"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
      <?= V::bar($s['counts']) ?>
      <p class="meta"><span><?= V::outlets((int)$s['n_sources']) ?></span><span><?= V::ago((int)$s['last_seen']) ?></span></p>
    </article>
    <?php endforeach; ?>
  </section>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php V::footer();
