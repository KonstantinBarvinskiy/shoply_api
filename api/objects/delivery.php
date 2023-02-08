<?php

class Delivery {

    // подключение к БД таблице
    private $conn;
    private $table_name = "shop_delivery";       // Статусы

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $order;      // Порядок  (Int)
    public $type;       // Тип
    public $name;       // Наименование
    public $show;       // Используется?

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // обновить запись
    public function update() {

        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` = '{$this->id}'";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {

        // выбираем все записи
        $query = "SELECT * FROM {$this->table_name}";
        if (isset($this->item)) {
            $query .= " WHERE ";
            foreach ($this->item as $key => $value) {
                $query .= "`{$key}` = '$value' AND ";
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