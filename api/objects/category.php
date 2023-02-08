<?php

class Category {

    // подключение к БД
    private $conn;
    private $table_name = "products_topics";     // Категории
    private $table_i = "images";                 // Изображения
    private $table_a = "products_attr";          // Привязки атрибутов
    private $table_a_n = "products_attr_names";  // Названия отрибутов
    private $table_tg = "type_groupes";          // Группы хар-к
    private $table_tn = "type_names";            // Хар-ки
    private $table_grid = "products_grid";       // Сетка

    // свойства объекта
    public $id;             // Идентификатор  (Int)
    public $import_ids;     // Артикулы групп для импорта (через запятую) Не работает
    public $top;            // Родительская категория  (Int)
    public $order;          // Порядок  (Int)
    public $name;           // Наименование  (Varchar)
    public $name_long;      // ???????
    public $name_one;       // ???????
    public $nav;            // Опциональная URI ссылка  (Varchar)
    public $show;           // Показывать?  (enum('Y', 'N'))
    public $deleted;        // Удален?   (enum('Y', 'N'))
    public $show_menu;      // Показывать в верхнем меню (enum('Y', 'N')) Не работает
    public $show_dd;        // Показывать внизу выпадающего меню (enum('Y', 'N')) Не работает
    public $show_main;      // Показывать на главной (enum('Y', 'N')) Не работает
    public $created;        // Дата создания  (datetime)
    public $modified;       // Дата последнего изменения  (datetime)
    public $text;           // Тоже описание категории???
    public $attr;           // Сериализованный массив с сохраненными атрибутами и значениями
    public $cases;          // ??????
    public $rate;           // Количество просмотров (Int)
    public $discount;       // Скидка, %  (Int)
    public $discount_number;// Скидка на определенное колличество товаров  (Int)
    public $margin;         // Наценка на товары в группе (%) Не работает
    public $list_type;      // Тип вывода по умолчанию Список/Витрина Enum(list/showcase)
    public $grid;           // Id типа сетки товаров в категории из таблицы products_grid
    public $anons;          // Описание категории
    public $title;          // SEO - Заголовок страницы (title)
    public $keywords;       // SEO - Ключевые слова (meta keywords)
    public $description;    // SEO - Описание (meta description)

    public $img_source;     // Сыллка на главное изображение
    public $img_md5;        // Хеш сумма главного изображения

    public $item;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {

        $hop = $this->item;

        $order = db()->query_value("SELECT `order` 
            FROM `{$this->table_name}` 
            WHERE `top` = {$this->item['top']} 
            ORDER BY `order` DESC");
        $hop['order'] = $order + 1;

        // Вставляем запрос
        $query = "INSERT INTO {$this->table_name} SET ";
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

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // обновить запись пользователя
    public function update(){

        $hop = $this->item;

        if (isset($hop)) {
            // Вставляем запрос
            $query = "UPDATE {$this->table_name} SET ";
            foreach ($hop as $key => $value) {
                $query .= "`{$key}` = :{$key}, ";
            }
            $query = substr($query, 0, -2);
            $query .= " WHERE `id` IN ({$this->id})";

            // подготовка запроса
            $stmt = $this->conn->prepare($query);
            foreach ($hop as $key => $value) {
                if ($key != 'attr') $this->item[$key] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $this->item[$key]);
            }
            $base_work = true;
        }

        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных
        if(isset($base_work)) {
            if ($stmt->execute()) {
                return true;
            }
        }
        if(isset($work)) {
            return true;
        }

        return false;
    }

    // обновить запись пользователя
    public function delete(){

        $query = "UPDATE " . $this->table_name . "
            SET deleted = :del
            WHERE id = :id";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $del = 'Y';
        // уникальный идентификатор записи для редактирования
        $stmt->bindParam(':del', $del);
        $stmt->bindParam(':id', $this->id);

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
                $query .= "`{$key}` = :{$key} AND ";
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

        $query = "SELECT p.*, i.src, i.md5 FROM " . $this->table_name . " p
            LEFT JOIN " . $this->table_i . " i ON i.module = 'Catalog' AND i.module_id = p.id AND main = 'Y'
            WHERE p.id = :id
            ORDER BY `order` ASC";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return $stmt;
        }
        return false;

    }

    public function tree() {

        // выбираем все записи
        $query = "SELECT * FROM {$this->table_name}";
        if (isset($this->item)) {
            $query .= " WHERE ";
            foreach ($this->item as $key => $value) {
                $query .= "`{$key}` = :{$key} AND ";
            }
            $query = substr($query, 0, -5);
        }
        $query .= "ORDER BY `order` ASC";

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

    public function getGrid() {
        $query = "SELECT * FROM {$this->table_grid}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }

    public function getTypeGroups() {
        $query = "SELECT * FROM {$this->table_tg} 
            WHERE `group_id` = {$this->topic_id}
            ORDER BY `order` ASC";

        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }
    public function getTypes() {
        $query = "SELECT * FROM {$this->table_tn}
        WHERE ``";
    }

    /**
     * @param $data
     * @return mixed
     * Строим дерево
     */
    public function createTree($data)
    {
        $parents = [];
        foreach ($data as $key => $item):
            $parents[$item['top']][$item['id']] = $item;
        endforeach;
        $treeElem = $parents[0];
        $this->generateElemTree($treeElem, $parents);
        return $treeElem;
    }

    public function generateElemTree(&$treeElem, $parents)
    {
        foreach ($treeElem as $key => $item):
            if (!isset($item['subtopics'])):
                $treeElem[$key]['subtopics'] = [];
            endif;

            if (array_key_exists($key, $parents)):
                $treeElem[$key]['subtopics'] = $parents[$key];
                $this->generateElemTree($treeElem[$key]['subtopics'], $parents);
            endif;
        endforeach;
    }
}