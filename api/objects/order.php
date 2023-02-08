<?php

class Order {

    // подключение к БД таблице
    private $conn;
    private $table_name = "shop_orders";             // Заказы
    private $table_pm = "shop_paymethods";           // Способы оплаты
    private $table_d = "shop_delivery";              // Способы доставки
    private $table_p = "shop_delivery_points";       //
    private $table_pp = "shop_delivery_pickpoint";   //
    private $table_s = "shop_statuses";              // Статусы
    private $table_v = "products_multi";             // Связанные товары
    private $table_l = "links";                      // Варианты товаров
    private $table_h_g = "type_groups";              // Группы характеристик
    private $table_h_n = "type_names";               // Характеристики
    private $table_h_p = "type_products";            // Значения характеристик для товаров

    // свойства объекта
    public $id;              // Идентификатор  (Int)
    public $user_id;         // Id пользователя  (Int)
    public $admin_id;         // Id пользователя  (Int)
    public $name;            // Имя
    public $mail;            // Почта  (Varchar)
    public $phone;           // Телефон  (Varchar)
    public $address;         // Адрес  (Int)
    public $complete;        // Завершен (Служебный?)
    public $comment;         // Коммент
    public $delivery_type;   // Тип доставки
    public $delivery_cost;   // Стоимость доставки
    public $point_id;        // Номер ПВЗ
    public $delivery;        // Доставка
    public $pickpoint_id;    // Номер постомата
    public $dispatch;        // Отправление
    public $price;           // Стоимость
    public $promo_code;      // Использованный промокод
    public $discount;        // Примененная скидка
    public $paymethod;       // Метод оплаты
    public $status;          // Статус заказа
    public $paid;            // Оплачен ли заказ  (enum('Y', 'N'))
    public $date;            // Дата заказа
    public $modified;        // Дата последнего изменения
    public $deleted;         // Удален  (enum('Y', 'N'))
    public $admin_user;      // Администратор назначенный на заказ

    public $item;
    public $int;
    public $limit;
    public $order;

    // конструктор класса
    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание
    function create() {
        $hop = $this->order;

        $query = "INSERT INTO {$this->table_name} SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = :{$key}, ";
        }
        $query = substr($query, 0, -2);

        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->order[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->order[$key]);
        }

        if($stmt->execute()) {
            return db()->query_value("SELECT `id` FROM `shop_orders` WHERE `admin_id` = {$this->admin_id} ORDER BY `id` DESC");
        }
        return false;
    }

    // обновить запись
    public function update() {
        $hop = $this->order;

        $query = "UPDATE " . $this->table_name . " SET ";
        foreach ($hop as $key => $value) {
            $query .= "`{$key}` = :{$key}, ";
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id` IN ({$this->id})";

        $stmt = $this->conn->prepare($query);
        foreach ($hop as $key => $value) {
            $this->order[$key] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $this->order[$key]);
        }

        if ($stmt->execute()) {
            $ids = explode(',', $this->id);
            foreach ($ids as &$ord) {
                $this->recount($ord);
            }

            return true;
        }
        return false;
    }

    // обновить одно значение у нескольких записей
    public function updateMulti(){
        $query = "UPDATE " . $this->table_name . "
            SET `{$this->field}` = :value
            WHERE `id` IN ({$this->id})";

        $stmt = $this->conn->prepare($query);
        $this->data=htmlspecialchars(strip_tags($this->value));

        $stmt->bindParam(':value', $this->value);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // обновить запись
    public function delete(){
        $query = "UPDATE `" . $this->table_name . "`
            SET `deleted` = 'Y'
            WHERE `id` IN ({$this->id})";

        $stmt = $this->conn->prepare($query);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * 
            FROM " . $this->table_name;
        $query .= " WHERE `complete` = 'Y' AND ";
        if (isset($this->item)) {
            foreach ($this->item as $key => $value) {
                $value = htmlspecialchars(strip_tags($value));
                $query .= "`{$key}` = :{$key} AND ";
            }
        }
        if (isset($this->int)) {
            foreach ($this->int as $key => $value) {
                $value = htmlspecialchars(strip_tags($value));
                $query .= "`{$key}` IN ({$value}) AND ";
            }
        }
        $query = substr($query, 0, -5);
        $query .= " ORDER BY `date` DESC";
        if (isset($this->limit)) $query .= " LIMIT {$this->limit}";

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
//        $query = db()->query_first("SELECT * FROM `{$this->table_name}` WHERE `id` = {$this->id}");
//        if(!empty($query)) {
//            return $query;
//        }
//        return false;

        $query = "SELECT o.*, p.type as paymethod_type FROM `{$this->table_name}` o
            LEFT JOIN `{$this->table_pm}` p ON p.id = o.paymethod
            WHERE o.`id` = {$this->id}";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    public function userStory() {
        $stats = db()->rows("SELECT o.`price`, o.`status`, s.`type` 
            FROM `{$this->table_name}` o
            LEFT JOIN `{$this->table_s}` s ON o.`status` = s.`id`
            WHERE o.`user_id` = {$this->user_id} 
            AND s.`type` <> 'Cancelled' AND o.`status` <> 0");

        if (empty($stats)) return false;

        $orders = 0;
        $price = 0;
        $return = 0;
        foreach ($stats as $stat) {
            $orders++;
            if ($stat['type'] == 'Returned') $return += $stat['price'];
            $price += $stat['price'];
        }
        $buyout = round(($return / $price * 100),0);
        if ($return == 0) $buyout = 100;
        $stata = array(
            'orders' => $orders,
            'price' => $price,
            'return' => $return,
            'buyout' => $buyout
        );
        return $stata;
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