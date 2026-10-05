<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

$store = new Store(App::db());
$stats = $store->sourceStats();
$by = ['gov' => [], 'ind' => [], 'opp' => []];
foreach ($stats as $s) {
    $by[$s['label']][] = $s;
}

V::header(Lang::t('sources.title'), 'sources', $store);
?>
<section class="page-head">
  <h1 class="hl hl-page"><?= V::t('sources.title') ?></h1>
  <p class="dek"><?= V::t('sources.intro') ?></p>
</section>
<div class="compare">
  <?php foreach (LABELS as $l): ?>
  <section class="col col-<?= $l ?>" aria-labelledby="sc-<?= $l ?>">
    <h2 class="col-h" id="sc-<?= $l ?>"><?= V::swatch($l) ?><?= V::t("label.$l") ?> <span class="col-n"><?= count($by[$l]) ?></span></h2>
    <p class="col-def"><?= V::t("sources.def.$l") ?></p>
    <ul class="src-list">
      <?php foreach ($by[$l] as $s):
          $st = $s['state'];
          $ok = $st && $st['last_error'] === null;
          $status = !$st ? Lang::t('sources.never') : ($ok ? Lang::t('sources.ok') : Lang::t('sources.error'));
      ?>
      <li class="src<?= $st && !$ok ? ' is-failing' : '' ?>">
        <p class="src-name"><?= V::out($s['site'], V::e($s['name'])) ?></p>
        <?php if (($about = V::sourceAbout($s['id'])) !== null): ?><p class="src-about"><?= V::e($about) ?></p><?php endif; ?>
        <p class="src-meta"><span><?= V::t('sources.last24', $s['last24h']) ?></span><span class="src-status"><?= V::e($status) ?></span></p>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endforeach; ?>
</div>
<?php V::footer();
