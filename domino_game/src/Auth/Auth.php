<?php

namespace Game\Auth;

use Game\Config\Database;
use PDO;

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($username, $password)
    {
        $query = "SELECT id FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return ["success" => false, "message" => "Usuário já existe."];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertQuery = "INSERT INTO usuarios (username, senha) VALUES (:username, :senha)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':senha', $hashedPassword);

        if ($insertStmt->execute()) {
            return ["success" => true, "message" => "Cadastro realizado com sucesso."];
        }

        return ["success" => false, "message" => "Erro ao registrar usuário."];
    }

    public function login($username, $password)
    {
        $query = "SELECT id, username, senha FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['senha'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                return ["success" => true, "message" => "Login realizado com sucesso."];
            }
        }

        return ["success" => false, "message" => "Credenciais inválidas."];
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();
        return ["success" => true, "message" => "Logout realizado com sucesso."];
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUsername()
    {
        return $_SESSION['username'] ?? null;
    }
}
