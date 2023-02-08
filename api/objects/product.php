<?php

class Product {

    // подключение к БД таблице
    private $conn;
    private $table_name = "products";               // Товары
    private $table_t = "products_topics";           // Категории товаров
    private $table_i = "images";                    // Изображения
    private $table_a = "products_attr";             // Привязки атрибутов
    private $table_a_n = "products_attr_names";     // Названия отрибутов
    private $table_b = "products_brands";           // Бренды
    private $table_v = "products_multi";            // Связанные товары
    private $table_l = "links";                     // Варианты товаров
    private $table_values = "products_attr_values";
    private $table_vars = "products_variants";

    // свойства объекта
    public $id;             // Идентификатор  (Int)
    public $external_id;    // Артикул  (Varchar)
    public $top;            // Категория  (Int)
    public $order;          // Порядок  (Int)
    public $name;           // Наименование  (Varchar)
    public $nav;            // Опциональная URI ссылка  (Varchar)
    public $brand;          // Бренд  (Int)
    public $country;        // Страна происхождения  (Varchar)
    public $price;          // Цена  (decimal(11,2))
    public $price_old;      // Старая цена  (decimal(11,2))
    public $show;           // Показывать?  (enum('Y', 'N'))
    public $deleted;        // Удален?  (enum('Y', 'N'))
    public $created;        // Дата создания  (datetime)
    public $modified;       // Дата последнего изменения  (datetime)
    public $anons;          // Анонс товара  (text)
    public $remain;         // Количество на складе  (smallint)
    public $rate;           // Рейтинг (количество просмотров)  (Int)
    public $discount;       // Скидка, %  (Int)
    public $hit_sales;      // Хит продаж  (enum('Y', 'N'))
    public $new_item;       // Новинка  (enum('Y', 'N'))
    public $is_order;       // Под заказ  (enum('Y', 'N'))
    public $weight;         // Вес товара, кг  (decimal(11,3))
    public $multiplicity;   // Кратность отпуска  (decimal(11,3))
    public $title;          // SEO - Заголовок страницы (title)
    public $keywords;       // SEO - Ключевые слова (meta keywords)
    public $description;    // SEO - Описание (meta description)

    public $img_source;     // Сыллка на главное изображение
    public $img_md5;        // Хеш сумма главного изображения

    public $attrs;
    public $variants;
    public $attr_id;

    public $links;          // Варианты товара

    public $product;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // POST

    // Создание
    function create() {

        $hop = $this->product;

        // Вставляем запрос
        $query = "INSERT INTO " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "{$key} = :{$key}, ";
        }
        $query = substr($query, 0, -2);

        // подготовка запроса
        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->product[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->product[$key]);
        }

        // Выполняем запрос
        // Если выполнение успешно, то информация будет сохранена в базе данных
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    function createAttr() {
        $query = "INSERT INTO {$this->table_a_n} SET `name` = '{$this->name}'";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // PATCH

    // обновить запись
    public function update() {

        $hop = $this->product;

        // Варианты товаров
        if (isset($hop['links'])) {
            $links = $hop['links'];
            unset($hop['links']);

            $query = "SELECT * FROM {$this->table_l} WHERE one_id = :id";
            $stmtt = $this->conn->prepare($query);
            $stmtt->execute(array(":id" => $this->id));
            foreach ($stmtt as $row) {
                $old_links = $row['links'];
            }

            if ($links != $old_links) {
                $links = explode(',', $links);
                $links[] = $this->id;
                $old_linkss = explode(',', $old_links);
                $old_linkss[] = "$this->id";

                foreach ($old_linkss as &$link) {
                    $query = "DELETE FROM {$this->table_l}  WHERE one_id = {$link}";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                }

                foreach ($links as &$link) {
                    $links_str = '';
                    foreach ($links as &$linkk) {
                        if ($linkk != $link) $links_str .= "$linkk,";
                    }
                    $links_str = substr($links_str, 0, -1);

                    $query = "INSERT INTO {$this->table_l} SET one_id = '{$link}', links = '{$links_str}'";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                }
                $work = true;
            }
        }

        // Связанные товары
        if (isset($hop['multi'])) {
            $multi = $hop['multi'];
            unset($hop['multi']);
            $multi = explode(',', $multi);

            $query = "DELETE FROM {$this->table_v}  WHERE one_id = {$this->id}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            foreach ($multi as &$link) {
                $query = "INSERT INTO {$this->table_v} SET one_id = {$this->id}, multi_id = {$link}";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            }
            $work = true;
        }

        if (isset($hop)) {
            // Вставляем запрос
            $query = "UPDATE " . $this->table_name . " SET ";
            foreach ($hop as $key => $value) {
                $query .= "{$key} = :{$key}, ";
            }
            $query = substr($query, 0, -2);
            $query .= " WHERE id = :id";

            // подготовка запроса
            $stmt = $this->conn->prepare($query);
            foreach ($hop as $key => $value) {
                $this->product[$key] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $this->product[$key]);
            }
            // уникальный идентификатор записи для редактирования
            $stmt->bindParam(':id', $this->id);
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

    // обновить одно значение у нескольких записей
    public function updateAttr(){

        $hop = $this->product;
        // Вставляем запрос
        $query = "UPDATE " . $this->table_a . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `product_id` = {$this->id} AND `order` = {$this->order} AND `attr_id` = {$this->attr_id}";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function updateAttrs() {
        $i = 0;
        $query = "DELETE FROM {$this->table_a}
            WHERE `product_id` = {$this->id}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if (empty($this->attrs)) return true;

//        $old_attrs = unserialize(db()->query_value("SELECT t.`attr`
//            FROM `{$this->table_t}` t
//            LEFT JOIN `{$this->table_name}` p
//            ON p.`top` = t.`id`
//            WHERE p.`id` = {$this->id}"));
//        $attr_names = db()->query_first("SELECT * FROM {$this->table_a_n}");

        foreach ($this->attrs as $attr) {
            $attr_name = db()->query_first("SELECT * FROM {$this->table_a_n} WHERE `name` = '{$attr['name']}'");
            if(empty($attr_name)) {
                $query = "INSERT INTO {$this->table_a_n}
                    SET `name` = {$attr['name']}";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $attr_name = db()->query_first("SELECT * FROM {$this->table_a_n} WHERE `name` = '{$attr['name']}'");
            }
            $i++;
            $attr['order'] = $i;
            $attr['attr_id'] = $attr_name['id'];
            $attr['product_id'] = $this->id;
            unset($attr['name']);
            $query = "INSERT INTO {$this->table_a} SET ";
            foreach ($attr as $key => $value) {
                $query .= "`{$key}` = '{$value}', ";
            }
            $query = substr($query, 0, -2);
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        return $query;
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

    public function updateVariantsFull() {
        $query = "DELETE FROM {$this->table_vars} WHERE `product_id` = {$this->id}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if (isset($this->attrs['primary_attr'])) {
            $primary_attr_id = db()->query_value("SELECT `id` FROM {$this->table_a_n} WHERE `name` = '{$this->attrs['primary_attr']}'");
            if (empty($primary_attr_id)) {
                $query = "INSERT INTO {$this->table_a_n}
                    SET `name` = '{$attr['name']}'";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $primary_attr_id = db()->query_value("SELECT * FROM {$this->table_a_n} WHERE `name` = '{$this->attrs['primary_attr']}'");
            }
        } else $primary_attr_id = '';
        if (isset($this->attrs['second_attr'])) {
            $second_attr_id = db()->query_value("SELECT `id` FROM {$this->table_a_n} WHERE `name` = '{$this->attrs['second_attr']}'");
            if (empty($second_attr_id)) {
                $query = "INSERT INTO {$this->table_a_n}
                    SET `name` = '{$attr['name']}'";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $second_attr_id = db()->query_value("SELECT * FROM {$this->table_a_n} WHERE `name` = '{$this->attrs['second_attr']}'");
            }
        } else $second_attr_id = '';

        $query = "INSERT INTO {$this->table_vars} (`product_id`,`var_primary_id`,`var_primary_val`,`var_second_id`,`var_second_val`,`external_id`,`price`,`price_old`,`discount`,`remain`,`weight`,`img`) VALUES ";
        foreach ($this->variants as $variant) {
            if (isset($variant['var_primary_value'])) {
                $prim_val_id = db()->query_value("SELECT `id` FROM {$this->table_values} WHERE `value` = '{$variant['var_primary_value']}'");
                if (empty($prim_val_id)) {
                    $quely = "INSERT INTO {$this->table_values} SET 
                        `attr_id` = {$primary_attr_id},
                        `value` = '{$variant['var_primary_value']}',
                        `add_info` = '{$variant['primary_add_info']}'";
                    $stmt = $this->conn->prepare($quely);
                    $stmt->execute();
                    $prim_val_id = db()->query_value("SELECT * FROM {$this->table_values} WHERE `value` = '{$variant['var_primary_value']}'");
                }
            } else $prim_val_id = '';
            if (isset($variant['var_second_value'])) {
                $sec_val_id = db()->query_value("SELECT `id` FROM {$this->table_values} WHERE `value` = '{$variant['var_second_value']}'");
                if (empty($sec_val_id)) {
                    $quely = "INSERT INTO {$this->table_values} SET 
                        `attr_id` = {$second_attr_id},
                        `value` = '{$variant['var_second_value']}',
                        `add_info` = '{$variant['second_add_info']}'";
                    $stmt = $this->conn->prepare($quely);
                    $stmt->execute();
                    $sec_val_id = db()->query_value("SELECT * FROM {$this->table_values} WHERE `value` = '{$variant['var_second_value']}'");
                }
            } else $sec_val_id = '';

            $query .= "('{$this->id}','$primary_attr_id','$prim_val_id','$second_attr_id','$sec_val_id','{$variant['external_id']}','{$variant['price']}','{$variant['price_old']}','{$variant['discount']}','{$variant['remain']}','{$variant['weight']}','{$variant['img']}'),";
        }

        $query = substr($query, 0, -1);
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateVariants() {
        $hop = $this->product;
        $query = "UPDATE {$this->table_vars} SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = '{$value}', ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` IN ({$this->id})";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateAttrOrder() {
        $query = "UPDATE {$this->table_name}
            SET `attr` = '{$this->attrs}'
            WHERE id = ({$this->id})";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // DELETE

    public function delete(){
        $query = "UPDATE {$this->table_name}
            SET `deleted` = 'Y'
            WHERE id IN ({$this->id})";

        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function deleteVariant() {
        $query = "DELETE FROM {$this->table_vars} WHERE `id` IN ({$this->id})";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // GET

    public function read() {

        // выбираем все записи
        $query = "SELECT * FROM " . $this->table_name . "
            WHERE deleted = :del";

        if (isset($this->deleted)) $del = $this->deleted;
        else $del = 'N';
        if (isset($this->top)) {
            $query .= " AND top = :top";
            $top = true;
        }
        if ($del == 'N') $query .= " ORDER BY `order` ASC";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':del', $del);
        if (isset($top) == true) $stmt->bindParam(':top', $this->top);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;

    }

    public function getAttrNames() {
        $query = "SELECT * FROM {$this->table_a_n}
            ORDER BY `id` ASC";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    public function getAttrValues() {
        $query = "SELECT * FROM {$this->table_values} ";
        if (isset($this->attr_id)) $query.= "WHERE `attr_id` = {$this->attr_id} ";
        $query .= "ORDER BY `order` ASC";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    public function getVariants() {
        $query = "SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM {$this->table_vars} vars
            LEFT JOIN {$this->table_values} vals ON vals.id = vars.var_primary_val
            LEFT JOIN {$this->table_values} valss ON valss.id = vars.var_second_val
            LEFT JOIN {$this->table_a_n} an ON vars.var_primary_id = an.id
            LEFT JOIN {$this->table_a_n} ans ON vars.var_second_id = ans.id
            WHERE `product_id` = {$this->id}
            ORDER BY prim_order, sec_order ASC";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }
    
    public function getAttr() {
        $query = "SELECT a.*, n.name FROM " . $this->table_a . " a
            LEFT JOIN " . $this->table_a_n . " n ON a.attr_id = n.id
            WHERE product_id = :id
            ORDER BY `order` ASC";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }

    public function getMulti() {
        $query = "SELECT * FROM " . $this->table_v . "
            WHERE one_id = :id";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;
    }

    public function readOne() {
        $query = "SELECT p.*, l.links FROM " . $this->table_name . " p
            LEFT JOIN " . $this->table_l . " l ON l.one_id = p.id
            WHERE p.id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return $stmt;
        }
        return false;

    }
}