<?php

class Settings {

    // подключение к БД таблице
    private $conn;
    private $table_name = "settings";       // Статусы

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $module;     // Модуль
    public $top;        // Подраздел в модуле
    public $name;       // Наименование
    public $callname;   // Имя для вызова
    public $value;      // Значение

    public $filter;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // обновить запись
    public function update() {
        $i = 0;
        $j = count($this->filter);
        $err = '';
        foreach ($this->filter as $key=>$value) {
            $query = "UPDATE {$this->table_name} 
                SET `value` = '{$value}' 
                WHERE `callname` = '{$key}'";
            $stmt = $this->conn->prepare($query);
            if ($stmt->execute()) {
                $i++;
            }
        }
        if ($i==0) return false;
        elseif ($i>0) return true;
        return false;
    }

    public function read() {
        $query = "SELECT * FROM {$this->table_name}";
        if (isset($this->filter)) {
            $query .= " WHERE ";
            foreach ($this->filter as $key=>$where) {
                $query .= "`{$key}` = '{$where}' AND ";
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