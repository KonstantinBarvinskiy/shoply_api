<?php

include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/product.php';

// создание объекта
$product = new Product($db);

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

        $product->id = $data->id;

        // создание
        if($product->readOne()) {
            // запрашиваем данные
            $stmt = $product->readOne();
            $num = $stmt->rowCount();

            // проверка, найдено ли больше 0 записей
            if ($num>0) {

                // массив данных
                $products_arr=array();
                $products_arr["records"]=array();

                // получаем содержимое таблицы
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    // извлекаем строку
                    extract($row);

                    $product_item=array(
                        "id" => $id,
                        "external_id" => $external_id,
                        "top" => $top,
                        "order" => $order,
                        "name" => $name,
                        "nav" => $nav,
                        "brand" => $brand,
                        "country" => $country,
                        "price" => $price,
                        "price_old" => $price_old,
                        "show" => $show,
                        "created" => stupDate($created),
                        "modified" => stupDate($modified),
                        "anons" => $anons,
                        "remain" => $remain,
                        "rate" => $rate,
                        "discount" => $discount,
                        "hit_sales" => $hit_sales,
                        "new_item" => $new_item,
                        "is_order" => $is_order,
                        "weight" => $weight,
                        "multiplicity" => $multiplicity,
                        "title" => $title,
                        "keywords" => $keywords,
                        "description" => $description,
                        "links" => $links
                    );

                    $product->id = $product_item['id'];
                    if($product->getAttr()) {
                        $stmtattr = $product->getAttr();
                        $numattr = $stmtattr->rowCount();

                        if ($numattr>0) {

                            // массив данных
                            $product_item["attrs"]=array();

                            // получаем содержимое таблицы
                            while ($rowattr = $stmtattr->fetch(PDO::FETCH_ASSOC)){
                                // извлекаем строку
                                extract($rowattr);

                                $attr_item=array(
                                    "product_id" => $rowattr['product_id'],
                                    "attr_id" => $rowattr["attr_id"],
                                    "order" => $rowattr["order"],
                                    "type" => $rowattr["type"],
                                    "name" => $rowattr["name"],
                                    "value" => $rowattr["value"],
                                    "remain" => $rowattr["remain"]
                                );

                                array_push($product_item["attrs"], $attr_item);
                            }
                        }
                    }

                    if($product->getMulti()) {
                        $stmtmulti = $product->getMulti();
                        $nummulti = $stmtmulti->rowCount();

                        if ($nummulti>0) {

                            // массив данных
                            $product_item["multi"]=array();

                            // получаем содержимое таблицы
                            while ($rowmulti = $stmtmulti->fetch(PDO::FETCH_ASSOC)){
                                // извлекаем строку
                                extract($rowmulti);
                                array_push($product_item["multi"], $rowmulti['multi_id']);
                            }
                        }
                    }

                    array_push($products_arr["records"], $product_item);
                }

                // устанавливаем код ответа - 200 OK
                http_response_code(200);

                // выводим данные в формате JSON
                echo json_encode($products_arr, JSON_UNESCAPED_UNICODE);
            }else {
                // устанавливаем код ответа - 200 OK
                http_response_code(204);

                // выводим данные в формате JSON
                echo json_encode(array("message" => "Товар не найден"), JSON_UNESCAPED_UNICODE);
            }

        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
                        http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно отобразить каталог товаров"), JSON_UNESCAPED_UNICODE);
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