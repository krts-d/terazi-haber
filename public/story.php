<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};
use const Terazi\LABELS;

$store = new Store(App::db());
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$story = $id > 0 ? $store->story($id) : null;

if ($story === null) {
    // build.php only renders stories that exist; anything else is a bug.
    fwrite(STDERR, "story.php: no story with id $id\n");
    exit(1);
}

$sides = V::sides($story);
$rep = $story['rep'];

// Outlets on each side that have not covered it (within the current window).
$missing = ['gov' => [], 'ind' => [], 'opp' => []];
foreach (App::sources() as $sid => $s) {
    if (!isset($sides[$s['label']][$sid])) {
        $missing[$s['label']][] = $s['name'];
    }
}

V::header($rep['title'], '', $store);
?>
<article class="story">
  <p class="crumb"><a href="<?= V::e(V::link('front')) ?>"><?= V::t('story.back') ?></a><?= V::randomButton('random.again', true) ?></p>
  <header class="story-head">
    <div class="story-title">
      <h1 class="hl hl-story"><?= V::e($rep['title']) ?></h1>
      <?php if ($rep['summary'] !== ''): ?><p class="dek"><?= V::e($rep['summary']) ?></p><?php endif; ?>
      <p class="meta">
        <span><?= V::outlets((int)$story['n_sources']) ?></span>
        <span><?= V::t('articles', (int)$story['n_articles']) ?></span>
        <span><?= V::t('story.first', Lang::shortDateTime((int)$story['first_seen'])) ?></span>
        <span><?= str_replace('%s', V::ago((int)$story['last_seen']), V::t('story.latest', '%s')) ?></span>
      </p>
    </div>
    <figure class="story-scale">
      <?= V::scale($story['counts']) ?>
      <figcaption><?= V::t('scale.caption') ?></figcaption>
    </figure>
  </header>


  <?php /* Phones stack the three sides, so this bar (hidden on wide screens) jumps between them. */ ?>
  <nav class="side-jump" aria-label="<?= V::t('story.sides') ?>">
    <?php foreach (LABELS as $l): ?>
    <a class="cc<?= $sides[$l] ? '' : ' is-zero' ?>" href="#col-<?= $l ?>"><?= V::swatch($l) ?><?= V::t("label.$l.short") ?> <b><?= count($sides[$l]) ?></b></a>
    <?php endforeach; ?>
  </nav>
  <div class="compare">
    <?php foreach (LABELS as $l): ?>
    <section class="col col-<?= $l ?>" aria-labelledby="col-<?= $l ?>">
      <h2 class="col-h" id="col-<?= $l ?>"><?= V::swatch($l) ?><?= V::t("label.$l") ?> <span class="col-n"><?= count($sides[$l]) ?></span></h2>
      <div class="col-body" id="col-body-<?= $l ?>">
      <?php if (!$sides[$l]): ?>
        <p class="col-none"><?= V::t('story.none') ?></p>
      <?php endif; ?>
      <?php foreach ($sides[$l] as $sid => $arts): $first = $arts[0]; ?>
      <div class="entry">
        <p class="entry-src"><span class="outlet"><?= V::e(V::sourceName($sid)) ?></span><time datetime="<?= date('c', (int)$first['published_at']) ?>"><?= V::e(Lang::shortDateTime((int)$first['published_at'])) ?></time></p>
        <h3 class="hl hl-entry"><?= V::out($first['url'], V::e($first['title'])) ?></h3>
        <?php if ($first['summary'] !== ''): ?><p class="entry-sum"><?= V::e($first['summary']) ?></p><?php endif; ?>
        <?php if (count($arts) > 1): ?>
        <ul class="entry-more">
          <?php foreach (array_slice($arts, 1) as $a): ?>
          <li><?= V::out($a['url'], V::e($a['title'])) ?> <time datetime="<?= date('c', (int)$a['published_at']) ?>"><?= V::e(date('H:i', (int)$a['published_at'])) ?></time></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if ($missing[$l]): ?>
        <p class="col-missing"><span><?= V::t('story.notyet') ?></span> <?= V::e(implode(', ', $missing[$l])) ?></p>
      <?php endif; ?>
      </div>
    </section>
    <?php endforeach; ?>
  </div>
</article>
<?php V::footer();
