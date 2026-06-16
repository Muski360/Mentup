<?php
$pageTitle = 'Mentup - Organize seus campeonatos';
$basePath = '';
$pageStyles = ['assets/css/index.css'];
require __DIR__ . '/../app/Views/layout/header.php';
?>

<section class="hero">
    <div class="container hero__grid">
        <div class="hero__content">
            <h1>Organize seus campeonatos com <span>mais facilidade</span></h1>
            <p>Gerencie times, partidas, tabelas e resultados em uma plataforma simples, moderna e feita para quem vive
                o esporte.</p>

            <div class="hero__actions">
                <a class="btn btn--primary btn--large" href="create-championship.php">
                    <?= mentupIcon('trophy') ?>
                    Criar um campeonato
                </a>
                <a class="btn btn--outline btn--large" href="#como-funciona">
                    <?= mentupIcon('circle-play', 'app-icon btn__icon-dark') ?>
                    Ver tutorial
                </a>
            </div>

            <div class="hero-benefits" aria-label="Beneficios principais">
                <div class="hero-benefit">
                    <?= mentupIcon('trophy') ?>
                    <span>Crie do<br>seu jeito.</span>
                </div>
                <div class="hero-benefit">
                    <?= mentupIcon('dumbbell') ?>
                    <span>Feito para<br>atletas.</span>
                </div>
                <div class="hero-benefit">
                    <?= mentupIcon('clock') ?>
                    <span>F&aacute;cil, r&aacute;pido e<br>inteligente.</span>
                </div>
            </div>
        </div>

        <div class="hero__media" aria-label="Previa do painel Mentup">
            <img src="assets/img/images/Video-placeholder.png" alt="Painel da Mentup com informacoes de campeonato">
        </div>
    </div>
</section>

<section class="features" id="recursos" aria-label="Recursos da plataforma">
    <div class="container feature-grid">
        <article class="feature-card">
            <?= mentupIcon('circle-check') ?>
            <h2>F&aacute;cil de usar</h2>
            <p>Enquanto voc&ecirc; perde tempo fazendo planilhas e se perdendo em papel, a Mentup facilita tudo para
                voc&ecirc;.</p>
        </article>

        <article class="feature-card">
            <?= mentupIcon('badge-dollar-sign') ?>
            <h2>Use gratuitamente</h2>
            <p>Use a Mentup agora gratuitamente, sem a necessidade de cadastrar seus dados financeiros.</p>
        </article>

        <article class="feature-card">
            <?= mentupIcon('medal') ?>
            <h2>Tabelas e campeonatos</h2>
            <p>Classifica&ccedil;&otilde;es e chaveamento feito automaticamente, com suporte a diferentes esportes.</p>
        </article>
    </div>
</section>

<section class="how-it-works" id="como-funciona">
    <div class="container">
        <h2 class="section-title">Como <span>funciona</span></h2>

        <div class="how-it-works__grid">
            <div class="how-it-works__copy">
                <h3>O <span>sistema completo</span> para seu campeonato!</h3>
                <p>Nossa miss&atilde;o &eacute; simplificar a cria&ccedil;&atilde;o de campeonatos e o gerenciamento de
                    times, promovendo um ambiente esportivo mais organizado, justo e transparente.</p>

                <ul class="check-list">
                    <li><?= mentupIcon('check') ?>Cria&ccedil;&atilde;o de campeonatos de esportes</li>
                    <li><?= mentupIcon('check') ?>Cadastro de times e jogadores</li>
                    <li><?= mentupIcon('check') ?>Fases de grupos e chaveamento autom&aacute;tico</li>
                    <li><?= mentupIcon('check') ?>Resultados e classifica&ccedil;&atilde;o autom&aacute;ticos</li>
                    <li><?= mentupIcon('check') ?>Login e cria&ccedil;&atilde;o de conta para organizadores</li>
                </ul>
            </div>

            <a class="video-preview" href="#contato" aria-label="Assistir apresentacao da Mentup">
                <img src="assets/img/images/Video-placeholder.png" alt="Video de apresentacao da plataforma Mentup">
            </a>
        </div>
    </div>
</section>

<section class="contact-cta" id="contato">
    <div class="container">
        <div class="support-card">
            <img class="support-card__logo" src="assets/img/logos/logo_1000x1000.png" alt="Mentup">

            <div class="support-card__content">
                <p>Est&aacute; com d&uacute;vidas?</p>
                <h2>Fale com nossa equipe!</h2>
                <a class="btn btn--whatsapp" href="https://wa.me/5519971344281" target="_blank" rel="noopener">
                    <?= mentupIcon('message-circle') ?>
                    Conversar pelo WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<section class="final-cta">
    <div class="container">
        <div class="final-cta__card">
            <div class="final-cta__copy">
                <h2>Pronto para organizar <span>seu campeonato?</span></h2>
                <p>Comece agora organizar sua competitividade!</p>
            </div>

            <a class="btn btn--primary final-cta__button" href="register.php">Comece agora</a>
            <img class="final-cta__trophy" src="assets/img/images/CTA-trophy.png" alt="" aria-hidden="true">
        </div>
    </div>
</section>

<?php
require __DIR__ . '/../app/Views/layout/footer.php';
?>
