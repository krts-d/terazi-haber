<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};

// GitHub shows this page for any address that doesn't exist, such as a story
// that has since been regrouped. build.php renders it with TERAZI_BASE set,
// so its links work at any depth.
V::header(Lang::t('notfound.title'), '', new Store(App::db()));
?>
<section class="empty">
  <h1><?= V::t('notfound.title') ?></h1>
  <p><?= V::t('notfound.body') ?></p>
  <p class="go"><a href="<?= V::e(V::link('front')) ?>"><?= V::t('story.back') ?></a></p>
</section>
<?php V::footer();
