<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/feedback.php';

$feedback = new Feedback($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->pid)) $feedback->item["pid"] = $data->pid;
        if (isset($data->author)) $feedback->item["author"] = $data->author;
        if (isset($data->rating)) $feedback->item["rating"] = $data->rating;
        if($feedback->read()) {
            $stmt = $feedback->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "pid" => $pid,
                        "date" => stupDate($date),
                        "rating" => $rating,
                        "author" => $author,
                        "text" => $text
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Отзывы не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить отзывы"), JSON_UNESCAPED_UNICODE);
        }
    }

    catch (Exception $e){
        http_response_code(401);
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

else {
    http_response_code(401);
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>