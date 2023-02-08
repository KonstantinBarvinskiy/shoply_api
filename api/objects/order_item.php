<?php

class Order_item {

    // подключение к БД таблице
    private $conn;
    private $table_name = "shop_orders_items";  // Товары в заказе
    private $table_o = "shop_orders";           // Заказы
    private $table_i = "images";                // Изображения
    private $table_p = "products";              // Товары
    private $table_a_n = "products_attr_names"; // Названия отрибутов
    private $table_values = "products_attr_values";
    private $table_vars = "products_variants";

    // свойства объекта
    public $id;              // Идентификатор  (Int)
    public $product;         // Id товара  (Int)
    public $brand;           // Id бренда
    public $order;           // Id заказа
    public $name;            // Наименование
    public $attr;            // Атрибуты
    public $link;            // Ссылка на товар
    public $top;             // Категория, в которой находится товар
    public $price;           // Цена на данный айтем
    public $count;           // Колличество
    public $total_fake;      // ??????
    public $created;         // Доставка
    public $modified;        // Номер постомата

    public $item;
    public $items;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {
        // Вставляем запрос
        $query = "INSERT INTO {$this->table_name} (`product`, `brand`, `order`, `name`, `attr`, `top`, `price`, `count`) VALUES ";
        foreach ($this->items as $key => $item) {
            if($item['attr']) {
                $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM {$this->table_vars} vars
                    LEFT JOIN {$this->table_values} vals ON vals.id = vars.var_primary_val
                    LEFT JOIN {$this->table_values} valss ON valss.id = vars.var_second_val
                    LEFT JOIN {$this->table_a_n} an ON vars.var_primary_id = an.id
                    LEFT JOIN {$this->table_a_n} ans ON vars.var_second_id = ans.id
                    WHERE vars.`id` = {$item['attr']}
                    ORDER BY prim_order, sec_order ASC");
                if ($variant['var_primary_id'] and $variant['var_second_id']) {
                    $item['attr'] = "{$variant['var_primary_name']}: {$variant['var_primary_value']}, {$variant['var_second_name']}: {$variant['var_second_value']}";
                } elseif ($variant['var_primary_id']) {
                    $item['attr'] = "{$variant['var_primary_name']}: {$variant['var_primary_value']}";
                } elseif ($variant['var_second_id']) {
                    $item['attr'] = "{$variant['var_second_name']}: {$variant['var_second_value']}";
                }
            }
            $query .= "('{$item['product']}', '{$item['brand']}', '{$this->order}', '{$item['name']}', '{$item['attr']}', '{$item['top']}', '{$item['price']}', '{$item['count']}'), ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            $this->recount($this->order);
            return true;
        }
        return false;
    }

    // обновить запись
    public function update() {
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
                $this->item[$key] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $this->item[$key]);
            }
        }

        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных
        if ($stmt->execute()) {
            $ids = explode(',', $this->id);
            $ord = db()->query_value("SELECT `order` FROM `shop_orders_items` WHERE `id` = {$ids[0]}");
            $this->recount($ord);
            return $ord;
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
            $this->recount($this->order);
            return true;
        }

        return false;
    }

    // обновить запись
    public function delete(){

        $ids = explode(',', $this->id);
        $ord = db()->query_value("SELECT `order` FROM `shop_orders_items` WHERE `id` = {$ids[0]}");

        $query = "DELETE FROM " . $this->table_name . "
            WHERE id IN ({$this->id})";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            $this->recount($ord);
            return $ord;
        }

        return false;
    }

    public function read() {
        $this->recount($this->order);

        // выбираем все записи
        $query = "SELECT oi.*, i.src, i.md5, p.weight, p.external_id 
            FROM {$this->table_name} oi
            LEFT JOIN {$this->table_p} p ON p.id = oi.product
            LEFT JOIN {$this->table_i} i ON i.module = 'Catalog' AND i.module_id = oi.product AND main = 'Y'
            WHERE oi.order = :order";

        // подготовка запроса
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":order", $this->order);

        if($stmt->execute()) {
            return $stmt;
        }

        return false;

    }

    public function recountCall() {
        return $this->recount($this->order);
    }

    private function recount($order) {
        $product_discount = 1;  // Скидка на товар (только %)
        $variant_discount = 1;  // Скидка на вариант товара (только %)

        $topic_discount = 1;    // Скидка на категорию товаров

        $user_discount = 1;     // Скидка для группы клиентов (только %)

        $promo_discount = 1;    // Скидка по промокоду
        $order_discount = 1;    // Скидка на заказ

        $order_price = 0;

        $orderr = db()->query_first("SELECT * FROM `shop_orders` WHERE `id` = {$order}");
        $order_items = db()->rows("SELECT * FROM `shop_orders_items` WHERE `order` = {$order}");

        if (empty($order_items)) {
            db()->query_value("UPDATE `shop_orders` SET `price` = 0 WHERE `id` = {$order}");
            return true;
        }

        if ($orderr['user_id'] != 0) {
            $user_group = db()->query_value("SELECT `group` FROM `users` WHERE `id` = {$orderr['user_id']}");
            if ($user_group != 0) $user_discount = 1 - intval(db()->query_value("SELECT `discount` FROM `user_groups` WHERE `id` = {$user_group}"))/100;
        }

        if ($orderr['promo_code'] or $orderr['discount']) {
            $promo_percent = 0;
            $promo_absolute = 0;
            if ($orderr['promo_code']) {
                $promocode_discount = db()->query_value("SELECT `price` FROM `promocodes` WHERE `code` = '{$orderr['promo_code']}'");
                if(substr($promocode_discount, -1) == '%') {
                    $discount_percent = substr($promocode_discount, 0, -1);
                    $promo_percent += $discount_percent;
                } else $promo_absolute += $promocode_discount;
            }
            if ($orderr['discount']) {
                $promocode_discount = $orderr['discount'];
                if(substr($promocode_discount, -1) == '%') {
                    $discount_percent = substr($promocode_discount, 0, -1);
                    $promo_percent += $discount_percent;
                } else $promo_absolute += $promocode_discount;
            }
        }
        if ($promo_percent) $promo_percent = 1-$promo_percent/100;
        else $promo_percent = 1;

        foreach ($order_items as $k=>$order_item) {
            $product_discount = 1;
            $variant_discount = 1;
            $topic_discount = 1;

            $product = db()->query_first("SELECT * FROM `products` WHERE `id` = {$order_item['product']}");
            $price = $product['price'];
            $product_discount = 1-$product['discount']/100;
            if (!empty($order_item['attr'])) {
                $attrs = explode(', ', $order_item['attr']);
                if (count($attrs) == 2) {
                    $attr1 = explode(': ', $attrs[0]);
                    $attr2 = explode(': ', $attrs[1]);
                    $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM `products_variants` vars
                        LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                        LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                        LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                        LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                        WHERE vals.value = '{$attr1[1]}' AND valss.value = '{$attr2[1]}' AND vars.product_id = {$order_item['product']}");
                }
                else {
                    $attr = explode(': ', $attrs[0]);
                    $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM `products_variants` vars
                        LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                        LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                        LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                        LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                        WHERE (vals.value = '{$attr[1]}' OR valss.value = '{$attr[1]}') AND vars.product_id = {$order_item['product']}");
                }
                if ($variant['price'] != 0) $price = $variant['price'];
                if ($variant['discount']) $variant_discount = 1-$variant['discount']/100;
                $order_items[$k]['variant'] = $variant;
                $order_items[$k]['variant_discount'] = $variant_discount;
                $order_items[$k]['price'] = $price;
            }

            $topic = db()->query_first("SELECT * FROM `products_topics` WHERE `id` = {$product['top']}");
            if ($order_item['count'] >= $topic['discount_number']) $topic_discount = 1-$topic['discount']/100;

            $cart_price = round(($price*$product_discount*$variant_discount*$topic_discount*$user_discount*$promo_percent), 2, PHP_ROUND_HALF_UP);
            $add_price = $cart_price * $order_item['count'];
            $order_items[$k]['cart_price'] = $cart_price;
            $order_price += $add_price;
            $order_items[$k]['variant'] = $variant;
            $order_items[$k]['product_discount'] = $product_discount;
            $order_items[$k]['variant_discount'] = $variant_discount;
            $order_items[$k]['topic_discount'] = $topic_discount;
            $order_items[$k]['user_discount'] = $user_discount;
            $order_items[$k]['promo_percent'] = $promo_percent;
            $order_items[$k]['price'] = $price;

            db()->query("UPDATE `shop_orders_items` SET `price` = '{$order_items[$k]['cart_price']}' WHERE `id` = '{$order_items[$k]['id']}'");
        }

        db()->query("UPDATE `shop_orders` SET `price` = '{$order_price}' WHERE `id` = '{$order}'");

        if ($promo_absolute) {
            if ($promo_absolute >= $order_price) {
                db()->query("UPDATE `shop_orders` SET `price` = '{$order_price}' WHERE `id` = '{$order}'");
                db()->query("UPDATE `shop_orders_items` SET `price` = '0' WHERE `order` = '{$order}'");
            }
            elseif ((count($order_items) == 1) and ($order_items[0]['count'] == 1)) {
                $order_items[0]['cart_price'] = $order_items[0]['cart_price'] - $promo_absolute;
                db()->query("UPDATE `shop_orders_items` SET `price` = '{$order_items[0]['cart_price']}' WHERE `id` = '{$order_items[0]['id']}'");
                $order_price = $order_items[0]['cart_price'];
                db()->query("UPDATE `shop_orders` SET `price` = '{$order_price}' WHERE `id` = '{$order}'");
            }
            else {
                $discount_percent = round((1 - $promo_absolute / $order_price), 20, PHP_ROUND_HALF_UP);
                $order_price = 0;

                foreach ($order_items as $k => $order_item) {
                    $order_items[$k]['cart_price'] = round(($order_item['cart_price'] * $discount_percent), 2, PHP_ROUND_HALF_UP);
                    db()->query("UPDATE `shop_orders_items` SET `price` = '{$order_items[$k]['cart_price']}' WHERE `id` = '{$order_items[$k]['id']}'");
                    $order_price += round(($order_items[$k]['cart_price'] * $order_item['count']), 2, PHP_ROUND_HALF_UP);
                }

                db()->query("UPDATE `shop_orders` SET `price` = '{$order_price}' WHERE `id` = '{$order}'");
            }
        }

//        return $order_items;
        return true;
    }
}