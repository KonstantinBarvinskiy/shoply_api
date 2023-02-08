<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order.php';

// создание объекта
$order = new Order($db);

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
        if (isset($data->id)) $order->item["id"] = $data->id;

        // создание
        if($order->readOne()) {
            // запрашиваем данные
            $stmt = $order->readOne();
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