<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/content.php';

// создание объекта
$content = new Content($db);

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
        if (isset($data->id)) $content->id = $data->id;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }

        // создание
        if($content->readOne()) {
            // запрашиваем данные
            $stmt = $content->readOne();
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
                        "top" => $top,
                        "order" => $order,
                        "nav" => $nav,
                        "name" => $name,
                        "anons" => $anons,
                        "link_text" => $link_text,
                        "text" => $text,
                        "module" => $module,
                        "menu" => $menu,
                        "template" => $template,
                        "showmenu" => $showmenu,
                        "showmain" => $showmain,
                        "promo" => $promo,
                        "show" => $show,
                        "deleted" => $deleted,
                        "created" => stupDate($created),
                        "modified" => stupDate($modified),
                        "title" => $title,
                        "keywords" => $keywords,
                        "description" => $description,
                        "src" => $src,
                        "md5" => $md5
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
                echo json_encode(array("message" => 'Контент не найден', $stmt), JSON_UNESCAPED_UNICODE);
            }

        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно отобразить контент"), JSON_UNESCAPED_UNICODE);
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