<?php

class Address {

    // подключение к БД таблице
    private $conn;
    private $table_name = "address";// Адрес

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $user_id;    // Идентификатор пользователя
    public $main;       // Основной (Y/N)
    public $city;       // Город
    public $street;     // Улица
    public $house;      // Дом
    public $flat;       // Квартира

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {
        $hop = $this->item;

        if (!db()->query_value("SELECT `id` FROM `{$this->table_name}` WHERE `user_id` = '{$hop['user_id']}'"))
            $hop['main'] = 'Y';

        $query = "INSERT INTO {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
            $query .= "{$key} = '{$value}', ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return $query;
    }

    public function update() {
        $hop = $this->item;

        if ($this->main == 'Y') {
            $user = db()->query_value("SELECT `user_id` FROM `{$this->table_name}` WHERE `id` = {$this->id}");
            $query = "UPDATE `{$this->table_name}` SET `main` = 'N' WHERE `user_id` = '{$user}'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }

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
        $query = "DELETE FROM {$this->table_name}
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