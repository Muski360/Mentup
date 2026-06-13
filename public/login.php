<?php
$pageTitle = 'Login - Mentup';
$basePath = '';
$pageStyles = ['assets/css/auth.css'];
$bodyClass = 'auth-page';
$hideChrome = true;
require __DIR__ . '/../app/Views/layout/header.php';
?>

<section class="auth" aria-label="Login Mentup">
    <div class="auth__brand-panel">
        <a class="auth-logo" href="index.php" aria-label="P&aacute;gina inicial da Mentup">
            <img src="assets/img/logos/logo_1200x400.png" alt="Mentup">
        </a>

        <div class="auth__intro">
            <h1>Entre agora na melhor plataforma para organizar <span>seus campeonatos</span></h1>
            <p>Entre para gerenciar campeonatos, suas equipes, suas rodadas. Nossa miss&atilde;o &eacute; organizar sua
                competi&ccedil;&atilde;o.</p>
        </div>

        <div class="auth-security">
            <img src="assets/img/icon/shield.svg" alt="" aria-hidden="true">
            <div>
                <strong>Plataforma segura e confi&aacute;vel</strong>
                <span>Seus dados est&atilde;o protegidos.</span>
            </div>
        </div>
    </div>

    <div class="auth__form-panel">
        <section class="auth-card" aria-labelledby="auth-title">
            <header class="auth-card__header">
                <h2 id="auth-title">Bem-vindo!</h2>
                <p>Acesse sua conta agora, de forma totalmente segura.</p>
            </header>

            <form class="auth-form" action="#" method="post">
                <label class="field" for="email">
                    <span class="field__label">E-mail</span>
                    <span class="field__control">
                        <img class="field__icon" src="assets/img/icon/mail.svg" alt="" aria-hidden="true">
                        <input id="email" name="email" type="email" placeholder="seu@email.com" autocomplete="email"
                            required>
                    </span>
                </label>

                <label class="field" for="password">
                    <span class="field__label">Senha</span>
                    <span class="field__control">
                        <img class="field__icon" src="assets/img/icon/lock.svg" alt="" aria-hidden="true">
                        <input id="password" name="password" type="password" placeholder="************"
                            autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Mostrar senha"
                            aria-controls="password" data-password-toggle></button>
                    </span>
                </label>

                <div class="auth-form__help">
                    <a href="#">Esqueci minha senha</a>
                </div>

                <button class="btn btn--primary auth-submit" type="submit">
                    Entrar na conta
                    <span class="auth-submit__arrow" aria-hidden="true"></span>
                </button>
            </form>

            <div class="auth-divider"><span>Ou continue com</span></div>

            <button class="google-button" type="button">
                <img src="assets/img/icon/google-500x512.png" alt="" aria-hidden="true">
                Continuar com Google
            </button>

            <p class="auth-switch">
                <strong>N&atilde;o tem uma conta?</strong>
                <a href="register.php">Criar conta</a>
            </p>
        </section>
    </div>
</section>

<?php
require __DIR__ . '/../app/Views/layout/footer.php';
?>