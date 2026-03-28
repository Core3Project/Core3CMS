<?php $fw = Widget::zone('footer'); ?>
<?php if ($fw): ?>
<div class="footer-widgets"><div class="container" style="padding-top:0;padding-bottom:0"><?= $fw ?></div></div>
<?php endif; ?>
<footer class="site-footer">
    &copy; <?= date('Y') ?> <?= e(Setting::get('site_name', 'Core 3 CMS')) ?>.
    Powered by <a href="https://core3cms.com" target="_blank" rel="noopener">Core 3 CMS</a>
</footer>
<?= Modules::html('footer') ?>
</body>
</html>
