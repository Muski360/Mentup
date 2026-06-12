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
            <p>Gerencie times, partidas, tabelas e resultados em uma plataforma simples, moderna e feita para quem vive o esporte.</p>

            <div class="hero__actions">
                <a class="btn btn--primary btn--large" href="criar-campeonato.php">
                    <img src="assets/img/icon/home_trophy.svg" alt="" aria-hidden="true">
                    Criar um campeonato
                </a>
                <a class="btn btn--outline btn--large" href="#como-funciona">
                    <img class="btn__icon-dark" src="assets/img/icon/camera.svg" alt="" aria-hidden="true">
                    Ver tutorial
                </a>
            </div>

            <div class="hero-benefits" aria-label="Beneficios principais">
                <div class="hero-benefit">
                    <img src="assets/img/icon/home_trophy.svg" alt="" aria-hidden="true">
                    <span>Crie do<br>seu jeito.</span>
                </div>
                <div class="hero-benefit">
                    <img src="assets/img/icon/dumbbell.svg" alt="" aria-hidden="true">
                    <span>Feito para<br>atletas.</span>
                </div>
                <div class="hero-benefit">
                    <img src="assets/img/icon/clock-lines.svg" alt="" aria-hidden="true">
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
            <img src="assets/img/icon/check.svg" alt="" aria-hidden="true">
            <h2>F&aacute;cil de usar</h2>
            <p>Enquanto voc&ecirc; perde tempo fazendo planilhas e se perdendo em papel, a Mentup facilita tudo para voc&ecirc;.</p>
        </article>

        <article class="feature-card">
            <img src="assets/img/icon/tag.svg" alt="" aria-hidden="true">
            <h2>Use gratuitamente</h2>
            <p>Use a Mentup agora gratuitamente, sem a necessidade de cadastrar seus dados financeiros.</p>
        </article>

        <article class="feature-card">
            <img src="assets/img/icon/medal.svg" alt="" aria-hidden="true">
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
                <p>Nossa miss&atilde;o &eacute; simplificar a cria&ccedil;&atilde;o de campeonatos e o gerenciamento de times, promovendo um ambiente esportivo mais organizado, justo e transparente.</p>

                <ul class="check-list">
                    <li>Cria&ccedil;&atilde;o de campeonatos de esportes</li>
                    <li>Cadastro de times e jogadores</li>
                    <li>Fases de grupos e chaveamento autom&aacute;tico</li>
                    <li>Resultados e classifica&ccedil;&atilde;o autom&aacute;ticos</li>
                    <li>Login e cria&ccedil;&atilde;o de conta para organizadores</li>
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
                <a class="btn btn--whatsapp" href="https://wa.me/5599999999999" target="_blank" rel="noopener">
                    <img src="assets/img/icon/whatsapp-753x753.png" alt="" aria-hidden="true">
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

            <a class="btn btn--primary final-cta__button" href="cadastro.php">Comece agora</a>
            <img class="final-cta__trophy" src="assets/img/images/CTA-trophy.png" alt="" aria-hidden="true">
        </div>
    </div>
</section>

<?php
require __DIR__ . '/../app/Views/layout/footer.php';
?>
