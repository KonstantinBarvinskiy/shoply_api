<?php

class Brand {

    // подключение к БД таблице
    private $conn;
    private $table_name = "products_brands";     // Бренды
    private $table_i = "images";                 // Изображения

    // свойства объекта
    public $id;           // Идентификатор  (Int)
    public $order;        // Порядок
    public $name;         // Наименование
    public $nav;          // URI ссылка
    public $text;         // Описание
    public $show;         // Показывать (Y/N)
    public $deleted;      // Удален (Y/N)
    public $show_main;    // Показывать на главной (Y/N)
    public $created;      // Дата создания
    public $modified;     // Дата последнего изменения
    public $href_official;// Ссылка на офф сайт

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
            $value = htmlspecialchars(strip_tags($value));
            $query .= "{$key} = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // обновить запись
    public function update() {

        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
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

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }

    public function readOne() {

        // выбираем все записи
        $query = "SELECT b.*, i.src, i.md5 
            FROM {$this->table_name} b
            LEFT JOIN {$this->table_i} i ON 
                i.module = 'Catalog' 
                AND i.module_id = 0
                AND i.alter_key = '{$this->table_name}-{$this->id}'
            WHERE b.id = {$this->id}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return $stmt;
        }

        return $query;

    }
}