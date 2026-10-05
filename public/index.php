<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

$store = new Store(App::db());
$n = max(4, (int)App::config('front_stories'));
$stories = $store->stories(2, $n);
$blind = $store->blindspots((int)App::config('blindspot_min_sources'), 5);
$briefs = $store->briefs(12);
$lead = array_shift($stories);

V::header('', 'front', $store);

if ($lead === null && !$briefs) {
    V::empty();
    V::footer();
    exit;
}
?>
<?php if ($lead !== null):
    $sides = V::sides($lead);
?>
<section class="front">
  <article class="lead">
    <h1 class="hl hl-lead"><a href="<?= V::e(V::storyUrl($lead)) ?>"><?= V::e($lead['rep']['title']) ?></a></h1>
    <?php if ($lead['rep']['summary'] !== ''): ?><p class="dek"><?= V::e($lead['rep']['summary']) ?></p><?php endif; ?>
    <?= V::bar($lead['counts']) ?>
    <div class="lead-sides">
      <?php foreach (LABELS as $l): ?>
      <div class="side side-<?= $l ?>">
        <h2 class="side-h"><?= V::swatch($l) ?><?= V::t("label.$l") ?></h2>
        <?php if (!$sides[$l]): ?>
          <p class="side-none"><?= V::t('story.none') ?></p>
        <?php else: foreach (array_slice($sides[$l], 0, 3, true) as $sid => $arts): ?>
          <p class="side-item"><span class="outlet"><?= V::e(V::sourceName($sid)) ?></span><?= V::out($arts[0]['url'], V::e($arts[0]['title'])) ?></p>
        <?php endforeach; endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="go"><a href="<?= V::e(V::storyUrl($lead)) ?>"><?= V::t('compare', (int)$lead['n_sources']) ?></a></p>
  </article>

  <aside class="rail" aria-labelledby="rail-h">
    <h2 class="rail-h" id="rail-h"><?= V::t('blindspots.title') ?></h2>
    <p class="rail-intro"><?= V::t('blindspots.intro') ?></p>
    <?php if (!$blind): ?>
      <p class="rail-none"><?= V::t('blindspots.none') ?></p>
    <?php else: foreach ($blind as $s): ?>
      <article class="blind">
        <p class="blind-who"><?= V::swatch($s['blind_side']) ?><?= V::t('blindspots.only.' . $s['blind_side']) ?></p>
        <h3 class="hl hl-s"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
        <?= V::bar($s['counts'], false) ?>
      </article>
    <?php endforeach; ?>
      <p class="go"><a href="<?= V::e(V::link('blindspots')) ?>"><?= V::t('blindspots.more') ?></a></p>
    <?php endif; ?>
  </aside>
</section>
<?php endif; ?>

<?php if ($stories): ?>
<section class="more" aria-labelledby="more-h">
  <h2 class="section-h" id="more-h"><?= V::t('more.title') ?></h2>
  <div class="grid">
  <?php foreach ($stories as $s): ?>
    <article class="card">
      <h3 class="hl hl-m"><a href="<?= V::e(V::storyUrl($s)) ?>"><?= V::e($s['rep']['title']) ?></a></h3>
      <?php if ($s['rep']['summary'] !== ''): ?><p class="dek dek-s"><?= V::e($s['rep']['summary']) ?></p><?php endif; ?>
      <?= V::bar($s['counts']) ?>
      <p class="meta"><span><?= V::outlets((int)$s['n_sources']) ?></span><span><?= V::ago((int)$s['last_seen']) ?></span></p>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($briefs): ?>
<section class="brief" aria-labelledby="brief-h">
  <h2 class="section-h" id="brief-h"><?= V::t('brief.title') ?></h2>
  <p class="section-intro"><?= V::t('brief.intro') ?></p>
  <ul class="brief-list">
  <?php foreach ($briefs as $s):
      $a = $s['rep'];
      $label = V::sourceLabel($a['source_id']);
      if ($label === null) continue; ?>
    <li>
      <p class="brief-src"><?= V::swatch($label) ?><span class="outlet"><?= V::e(V::sourceName($a['source_id'])) ?></span><span class="when"><?= V::ago((int)$a['published_at']) ?></span></p>
      <?= V::out($a['url'], V::e($a['title']), 'brief-hl') ?>
    </li>
  <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<?php V::footer();
