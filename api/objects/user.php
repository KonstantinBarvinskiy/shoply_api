<?php

class User {

    // подключение к БД таблице
    private $conn;
    private $table_name = "users";           // Клиенты
    private $table_ug = "user_groups";       // Группы клиентов

    // свойства объекта
    public $id;        // Идентификатор  (Int)
    public $group;     // Id группы клиентов  (Int)
    public $type;      // Юридическое лицо? (enum('Y', 'N'))
    public $elogin;    // Почта
    public $name;      // Имя
    public $email;     // Email
    public $phone;     // Телефон
    public $address;   // Основной адрес
    public $company;   // Организация   (Это вообще используется?)
    public $inn;       // ИНН   (Это вообще используется?)
    public $ogrn;      // Доставка   (Это вообще используется?)
    public $jaddress;  // Номер постомата   (Это вообще используется?)
    public $bik;       // Отправление   (Это вообще используется?)
    public $rsch;      // Стоимость   (Это вообще используется?)
    public $subscribe; // Подписан на рассылку? (enum('Y', 'N'))
    public $lastenter; // Последняя авторизация
    public $created;   // Дата создания/регистрации
    public $modified;  // Дата последнего изменения записи

    public $item;
    public $search;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {

        $hop = $this->item;

        // Вставляем запрос
        $query = "INSERT INTO " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = :{$key}, ";
        }
        $query = substr($query, 0, -2);

        // подготовка запроса
        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->item[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->item[$key]);
        }

        // Выполняем запрос
        // Если выполнение успешно, то информация будет сохранена в базе данных
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить запись
    public function update() {

        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = :{$key}, ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` IN ({$this->id})";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->item[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->item[$key]);
        }

        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить одно значение у нескольких записей
    public function updateMulti(){

        // Вставляем запрос
        $query = "UPDATE " . $this->table_name . "
            SET `{$this->field}` = :value
            WHERE id IN ({$this->id})";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        // инъекция (очистка)
        $this->data=htmlspecialchars(strip_tags($this->value));

        // привязываем значения с HTML формы
        $stmt->bindParam(':value', $this->value);

        // Если выполнение успешно, то информация будет сохранена в базе данных
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить запись
    public function delete(){

        $query = "DELETE FROM " . $this->table_name . "
            WHERE `id` IN ({$this->id})";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {

        // выбираем все записи
        $query = "SELECT * 
            FROM " . $this->table_name;

        if (isset($this->item)) {
            $query .= " WHERE ";
            foreach ($this->item as $key => $value) {
                $query .= "{$key} = :{$key} AND ";
            }
            $query = substr($query, 0, -5);
        }

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if (isset($this->item)) {
            foreach ($this->item as $key => $value) {
                $this->item[$key] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $this->item[$key]);
            }
        }

        if($stmt->execute()) {
            return $stmt;
        }

        return false;

    }

    public function readOne() {
        $query = "SELECT p.*, i.src, i.md5, l.links FROM " . $this->table_name . " p
            LEFT JOIN " . $this->table_i . " i ON i.module = 'Catalog' AND i.module_id = p.id AND main = 'Y'
            LEFT JOIN " . $this->table_l . " l ON l.one_id = p.id
            WHERE p.id = :id";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    public function search() {
        $query = "SELECT * FROM `{$this->table_name}` WHERE `name` LIKE '%{$this->search}%' OR `elogin` LIKE '%{$this->search}%' OR `phone` LIKE '%{$this->search}%'";

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }


}