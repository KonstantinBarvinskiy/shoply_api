<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order.php';
//include_once '../objects/order_item.php';

// создание объекта
$order = new Order($db);
//$order_item = new Order_item($db);

// получаем данные
$data = json_decode(json_encode($_GET), false);

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if (isset($data->id)) {
            $order->int["id"] = $data->id;
            $order->order = $data->id;
            if (db()->query_value("SELECT `complete` FROM `shop_orders` WHERE `id` = '{$data->id}'") == 'N') {
                $order->recountCall();
            }
        }
        if (isset($data->user_id)) $order->int["user_id"] = $data->user_id;
        if (isset($data->admin_id)) $order->int["admin_id"] = $data->admin_id;
        if (isset($data->delivery_type)) $order->int["delivery_type"] = $data->delivery_type;
        if (isset($data->paymethod)) $order->int["paymethod"] = $data->paymethod;
        if (isset($data->status)) $order->int["status"] = $data->status;

        if (isset($data->name)) $order->item["name"] = $data->name;
        if (isset($data->mail)) $order->item["mail"] = $data->mail;
        if (isset($data->phone)) $order->item["phone"] = $data->phone;
        if (isset($data->address)) $order->item["address"] = $data->address;
        if (isset($data->complete)) $order->item["complete"] = $data->complete;
        else $order->item["complete"] = 'Y';
        if (isset($data->comment)) $order->item["comment"] = $data->comment;
        if (isset($data->delivery_cost)) $order->item["delivery_cost"] = $data->delivery_cost;
        if (isset($data->delivery_term)) $order->item["delivery_term"] = $data->delivery_term;
        if (isset($data->point_id)) $order->item["point_id"] = $data->point_id;
        if (isset($data->delivery_city)) $order->order["delivery_city"] = $data->delivery_city;
        if (isset($data->delivery)) $order->item["delivery"] = $data->delivery;
        if (isset($data->delivery_tariff_id)) $order->order["delivery_tariff_id"] = $data->delivery_tariff_id;
        if (isset($data->delivery_mode_id)) $order->order["delivery_mode_id"] = $data->delivery_mode_id;
        if (isset($data->pickpoint_id)) $order->item["pickpoint_id"] = $data->pickpoint_id;
        if (isset($data->dispatch)) $order->item["dispatch"] = $data->dispatch;
        if (isset($data->price)) $order->item["price"] = $data->price;
        if (isset($data->promo_code)) $order->item["promo_code"] = $data->promo_code;
        if (isset($data->paid)) $order->item["paid"] = $data->paid;
        if (isset($data->date)) $order->item["date"] = $data->date;
        if (isset($data->modified)) $order->item["modified"] = $data->modified;
        if (isset($data->deleted)) $order->item["deleted"] = $data->deleted;
        else $order->item["deleted"] = 'N';
        if (isset($data->limit)) $order->limit = $data->limit;

        if($order->read()) {
            // запрашиваем данные
            $stmt = $order->read();
            $num = $stmt->rowCount();

            // проверка, найдено ли больше 0 записей
            if ($num>0) {

                // массив данных
                $items_arr=array();
                $items_arr["records"]=array();

                // получаем содержимое таблицы
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    // извлекаем строку
                    extract($row);

                    $item=array(
                        "id" => $id,
                        "user_id" => $user_id,
                        "admin_id" => $admin_id,
                        "name" => $name,
                        "mail" => $mail,
                        "phone" => $phone,
                        "address" => $address,
                        "complete" => $complete,
                        "comment" => $comment,
                        "delivery_type" => $delivery_type,
                        "delivery_cost" => $delivery_cost,
                        "delivery_term" => $delivery_term,
                        "point_id" => $point_id,
                        "delivery_city" => $delivery_city,
                        "delivery" => $delivery,
                        "delivery_tariff_id" => $delivery_tariff_id,
                        "delivery_mode_id" => $delivery_mode_id,
                        "pickpoint_id" => $pickpoint_id,
                        "dispatch" => $dispatch,
                        "price" => $price,
                        "promo_code" => $promo_code,
                        "discount" => $discount,
                        "paymethod" => $paymethod,
                        "status" => $status,
                        "paid" => $paid,
                        "date" => stupDate($date),
                        "modified" => stupDate($modified),
                        "deleted" => $deleted
                    );

                    array_push($items_arr["records"], $item);
                }

                // устанавливаем код ответа - 200 OK
                http_response_code(200);

                // выводим данные в формате JSON
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                // устанавливаем код ответа - 200 OK
                http_response_code(204);

                // выводим данные в формате JSON
                echo json_encode(array("message" => 'Заказы не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно отобразить заказы"), JSON_UNESCAPED_UNICODE);
        }
    }

        // если декодирование не удалось, это означает, что JWT является недействительным
    catch (Exception $e){

        // код ответа
                http_response_code(401);

        // сообщение об ошибке
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

// показать сообщение об ошибке, если jwt пуст
else {

    // код ответа
        http_response_code(401);

    // сообщить пользователю что доступ запрещен
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>