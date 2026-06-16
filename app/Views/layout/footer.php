</main>

<?php if (!($hideChrome ?? false)): ?>
<footer class="site-footer">
    <div class="container footer__inner">
        <a class="footer-brand" href="index.php" aria-label="P&aacute;gina inicial da Mentup">
            <img src="<?= $basePath ?>assets/img/logos/logo_1000x1000.png" alt="Mentup">
        </a>

        <nav class="footer-nav" aria-label="Navega&ccedil;&atilde;o do rodap&eacute;">
            <a href="index.php">In&iacute;cio</a>
            <a href="championship-list.php">Campeonatos</a>
            <a href="login.php">Login</a>
            <a href="#contato">Contato</a>
        </nav>

        <div class="footer-contact">
            <span>Atendimento:</span>
            <strong>(99) 99999-9999</strong>
        </div>
    </div>

    <div class="container footer__bottom">
        <span>&copy; 2026 Mentup.</span>
        <span>Todos os direitos reservados.</span>
    </div>
</footer>
<?php endif; ?>

<?php
$publicPath = $publicPath ?? (realpath(__DIR__ . '/../../../public') ?: (__DIR__ . '/../../../public'));
$scriptFile = $publicPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'script.js';
$scriptVersion = is_file($scriptFile) ? '?v=' . filemtime($scriptFile) : '';
?>
<script src="<?= $basePath ?>assets/js/script.js<?= $scriptVersion ?>"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>
</body>

</html>
