<?php
include_once '../config/core.php';
new headers_api();
header("Content-Type: multipart/form-data; boundary=something");
use \Firebase\JWT\JWT;

include_once '../libs/MediaFiles.php';
$mfile = new MediaFiles();

//$data = json_decode(file_get_contents("php://input"), true);
$data = json_decode(json_encode($_GET), false);
//$data = array();
//array_push($data, file_get_contents("php://fd/module"));
array_push($_FILES, file_get_contents("php://memory"));

//print_r($data);
//print_r($_FILES);
//die();

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if($data->order == "Y") $order = true;
        else $order = 0;
        if (isset($data->module) and
            isset($data->module_id)) {
            $module = $data->module;
            $module_id = $data->module_id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        $i=0;
        $act = "upload";

        foreach ($_FILES as $FILE) {
            if ($mfile->AddFile($FILE['tmp_name'], $FILE['name'], $module, $module_id)) {
                $i++;
            }
            $main = false;
        }

        if($i>0) {
            if ($i>1) $many = 's';
            http_response_code(201);
            echo json_encode(array("message" => "{$i} file{$many} {$act} success"), JSON_UNESCAPED_UNICODE);
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
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