<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/content.php';

// создание объекта
$content = new Content($db);

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
        if (isset($data->name)) {
            $content->item["name"] = $content->name = $data->name;
        } else {
                        http_response_code(400);
            echo json_encode(array("message" => "No name"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->top)) $content->item["top"] = $data->top;
        if (isset($data->order)) $content->item["order"] = $data->order;
        if (isset($data->nav)) $content->item["nav"] = $data->nav;
        else $content->item['nav'] = makeURI($data->name);
        if (isset($data->name)) $content->item["name"] = $data->name;
        if (isset($data->anons)) $content->item["anons"] = $data->anons;
        if (isset($data->link_text)) $content->item["link_text"] = $data->link_text;
        if (isset($data->text)) $content->item["text"] = $data->text;
        if (isset($data->module)) $content->item["module"] = $data->module;
        if (isset($data->menu)) $content->item["menu"] = $data->menu;
        if (isset($data->template)) $content->item["template"] = $data->template;
        if (isset($data->showmenu)) $content->item["showmenu"] = $data->showmenu;
        if (isset($data->showmain)) $content->item["showmain"] = $data->showmain;
        if (isset($data->promo)) $content->item["promo"] = $data->promo;
        if (isset($data->show)) $content->item["show"] = $data->show;
        if (isset($data->deleted)) $content->item["deleted"] = $data->deleted;
        if (isset($data->title)) $content->item["title"] = $data->title;
        if (isset($data->keywords)) $content->item["keywords"] = $data->keywords;
        if (isset($data->description)) $content->item["description"] = $data->description;

        if ($query = $content->create()) {
            // устанавливаем код ответа - 200 OK
            http_response_code(201);

            // выводим данные в формате JSON
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
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