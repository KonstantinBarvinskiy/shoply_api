<?php

class Expected_item {

    // подключение к БД таблице
    private $conn;
    private $table_name = "products_order";// Адрес

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $product_id; // Идентификатор товара
    public $email;      // Почта
    public $attr;       // Атрибуты
    public $date;       // Улица

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {
        $hop = $this->item;

        $add_attr = '';
        if ($hop['attr']) {
            $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM products_variants vars
                    LEFT JOIN products_attr_values vals ON vals.id = vars.var_primary_val
                    LEFT JOIN products_attr_values valss ON valss.id = vars.var_second_val
                    LEFT JOIN products_attr_names an ON vars.var_primary_id = an.id
                    LEFT JOIN products_attr_names ans ON vars.var_second_id = ans.id
                    WHERE vars.`id` = {$hop['attr']}
                    ORDER BY prim_order, sec_order ASC");
            if ($variant['var_primary_id'] and $variant['var_second_id']) {
                $hop['attr'] = "{$variant['var_primary_name']}: {$variant['var_primary_value']}, {$variant['var_second_name']}: {$variant['var_second_value']}";
            } elseif ($variant['var_primary_id']) {
                $hop['attr'] = "{$variant['var_primary_name']}: {$variant['var_primary_value']}";
            } elseif ($variant['var_second_id']) {
                $hop['attr'] = "{$variant['var_second_name']}: {$variant['var_second_value']}";
            }
            $add_attr = " AND `attr` = '{$hop['attr']}'";
        }

        if (db()->query_first("SELECT * FROM `products_order` WHERE `product_id` = '{$hop}' AND `email` = '{$hop['email']}'{$add_attr}")) return 'Already exist';

        $query = "INSERT INTO {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
            $query .= "{$key} = '{$value}', ";
        }
        $query .= "date = CURDATE()";

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
}