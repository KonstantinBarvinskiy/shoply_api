<?php

class Module {

    // подключение к БД таблице
    private $conn;
    private $table_name = "settings_modules";// Адрес

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $block;      // Блок
    public $name;       // Имя модуля
    public $callname;   // Имя вызова
    public $value;      // Активен (Y/N)

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM {$this->table_name}";
        if (isset($this->item)) {
            $query .= " WHERE ";
            foreach ($this->item as $key => $value) {
                $query .= "`{$key}` = '{$value}' AND ";
            }
            $query = substr($query, 0, -5);
        }

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    public function readOne() {
        $query = "SELECT *
            FROM {$this->table_name}
            WHERE ";
        if (isset($this->id)) $query .= "id = {$this->id}";
        elseif (isset($this->user_id)) $query .= "user_id = {$this->user_id} AND main = 'Y'";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return $query;
    }
}