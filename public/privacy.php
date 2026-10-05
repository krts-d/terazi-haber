<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Terazi\{App, Lang, Store, View as V};

// Privacy notice (KVKK aydınlatma metni), cookies and terms of use, written
// for hosting on GitHub Pages. The text lives in src/Lang.php (privacy.*);
// the owner and hosting details come from config.php. If you change how the
// site treats visitors, or move it off GitHub, update the text.

$op = array_map(fn ($v) => trim((string)$v), (array)App::config('operator') + ['name' => '', 'email' => '', 'address' => '']);
$host = array_map(fn ($v) => trim((string)$v), (array)App::config('hosting') + ['provider' => '', 'country' => '']);

// Sections after the owner's details, in order. Each has privacy.<name>.h and .p.
$sections = ['data', 'github', 'purpose', 'transfer', 'retention', 'cookies', 'search', 'third', 'links', 'fresh', 'rights', 'apply', 'content', 'changes'];
$rights = ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ'];
$githubDocs = [
    'privacy.github.statement' => 'https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement',
    'privacy.github.pages' => 'https://docs.github.com/en/pages/getting-started-with-github-pages/about-github-pages#data-collection',
];

V::header(Lang::t('privacy.title'), 'privacy', new Store(App::db()));
?>
<section class="page-head">
  <h1 class="hl hl-page"><?= V::t('privacy.h1') ?></h1>
  <p class="dek"><?= V::t('privacy.intro') ?></p>
</section>
<div class="policy">
  <section id="controller">
    <h2 class="section-h"><?= V::t('privacy.controller.h') ?></h2>
    <?php if ($op['name'] === ''): ?>
    <p><?= V::t('privacy.controller.missing') ?></p>
    <?php else: ?>
    <p><?= V::t('privacy.controller.p') ?></p>
    <?php endif; ?>
    <dl class="policy-who">
      <?php if ($op['name'] !== ''): ?><dt><?= V::t('privacy.name') ?></dt><dd><?= V::e($op['name']) ?></dd><?php endif; ?>
      <?php if ($op['email'] !== ''): ?><dt><?= V::t('privacy.email') ?></dt><dd><a href="mailto:<?= V::e($op['email']) ?>"><?= V::e($op['email']) ?></a></dd><?php endif; ?>
      <?php if ($op['address'] !== ''): ?><dt><?= V::t('privacy.address') ?></dt><dd><?= V::e($op['address']) ?></dd><?php endif; ?>
      <?php if ($host['provider'] !== ''): ?><dt><?= V::t('privacy.host') ?></dt><dd><?= V::e($host['provider'] . ($host['country'] !== '' ? ', ' . $host['country'] : '')) ?></dd><?php endif; ?>
    </dl>
  </section>
  <?php foreach ($sections as $sec): ?>
  <section id="<?= $sec ?>">
    <h2 class="section-h"><?= V::t("privacy.$sec.h") ?></h2>
    <p><?= V::t("privacy.$sec.p") ?></p>
    <?php if ($sec === 'github'): ?>
      <ul class="policy-links">
        <?php foreach ($githubDocs as $key => $url): ?><li><?= V::out($url, V::t($key)) ?></li><?php endforeach; ?>
      </ul>
    <?php elseif ($sec === 'transfer'): ?>
      <p><?= V::t('privacy.transfer.logs') ?></p>
    <?php elseif ($sec === 'rights'): ?>
      <ol class="policy-rights">
        <?php foreach ($rights as $r): ?><li data-n="<?= $r ?>)"><?= V::t("privacy.rights.$r") ?></li><?php endforeach; ?>
      </ol>
    <?php elseif ($sec === 'apply'): ?>
      <p><?= V::t('privacy.apply.github') ?></p>
      <p><?= V::t('privacy.apply.complaint') ?></p>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>
  <p class="section-intro"><?= V::t('privacy.updated') ?></p>
</div>
<?php V::footer();
