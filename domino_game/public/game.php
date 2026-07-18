<?php
require_once __DIR__ . '/../src/autoload.php';

use Game\Auth\Auth;
use Game\Config\Database;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['room'])) {
    header("Location: lobby.php");
    exit;
}

$roomId = (int)$_GET['room'];
$userId = $auth->getUserId();

$dbConnection = new Database();
$db = $dbConnection->getConnection();

// Verifica se a sala existe
$stmt = $db->prepare("SELECT * FROM salas WHERE id = :id");
$stmt->execute([':id' => $roomId]);
$room = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: lobby.php");
    exit;
}

// Verifica se o usuário está na sala
$stmtCheck = $db->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id = :sala_id AND usuario_id = :usuario_id");
$stmtCheck->execute([':sala_id' => $roomId, ':usuario_id' => $userId]);
$inRoom = $stmtCheck->fetchColumn() > 0;

if (!$inRoom) {
    header("Location: lobby.php");
    exit;
}

// Conta jogadores atuais para saber se pode iniciar
$stmtCount = $db->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id = :sala_id");
$stmtCount->execute([':sala_id' => $roomId]);
$currentPlayers = (int)$stmtCount->fetchColumn();

$canStart = ($currentPlayers >= 2 && $room['status'] === 'aguardando');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dominó Multiplayer - Mesa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/game.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .waiting-screen {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 100;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="game-wrapper">
        <header class="game-header">
            <h2>Mesa: <span id="room-name-display"><?= htmlspecialchars($room['nome']) ?></span> (<?= $currentPlayers ?>/<?= $room['max_jogadores'] ?>)</h2>
            <button class="btn btn-secondary" onclick="window.location.href='lobby.php'">Sair da Mesa</button>
        </header>

        <div class="table-area">
            <?php if ($room['status'] === 'aguardando' && !$canStart): ?>
                <div class="waiting-screen" id="waiting-screen">
                    <h2>Aguardando Oponentes...</h2>
                    <p>É necessário pelo menos 2 jogadores para iniciar.</p>
                    <p>Jogadores atuais: <?= $currentPlayers ?></p>
                </div>
            <?php elseif ($room['status'] === 'aguardando' && $canStart): ?>
                <div class="waiting-screen" id="waiting-screen" style="background: rgba(0,0,0,0.5);">
                    <h2>Pronto para começar!</h2>
                    <p>Aguardando o criador iniciar a partida...</p>
                    <button class="btn btn-success" style="margin-top:20px;">Iniciar Partida (Mock)</button>
                </div>
            <?php endif; ?>

            <!-- Restante da mesa continua o mesmo do frontend mock -->
            <!-- Adversário Topo -->
            <div class="opponent opponent-top" id="opp-top">
                <div class="avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" alt="Avatar">
                </div>
                <div class="info">
                    <span class="name">Carlos</span>
                    <span class="piece-count">7 Peças</span>
                </div>
            </div>

            <!-- Adversário Esquerda -->
            <div class="opponent opponent-left" id="opp-left">
                <div class="avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Ana" alt="Avatar">
                </div>
                <div class="info">
                    <span class="name">Ana</span>
                    <span class="piece-count">5 Peças</span>
                </div>
            </div>

            <!-- Adversário Direita -->
            <div class="opponent opponent-right" id="opp-right">
                <div class="avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Beto" alt="Avatar">
                </div>
                <div class="info">
                    <span class="name">Beto</span>
                    <span class="piece-count">6 Peças</span>
                </div>
            </div>

            <!-- Tabuleiro Central -->
            <div class="board" id="game-board">
                <div class="domino-piece on-board double" style="transform: translate(0, 0);">
                    <div class="half"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
                    <div class="divider"></div>
                    <div class="half"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
                </div>
            </div>

            <!-- Dorminhoco (Monte) -->
            <div class="boneyard" id="boneyard">
                <div class="domino-piece hidden"></div>
                <span>Comprar</span>
            </div>
        </div>

        <!-- Mão do Jogador Atual -->
        <div class="player-hand-area">
            <div class="turn-indicator active" id="my-turn-indicator">Sua Vez!</div>
            <div class="controls">
                <button class="btn btn-warning" id="pass-turn-btn" disabled>Passar a Vez</button>
            </div>
            <div class="player-hand" id="my-hand">
                <div class="domino-piece playable" draggable="true">
                    <div class="half"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
                    <div class="divider"></div>
                    <div class="half"><span class="dot"></span><span class="dot"></span></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
