<?php

class Slider {

    // подключение к БД таблице
    private $conn;
    private $table_name = "slider";// Блоки>

    // свойства объекта
    public $id;       // Идентификатор  (Int)
    public $order;    // Порядок
    public $name;     // Название
    public $link;     // Ссылка
    public $link_text;// Текст ссылки
    public $is_light; // Белый текст (Y/N)
    public $show;     // Показыать (Y/N)
    public $deleted;  // Удален (Y/N)

    public $item;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        $hop = $this->item;
        $query = "INSERT INTO {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            if ($key != 'text') $value = htmlspecialchars(strip_tags($value));
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
        $query = "UPDATE {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            if ($key != 'text') $value = htmlspecialchars(strip_tags($value));
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
        $query .= " WHERE `deleted` = 'N' AND ";
        if (isset($this->item)) {
            foreach ($this->item as $key => $value) {
                $query .= "`{$key}` = '{$value}' AND ";
            }
        }
        $query = substr($query, 0, -5);
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }
}