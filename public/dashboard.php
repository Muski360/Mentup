<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h1>Olá,
    <?= htmlspecialchars($_SESSION['user_name']) ?>
</h1>