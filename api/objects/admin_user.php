<?php

class admin_user {

    private $conn;
    private $table_name = "admin_users";

    // свойства объекта
    public $id;         // Идентификатор
    public $login;      // Логин
    public $password;   // Пароль
    public $type;       // Тип (a/m)
    public $access;     // Доступ
    public $name;       // Имя
    public $post;       // Должность
    public $email;      // Почта
    public $lastenter;  // Последняя авторизация
    public $created;    // Дата создания
    public $modified;   // Дата последнего изменения

    public $item;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание нового пользователя
    function create() {

        $password_hash = password_hash(htmlspecialchars(strip_tags($this->item['password'])), PASSWORD_BCRYPT);
        unset($this->item['password']);

        $hop = $this->item;

        // Вставляем запрос
        $query = "INSERT INTO {$this->table_name} SET `password` = '{$password_hash}', ";
        foreach ($hop as $key => $value) {
            $value = htmlspecialchars(strip_tags($value));
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return $query;
    }

    // Проверка, существует ли электронная почта в нашей базе данных
    function emailExists(){

        // запрос, чтобы проверить, существует ли электронная почта
        $query = "SELECT *
            FROM " . $this->table_name . "
            WHERE email = ?
            LIMIT 0,1";

        // подготовка запроса
        $stmt = $this->conn->prepare( $query );

        // инъекция
        $this->email=htmlspecialchars(strip_tags($this->email));

        // привязываем значение e-mail
        $stmt->bindParam(1, $this->email);

        // выполняем запрос
        $stmt->execute();

        // получаем количество строк
        $num = $stmt->rowCount();

        // если электронная почта существует,
        // присвоим значения свойствам объекта для легкого доступа и использования для php сессий
        if($num>0) {

            // получаем значения
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // присвоим значения свойствам объекта
            $this->id = $row['id'];
            $this->login = $row['login'];
            $this->email = $row['email'];
            $this->name = $row['name'];
            $this->post = $row['post'];
            $this->password = $row['password'];

            // вернём 'true', потому что в базе данных существует электронная почта
            return true;
        }

        // вернём 'false', если адрес электронной почты не существует в базе данных
        return false;
    }

    // обновить запись пользователя
    public function update(){

        $hop = $this->item;

        // Вставляем запрос
        $query = "UPDATE {$this->table_name} SET ";
        if (isset($hop['password']) and !empty($hop['password'])) {
            $password_hash = password_hash($hop['password'], PASSWORD_BCRYPT);
            $query .= "`password` = '{$password_hash}', ";
            unset($hop['password']);
        }
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

    public function delete() {
        $query = "DELETE FROM {$this->table_name}
            WHERE `id` = {$this->id}";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function login() {
        $query = "UPDATE {$this->table_name}
            SET `lastenter` = NOW()
            WHERE `id` = {$this->id}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM {$this->table_name}";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }
}