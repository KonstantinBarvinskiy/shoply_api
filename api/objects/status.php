<?php

class Status {

    // подключение к БД таблице
    private $conn;
    private $table_name = "shop_statuses";       // Статусы

    // свойства объекта
    public $id;     // Идентификатор  (Int)
    public $order;  // Порядок
    public $type;   // Тип
    public $name;   // Наименование
    public $color;  // Цвет

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {

        $hop = $this->item;
        $query = "INSERT INTO " . $this->table_name . " SET `type` = 'Noprofit'";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = :{$key}, ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->item[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->item[$key]);
        }

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить запись
    public function update() {

        $hop = $this->item;

        if (isset($hop)) {
            // Вставляем запрос
            $query = "UPDATE " . $this->table_name . " SET ";
            foreach ($hop as $key => $value) {
                $query .= "`{$key}` = :{$key}, ";
            }
            $query = substr($query, 0, -2);
            $query .= " WHERE id = $this->id";

            // подготовка запроса
            $stmt = $this->conn->prepare($query);
            foreach ($hop as $key => $value) {
                $this->product[$key] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $this->product[$key]);
            }
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

        $noprofit = db()->query_value("SELECT `type` FROM {$this->table_name} WHERE `id` = '{$this->id}'");
        if ($noprofit != 'Noprofit') return false;

        $query = "DELETE FROM {$this->table_name}
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