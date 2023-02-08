<?php

include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/type.php';

// создание объекта
$type = new Type($db);

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
        if (isset($data->topic_id)) $type->topic_id = $data->topic_id;
        if (isset($data->product_id)) $type->product_id = $data->product_id;

        // создание
        if($groups = $type->readGroups()) {

            // массив данных
            $types_arr=array();
            $types_arr["groups"]=array();

            // получаем содержимое таблицы
            foreach ($groups as $group){

                $type_item=array(
                    "id" => $group['id'],
                    "topic_id" => $group['topic_id'],
                    "order" => $group['order'],
                    "name" => $group['name'],
                    "types" => array()
                );

                $type->group_id = $type_item['id'];
                if($types = $type->readTypes()) {
//                        $type->id = $type_item['id'];
//                        $stmt_name = $type->readTypes();
                    foreach ($types as $row_name){

                        $name_item=array(
                            "id" => $row_name['id'],
                            "group_id" => $row_name["group_id"],
                            "order" => $row_name["order"],
                            "name" => $row_name["name"],
                            "type" => $row_name["type"],
                            "unit" => $row_name["unit"],
                            "select" => $row_name["select"],
                            "main" => $row_name["main"],
                            "descr" => $row_name["descr"]
                        );

                        if (isset($data->product_id)) {
                            $type->product_id = $data->product_id;
                            $type->type_id = $name_item['id'];
                            $name_item['value'] = $type->readTypesProducts();
                        }

                        array_push($type_item["types"], $name_item);
                    }
                }

                array_push($types_arr["groups"], $type_item);
            }

            // устанавливаем код ответа - 200 OK
            http_response_code(200);

            // выводим данные в формате JSON
            echo json_encode($types_arr, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(204);
            echo json_encode(array('message' =>'Характеристики не найдены'), JSON_UNESCAPED_UNICODE);
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