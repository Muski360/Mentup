<?php
$pageTitle = $pageTitle ?? 'Mentup - Organize seus campeonatos';
$basePath = $basePath ?? '';
$bodyClass = $bodyClass ?? '';
$hideChrome = $hideChrome ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Mentup &eacute; uma plataforma simples para organizar campeonatos, times, partidas e resultados esportivos.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/components.css">
    <?php foreach (($pageStyles ?? []) as $stylesheet): ?>
        <link rel="stylesheet" href="<?= $basePath . htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>

<body<?= $bodyClass ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <?php if (!$hideChrome): ?>
    <header class="site-header" id="inicio">
        <div class="container header__inner">
            <a class="brand" href="index.php" aria-label="P&aacute;gina inicial da Mentup">
                <img src="<?= $basePath ?>assets/img/logos/logo_1200x400.png" alt="Mentup">
            </a>

            <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false"
                aria-controls="primary-navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="primary-nav" id="primary-navigation" aria-label="Navega&ccedil;&atilde;o principal">
                <a href="index.php">In&iacute;cio</a>
                <a href="#recursos">Recursos</a>
                <a href="campeonatos.php">Campeonatos</a>
                <a href="#contato">Contato</a>
            </nav>

            <div class="header-actions">
                <a class="btn btn--ghost" href="login.php">Entrar</a>
                <a class="btn btn--primary" href="register.php">Comece agora</a>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <main>
