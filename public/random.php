<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};

// Sends the reader to a random story from today (just after midnight, from
// the last 24 hours). The story ids are in the page; assets/app.js picks one.
$store = new Store(App::db());
$ids = $store->multiStoryIds(strtotime('today')) ?: $store->multiStoryIds(time() - 86400);
$pattern = str_replace('story-0.html', 'story-{id}.html', V::link('story', ['id' => 0]));

V::header(Lang::t('random'), '', $store);
?>
<section class="empty" id="random-page" data-ids="<?= V::e(json_encode($ids)) ?>" data-pattern="<?= V::e($pattern) ?>">
  <h1><?= V::t('random') ?></h1>
  <p><?= $ids ? V::t('random.going') : V::t('random.none') ?></p>
  <p class="go"><a href="<?= V::e(V::link('news')) ?>"><?= V::t('nav.news') ?></a></p>
</section>
<?php V::footer();
