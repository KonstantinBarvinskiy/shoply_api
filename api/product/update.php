<?php

include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/product.php';

// создание объекта
$product = new Product($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $product->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->external_id)) $product->product["external_id"] = $data->external_id;
        if (isset($data->top)) $product->product["top"] = $data->top;
        if (isset($data->order)) $product->product["order"] = $data->order;
        if (isset($data->name)) $product->product["name"] = $data->name;
        if (isset($data->nav)) $product->product["nav"] = $data->nav;
        if (isset($data->brand)) $product->product["brand"] = $data->brand;
        if (isset($data->country)) $product->product["country"] = $data->country;
        if (isset($data->price)) $product->product["price"] = $data->price;
        if (isset($data->price_old)) $product->product["price_old"] = $data->price_old;
        if (isset($data->show)) $product->product["show"] = $data->show;
        if (isset($data->deleted)) $product->product["deleted"] = $data->deleted;
        if (isset($data->anons)) $product->product["anons"] = $data->anons;
        if (isset($data->remain)) $product->product["remain"] = $data->remain;
        if (isset($data->rate)) $product->product["rate"] = $data->rate;
        if (isset($data->discount)) $product->product["discount"] = $data->discount;
        if (isset($data->hit_sales)) $product->product["hit_sales"] = $data->hit_sales;
        if (isset($data->new_item)) $product->product["new_item"] = $data->new_item;
        if (isset($data->is_order)) $product->product["is_order"] = $data->is_order;
        if (isset($data->weight)) $product->product["weight"] = $data->weight;
        if (isset($data->multiplicity)) $product->product["multiplicity"] = $data->multiplicity;
        if (isset($data->title)) $product->product["title"] = $data->title;
        if (isset($data->keywords)) $product->product["keywords"] = $data->keywords;
        if (isset($data->description)) $product->product["description"] = $data->description;
        if (isset($data->links)) $product->product["links"] = $data->links;
        if (isset($data->multi)) $product->product["multi"] = $data->multi;

        if (empty($product->product)) {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $product->update()) {
            // устанавливаем код ответа - 200 OK
            http_response_code(200);

            // выводим данные в формате JSON
            echo json_encode(array("message" => "Update success"), JSON_UNESCAPED_UNICODE);
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
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