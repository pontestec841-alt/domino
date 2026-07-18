<?php
require_once __DIR__ . '/../src/autoload.php';

use Game\Auth\Auth;
use Game\Config\Database;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    $auth->logout();
    header("Location: login.php");
    exit;
}

$dbConnection = new Database();
$db = $dbConnection->getConnection();
$error = '';
$success = '';

// Processar criação de sala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    $nomeSala = $_POST['room_name'] ?? '';
    $maxJogadores = (int)($_POST['max_players'] ?? 0);

    if (!empty($nomeSala) && $maxJogadores >= 2 && $maxJogadores <= 4) {
        $stmt = $db->prepare("INSERT INTO salas (nome, max_jogadores, status) VALUES (:nome, :max, 'aguardando')");
        $stmt->bindParam(':nome', $nomeSala);
        $stmt->bindParam(':max', $maxJogadores);
        if ($stmt->execute()) {
            $roomId = $db->lastInsertId();
            // Inserir o criador na sala automaticamente
            $stmtJoin = $db->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id) VALUES (:sala_id, :usuario_id)");
            $stmtJoin->bindParam(':sala_id', $roomId);
            $userId = $auth->getUserId();
            $stmtJoin->bindParam(':usuario_id', $userId);
            $stmtJoin->execute();

            header("Location: game.php?room=" . $roomId);
            exit;
        } else {
            $error = "Erro ao criar a sala.";
        }
    } else {
        $error = "Dados inválidos para criação da sala.";
    }
}

// Processar entrar na sala pelo Lobby (quando não é o criador)
if (isset($_GET['join'])) {
    $roomIdToJoin = (int)$_GET['join'];
    $userId = $auth->getUserId();

    // Verifica se já está na sala
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id = :sala_id AND usuario_id = :usuario_id");
    $stmtCheck->execute([':sala_id' => $roomIdToJoin, ':usuario_id' => $userId]);
    $alreadyIn = $stmtCheck->fetchColumn() > 0;

    if (!$alreadyIn) {
        // Verifica se a sala existe, está aguardando e se tem vaga
        $stmtRoom = $db->prepare("
            SELECT s.max_jogadores, s.status, (SELECT COUNT(*) FROM sala_jogadores WHERE sala_id = s.id) as current_players
            FROM salas s WHERE s.id = :sala_id
        ");
        $stmtRoom->execute([':sala_id' => $roomIdToJoin]);
        $roomData = $stmtRoom->fetch(\PDO::FETCH_ASSOC);

        if ($roomData && $roomData['status'] === 'aguardando') {
            if ($roomData['current_players'] < $roomData['max_jogadores']) {
                $stmtJoin = $db->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id) VALUES (:sala_id, :usuario_id)");
                if ($stmtJoin->execute([':sala_id' => $roomIdToJoin, ':usuario_id' => $userId])) {
                     header("Location: game.php?room=" . $roomIdToJoin);
                     exit;
                } else {
                    $error = "Erro ao entrar na sala.";
                }
            } else {
                $error = "Esta sala está cheia.";
            }
        } else {
            $error = "Sala indisponível ou jogo já iniciado.";
        }
    } else {
         header("Location: game.php?room=" . $roomIdToJoin);
         exit;
    }
}

// Buscar salas
$stmtSalas = $db->query("
    SELECT s.id, s.nome, s.max_jogadores, s.status,
           (SELECT COUNT(*) FROM sala_jogadores WHERE sala_id = s.id) as players_count
    FROM salas s
    ORDER BY s.data_criacao DESC
");
$salas = $stmtSalas->fetchAll(\PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dominó Multiplayer - Lobby</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-error { background-color: rgba(231, 76, 60, 0.2); border: 1px solid var(--danger-color); color: var(--danger-color); }
        .alert-success { background-color: rgba(46, 204, 113, 0.2); border: 1px solid var(--success-color); color: var(--success-color); }
    </style>
</head>
<body>
    <header class="main-header">
        <h1>Dominó Multiplayer</h1>
        <div class="user-info">
            <span>Bem-vindo, <strong><?= htmlspecialchars($auth->getUsername()) ?></strong>!</span>
            <a href="?logout=1" class="btn btn-danger">Sair</a>
        </div>
    </header>

    <main class="lobby-container">
        <section class="create-room">
            <h2>Criar Nova Sala</h2>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="create_room">
                <div class="form-group">
                    <label for="room_name">Nome da Sala:</label>
                    <input type="text" id="room_name" name="room_name" required placeholder="Ex: Sala dos Campeões">
                </div>
                <div class="form-group">
                    <label for="max_players">Número de Jogadores:</label>
                    <select id="max_players" name="max_players" required>
                        <option value="2">2 Jogadores</option>
                        <option value="3">3 Jogadores</option>
                        <option value="4">4 Jogadores</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Criar e Entrar</button>
            </form>
        </section>

        <section class="room-list">
            <h2>Salas Disponíveis</h2>
            <div class="rooms-grid">
                <?php if (count($salas) === 0): ?>
                    <p>Nenhuma sala disponível no momento. Crie uma!</p>
                <?php else: ?>
                    <?php foreach ($salas as $sala): ?>
                        <div class="room-card">
                            <h3><?= htmlspecialchars($sala['nome']) ?></h3>
                            <p>Jogadores: <?= $sala['players_count'] ?> / <?= $sala['max_jogadores'] ?></p>
                            <?php if ($sala['status'] === 'aguardando'): ?>
                                <p class="status waiting">Aguardando...</p>
                                <?php if ($sala['players_count'] < $sala['max_jogadores']): ?>
                                    <a href="?join=<?= $sala['id'] ?>" class="btn btn-success">Entrar</a>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Cheia</button>
                                <?php endif; ?>
                            <?php elseif ($sala['status'] === 'em_jogo'): ?>
                                <p class="status playing">Em Jogo</p>
                                <button class="btn btn-disabled" disabled>Jogando</button>
                            <?php else: ?>
                                <p class="status" style="color: gray;">Finalizada</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
