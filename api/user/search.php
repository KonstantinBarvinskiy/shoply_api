<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/user.php';

// создание объекта
$user = new User($db);

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
        if (isset($data->search)) $user->search = $data->search;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No name"));
            die();
        }

        // создание
        if($user->search()) {
            // запрашиваем данные
            $stmt = $user->search();
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
                        "group" => $group,
                        "type" => $type,
                        "elogin" => $elogin,
                        "name" => $name,
                        "email" => $email,
                        "phone" => $phone,
                        "address" => $address,
                        "company" => $company,
                        "inn" => $inn,
                        "ogrn" => $ogrn,
                        "jaddress" => $jaddress,
                        "bik" => $bik,
                        "rsch" => $rsch,
                        "subscribe" => $subscribe,
                        "lastenter" => stupDate($lastenter),
                        "created" => stupDate($created),
                        "modified" => stupDate($modified)
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
                echo json_encode(array("message" => "Клиенты не найдены"), JSON_UNESCAPED_UNICODE);
            }

        }
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно выполнить данную операцию"), JSON_UNESCAPED_UNICODE);
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
