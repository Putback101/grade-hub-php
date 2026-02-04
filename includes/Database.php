<?php

class Database {
    private $host;
    private $db_name;
    private $user;
    private $pass;
    private $port;
    private $conn;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->port = DB_PORT;
    }

    public function connect() {
        $this->conn = new mysqli(
            $this->host,
            $this->user,
            $this->pass,
            $this->db_name,
            $this->port
        );

        if ($this->conn->connect_error) {
            die('Connection failed: ' . $this->conn->connect_error);
        }

        // Set charset to UTF-8
        $this->conn->set_charset("utf8mb4");

        return $this->conn;
    }

    public function disconnect() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
            $instance->connect();
        }
        return $instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
