<?php

class Promocode {

    // подключение к БД таблице
    private $conn;
    private $table_name = "promocodes";       // Статусы

    // свойства объекта
    public $id;     // Идентификатор  (Int)
    public $code;   // Код
    public $price;  // Номинал, в процентах или абсолютной сумме
    public $used;   // Использован?/Действителен? (Y/N)
    public $multi;  // Многоразовый (Y/N)

    public $item;
    public $quanity;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {

        $hop = $this->item;
        $query = "INSERT INTO " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    function generate() {
        for($i=0; $i<$this->quanity; $i++) {
            db()->query("INSERT INTO `{$this->table_name}` 
                SET `code` = '".$this->gen_code(6)."', `price` = '{$this->price}'");
        }
        return $i;
    }

    // обновить запись
    public function update() {

        $hop = $this->item;

        if (isset($hop)) {
            // Вставляем запрос
            $query = "UPDATE " . $this->table_name . " SET ";
            foreach ($hop as $key => $value) {
                $query .= "`{$key}` = '{$value}', ";
            }
            $query = substr($query, 0, -2);
            $query .= " WHERE id IN ($this->id)";

            // подготовка запроса
            $stmt = $this->conn->prepare($query);
            $base_work = true;
        }

        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных
        if(isset($base_work)) {
            if ($stmt->execute()) {
                return true;
            }
        }

        return false;
    }

    // обновить запись
    public function delete(){

        $query = "DELETE FROM {$this->table_name}
            WHERE id IN ({$this->id})";

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

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    function gen_code($n=1) {
        $ret = '';
        for($i=0; $i<$n; $i++) {
            $d = rand(0,61);
            if($d<10) $ret .= chr($d+48);
            elseif($d<36) $ret .= chr($d+55);
            else $ret .= chr($d+61);
        }
        return $ret;
    }
}