<?php
// используем для подключения к базе данных MySQL
class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct($host, $db, $name, $pass) {
        $this->host = $host;
        $this->db_name = $db;
        $this->username = $name;
        $this->password = $pass;
    }

    // получаем соединение с базой данных
    public function getConnection() {

        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name .";charset=utf8", $this->username, $this->password);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>