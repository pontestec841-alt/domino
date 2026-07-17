CREATE DATABASE IF NOT EXISTS domino_game;
USE domino_game;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    status ENUM('aguardando', 'em_jogo', 'finalizada') DEFAULT 'aguardando',
    max_jogadores INT NOT NULL CHECK (max_jogadores BETWEEN 2 AND 4),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sala_jogadores (
    sala_id INT NOT NULL,
    usuario_id INT NOT NULL,
    ordem_jogada INT,
    PRIMARY KEY (sala_id, usuario_id),
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT NOT NULL,
    estado_tabuleiro JSON DEFAULT NULL,
    mao_jogadores JSON DEFAULT NULL,
    vez_do_usuario_id INT DEFAULT NULL,
    status ENUM('em_andamento', 'fechado', 'finalizada') DEFAULT 'em_andamento',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (vez_do_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
