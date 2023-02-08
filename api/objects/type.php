<?php

class Type {

    // подключение к БД таблице
    private $conn;
    private $table_tg = "type_groups";    // Товары
    private $table_tn = "type_names";     // Категории товаров
    private $table_tp = "type_products";  // Изображения

    // свойства объекта
    // Groups
    public $group_id;
    public $topic_id;
    public $order;
    public $name;
    // Names
    public $id;
    public $type;
    public $unit;
    public $select;
    public $main;
    public $descr;
    // Products
    public $type_id;
    public $product_id;
    public $value;
    // Service
    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function createGroup() {

        $hop = $this->item;
        $query = "INSERT INTO " . $this->table_tg . " SET ";
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

    function createType() {

        $hop = $this->item;
        $query = "INSERT INTO " . $this->table_tn . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return true;
        }

        return $query;
    }

    public function updateGroup() {
        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE " . $this->table_tg . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE id = $this->id";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function updateType() {
        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE " . $this->table_tn . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE id = $this->id";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function updateProduct() {
        $i = 0;
        foreach ($this->item as $typee) {
            if ($isset = db()->query_first("SELECT * FROM {$this->table_tp}
                WHERE `type_id` = {$typee['type_id']} AND `product_id` = {$this->product_id}")) {
                db()->query("UPDATE {$this->table_tp} 
                SET `value` = '{$typee['value']}' 
                WHERE `type_id` = {$typee['type_id']} AND `product_id` = {$this->product_id}");
                $i++;
            }
            else {
                db()->query("INSERT INTO {$this->table_tp} 
                SET `value` = '{$typee['value']}',
                    `type_id` = {$typee['type_id']},
                    `product_id` = {$this->product_id}");
                $i++;
            }
        }
        return $i;
    }

    public function readGroups() {
        if (isset($this->product_id))
            $this->topic_id = db()->query_value("SELECT `top` FROM `products` WHERE id = {$this->product_id}");
            if (empty($this->topic_id)) return false;
        $query = "SELECT * FROM {$this->table_tg}";
        if (isset($this->topic_id)) {
            $query .= " WHERE `topic_id` = {$this->topic_id}";
        }
        $query = db()->rows($query);
        if (!empty($query)) return $query;
        else return false;
//        $stmt = $this->conn->prepare($query);
//        if($stmt->execute()) return $stmt;
//        return false;
    }

    public function readTypes() {

        $query = db()->rows("SELECT * FROM {$this->table_tn}
            WHERE `group_id` = {$this->group_id}");
        if (!empty($query)) return $query;
        else return false;

//        $query = "SELECT * FROM {$this->table_tn}
//            WHERE `group_id` = {$this->id}";
//        $stmt = $this->conn->prepare($query);
//        if($stmt->execute()) {
//            return $stmt;
//        }
//        return false;
    }

    public function readTypesProducts() {
        $query = db()->query_value("SELECT value FROM {$this->table_tp}
            WHERE `type_id` = {$this->type_id} AND `product_id` = {$this->product_id}");
        if (!empty($query)) return $query;
        else return '';
    }

    public function deleteGroup() {
        $types = db()->rows("SELECT * FROM {$this->table_tn} WHERE `group_id` = {$this->id}");
        foreach ($types as $typee) {
            db()->query("DELETE FROM {$this->table_tp} WHERE `type_id` = {$typee['id']}");
        }
        db()->query("DELETE FROM {$this->table_tn} WHERE `group_id` = {$this->id}");
        if(db()->query("DELETE FROM {$this->table_tg} WHERE `id` = {$this->id}"))
            return true;
        return false;
    }

    public function deleteType() {
        db()->query("DELETE FROM {$this->table_tp} WHERE `type_id` = {$this->id}");
        if(db()->query("DELETE FROM {$this->table_tn} WHERE `id` = {$this->id}"))
            return true;
        return false;

    }
}