<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/category.php';

// создание объекта
$category = new Category($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

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
            $category->id = $data->id;
        } else {
                        http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->import_ids)) $category->item["import_ids"] = $data->import_ids;
        if (isset($data->top)) $category->item["top"] = $data->top;
        if (isset($data->order)) $category->item["order"] = $data->order;
        if (isset($data->name)) $category->item["name"] = $data->name;
        if (isset($data->name_long)) $category->item["name_long"] = $data->name_long;
        if (isset($data->name_one)) $category->item["name_one"] = $data->name_one;
        if (isset($data->nav)) $category->item["nav"] = $data->nav;
        if (isset($data->show)) $category->item["show"] = $data->show;
        if (isset($data->deleted)) $category->item["deleted"] = $data->deleted;
        if (isset($data->show_menu)) $category->item["show_menu"] = $data->show_menu;
        if (isset($data->show_dd)) $category->item["show_dd"] = $data->show_dd;
        if (isset($data->show_main)) $category->item["show_main"] = $data->show_main;
        if (isset($data->created)) $category->item["created"] = $data->created;
        if (isset($data->modified)) $category->item["modified"] = $data->modified;
        if (isset($data->text)) $category->item["text"] = $data->text;
        if (isset($data->attr)) $category->item["attr"] = serialize(json_decode(json_encode($data->attr), true));
        if (isset($data->cases)) $category->item["cases"] = $data->cases;
        if (isset($data->rate)) $category->item["rate"] = $data->rate;
        if (isset($data->discount)) $category->item["discount"] = $data->discount;
        if (isset($data->discount_number)) $category->item["discount_number"] = $data->discount_number;
        if (isset($data->margin)) $category->item["margin"] = $data->margin;
        if (isset($data->list_type)) $category->item["list_type"] = $data->list_type;
        if (isset($data->grid)) $category->item["grid"] = $data->grid;
        if (isset($data->anons)) $category->item["anons"] = $data->anons;
        if (isset($data->title)) $category->item["title"] = $data->title;
        if (isset($data->keywords)) $category->item["keywords"] = $data->keywords;
        if (isset($data->description)) $category->item["description"] = $data->description;

        if (!isset($category->item)) {
                        http_response_code(400);
            echo json_encode(array("message" => "No data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $category->update()) {
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