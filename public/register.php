<?php
// Backend para registrar conta, com PDO e conexao com banco de dados.
session_start();

$error = '';
$name = '';
$email = '';

if (isset($_SESSION['user_id'])) {
    $_SESSION['snack'] = [
        'type' => 'success',
        'message' => "Voc\u{00EA} j\u{00E1} est\u{00E1} logado.",
    ];

    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plainPassword = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (strlen($name) < 2) {
        $error = 'O nome deve conter pelo menos 2 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'O email informado não é válido.';
    } elseif (strlen($plainPassword) < 6) {
        $error = 'A senha deve conter pelo menos 6 caracteres.';
    } elseif ($plainPassword !== $passwordConfirmation) {
        $error = 'A senha deve ser a mesma.';
    } else {
        try {
            require_once __DIR__ . '/../config/database.php';

            $stmt = $pdo->prepare("
                select id
                from users
                where email = :email
                limit 1
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            if ($stmt->fetch()) {
                $error = 'Este email já está registrado. Tente outro ou faça login.';
            } else {
                $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    insert into users (
                        name,
                        email,
                        password_hash
                    ) values (
                        :name,
                        :email,
                        :password_hash
                    )
                    returning id, name, email
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password_hash' => $passwordHash
                ]);

                $user = $stmt->fetch();

                // Troca o ID da sessao antes de autenticar o usuario recem-cadastrado.
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Erro ao criar conta.';
        }
    }
}

$pageTitle = 'Criar conta - Mentup';
$basePath = '';
$pageStyles = ['assets/css/auth.css'];
$bodyClass = 'auth-page';
$hideChrome = true;
require __DIR__ . '/../app/Views/layout/header.php';
?>

<section class="auth auth--register" aria-label="Cadastro Mentup">
    <div class="auth__brand-panel">
        <a class="auth-logo" href="index.php" aria-label="P&aacute;gina inicial da Mentup">
            <img src="assets/img/logos/logo_1200x400.png" alt="Mentup">
        </a>

        <div class="auth__intro">
            <h1>Comece agora na melhor plataforma para organizar <span>seus campeonatos</span></h1>
            <p>Crie sua conta para cadastrar campeonatos, acompanhar equipes e manter cada rodada organizada em um
                s&oacute; lugar.</p>
        </div>

        <div class="auth-security">
            <?= mentupIcon('shield-check') ?>
            <div>
                <strong>Plataforma segura e confi&aacute;vel</strong>
                <span>Seus dados est&atilde;o protegidos.</span>
            </div>
        </div>
    </div>

    <div class="auth__form-panel">
        <section class="auth-card auth-card--register" aria-labelledby="auth-title">
            <header class="auth-card__header">
                <h2 id="auth-title">Crie sua conta!</h2>
                <p>Cadastre-se para come&ccedil;ar a organizar seus campeonatos.</p>
            </header>

            <?php if ($error): ?>
                <div class="auth-alert" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="#" method="post">
                <label class="field" for="name">
                    <span class="field__label">Nome</span>
                    <span class="field__control field__control--plain">
                        <input id="name" name="name" type="text" placeholder="Seu nome completo" autocomplete="name"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                    </span>
                </label>

                <label class="field" for="email">
                    <span class="field__label">E-mail</span>
                    <span class="field__control">
                        <?= mentupIcon('mail', 'app-icon field__icon') ?>
                        <input id="email" name="email" type="email" placeholder="seu@email.com" autocomplete="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                    </span>
                </label>

                <label class="field" for="password">
                    <span class="field__label">Senha</span>
                    <span class="field__control">
                        <?= mentupIcon('lock-keyhole', 'app-icon field__icon') ?>
                        <input id="password" name="password" type="password" placeholder="************"
                            autocomplete="new-password" required>
                        <button class="password-toggle" type="button" aria-label="Mostrar senha"
                            aria-controls="password" data-password-toggle>
                            <?= mentupIcon('eye', 'app-icon password-toggle__icon password-toggle__icon--show') ?>
                            <?= mentupIcon('eye-off', 'app-icon password-toggle__icon password-toggle__icon--hide') ?>
                        </button>
                    </span>
                </label>

                <label class="field" for="password-confirmation">
                    <span class="field__label">Confirmar senha</span>
                    <span class="field__control">
                        <?= mentupIcon('lock-keyhole', 'app-icon field__icon') ?>
                        <input id="password-confirmation" name="password_confirmation" type="password"
                            placeholder="************" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" aria-label="Mostrar senha"
                            aria-controls="password-confirmation" data-password-toggle>
                            <?= mentupIcon('eye', 'app-icon password-toggle__icon password-toggle__icon--show') ?>
                            <?= mentupIcon('eye-off', 'app-icon password-toggle__icon password-toggle__icon--hide') ?>
                        </button>
                    </span>
                </label>

                <button class="btn btn--primary auth-submit" type="submit">
                    Criar conta
                    <?= mentupIcon('arrow-right', 'app-icon auth-submit__arrow') ?>
                </button>
            </form>

            <div class="auth-divider"><span>Ou continue com</span></div>

            <button class="google-button" type="button">
                <?= mentupIcon('log-in') ?>
                Continuar com Google
            </button>

            <p class="auth-switch">
                <strong>J&aacute; tem uma conta?</strong>
                <a href="login.php">Entrar</a>
            </p>
        </section>
    </div>
</section>

<?php
require __DIR__ . '/../app/Views/layout/footer.php';
?>
