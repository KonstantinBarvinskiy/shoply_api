<?php

class Block {

    // подключение к БД таблице
    private $conn;
    private $table_name = "blocks";// Блоки>

    // свойства объекта
    public $id;           // Идентификатор  (Int)
    public $order;        // Порядок
    public $name;         // Наименование блока
    public $callname;     // Имя для вызова
    public $show;         // Показывать (Y/N)
    public $deleted;      // Удален (Y/N)
    public $created;      // Дата создания
    public $modified;     // Дата последнего изменения
    public $text;         // Содержание блока

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {

        $hop = $this->item;

        // Вставляем запрос
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

    // обновить запись
    public function update() {

        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            if ($key != 'text') $value = htmlspecialchars(strip_tags($value));
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` = {$this->id}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить запись
    public function delete(){

        $query = "UPDATE {$this->table_name}
            SET `deleted` = 'Y'
            WHERE id = {$this->id}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
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
                $query .= "`{$key}` = '{$value}' AND ";
            }
            $query = substr($query, 0, -5);
        }

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }
}