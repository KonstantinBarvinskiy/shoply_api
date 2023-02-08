<?php

class Paymethod {

    // подключение к БД таблице
    private $conn;
    private $table_name = "shop_paymethods";       // Статусы

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $name;       // Наименование
    public $type;       // Тип
    public $show;       // Используется?

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // обновить запись
    public function update() {

        if (($this->id > 1) and ($this->show == "Y")) {
            $query = "UPDATE " . $this->table_name . "
            SET `show` = 'N'
            WHERE `id` > 1";
            $stmtt = $this->conn->prepare($query);
            $stmtt->execute();
            $query = "UPDATE " . $this->table_name . " 
            SET `show` = 'Y' 
            WHERE `id` = {$this->id}";
            $stmt = $this->conn->prepare($query);
        }
        else {
            $query = "UPDATE " . $this->table_name . " 
            SET `show` = '{$this->show}' 
            WHERE `id` = {$this->id}";
            $stmt = $this->conn->prepare($query);
        }

        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {

        // выбираем все записи
        $query = "SELECT * FROM {$this->table_name}";
        if (isset($this->id)) {
            $query .= " WHERE id = {$this->id}";
        }

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }
}