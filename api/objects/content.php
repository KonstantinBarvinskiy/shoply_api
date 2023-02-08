<?php

class Content {

    // подключение к БД таблице
    private $conn;
    private $table_name = "content";// Контент
    private $table_i = "images";// Контент

    // свойства объекта
    public $id;         // Идентификатор  (Int)
    public $top;        // Родительский раздел, если нет то 0
    public $order;      // Порядок
    public $nav;        // Имя для вызова
    public $name;       // Наименование
    public $anons;      // Анонс
    public $link_text;  // Описание ссылки
    public $text;       // Содержание
    public $module;     // Модуль
    public $menu;       // Меню
    public $template;   // Шаблон
    public $showmenu;   // Показывать в меню (Y/N)
    public $showmain;   // Показывать на главной (Y/N)
    public $promo;      // Промо (Y/N)
    public $show;       // Показывать (Y/N)
    public $deleted;    // Удален (Y/N)
    public $created;    // Дата создания
    public $modified;   // Дата последнего изменения
    public $title;      // SEO - Заголовок страницы (title)
    public $keywords;   // SEO - Ключевые слова (meta keywords)
    public $description;// SEO - Описание (meta description)

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
            if ($key != 'text' or $key != 'anons') $value = htmlspecialchars(strip_tags($value));
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
            if ($key != 'text' or $key != 'anons') $value = htmlspecialchars(strip_tags($value));
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

    public function readOne() {

        // выбираем все записи
        $query = "SELECT c.*, i.src, i.md5 
            FROM {$this->table_name} c
            LEFT JOIN {$this->table_i} i ON 
                i.module = 'Content' 
                AND i.module_id = 0
                AND i.alter_key = '{$this->table_name}-{$this->id}'
            WHERE c.id = {$this->id}";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return $query;
    }
}