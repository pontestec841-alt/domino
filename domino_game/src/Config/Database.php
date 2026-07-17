<?php

namespace Game\Config;

use PDO;
use PDOException;

class Database
{
    private $host = '127.0.0.1';
    private $db_name = 'domino_game';
    private $username = 'root'; // Change according to your environment
    private $password = ''; // Change according to your environment
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
