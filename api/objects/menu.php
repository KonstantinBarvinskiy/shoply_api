<?php

class Menu {

    // подключение к БД таблице
    private $conn;
    private $table_name = "menu";// Изображения

    // свойства объекта
    public $id;      // Идентификатор  (Int)
    public $name;    // Наименование
    public $show;    // Показывать (Y/N)
    public $deleted; // Удален (Y/N)

    public $item;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $hop = $this->item;

        $query = "INSERT INTO `{$this->table_name}` SET ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $hop = $this->item;

        $query = "UPDATE {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` = {$this->id}";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete(){
        $query = "UPDATE {$this->table_name}
            SET `deleted` = 'Y'
            WHERE id = {$this->id}";

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
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
}