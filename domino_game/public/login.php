<?php
require_once __DIR__ . '/../src/autoload.php';

use Game\Auth\Auth;

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: lobby.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header("Location: lobby.php");
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dominó Multiplayer - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 20px; background: var(--card-bg); border-radius: 8px; }
        .error { color: var(--danger-color); margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%">Entrar</button>
        </form>
        <p style="margin-top: 15px; text-align: center;">Não tem conta? <a href="register.php" style="color: var(--primary-color);">Cadastre-se</a></p>
    </div>
</body>
</html>
