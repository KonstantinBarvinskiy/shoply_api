<?php
include_once '../config/core.php';
new headers_api();
header("Content-Type: multipart/form-data; boundary=something");
use \Firebase\JWT\JWT;

include_once '../objects/image.php';

$image = new Image($db);

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
        if($data->main == "Y") $main = true;
        else $main = false;
        if (isset($data->module) and
            isset($data->module_id)) {
            $module = $data->module;
            $module_id = $data->module_id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if ($data->module == 'Catalog') {
            if (isset($data->alter_key)) {
                if ($data->alter_key == 'topic') $module_id = "root_topic-{$data->module_id}";
                if ($data->alter_key == 'brand') $module_id = "product_brands-{$data->module_id}";
            }
        } else {
            $module_id = strtolower($data->module)."-".$data->module_id;
        }

        $i=0;
        $act = "upload";

        if (!isset($data->alter_key) and $data->module == "Catalog") {
            foreach ($_FILES as $FILE) {
                if (imget()->AddImage($FILE['tmp_name'], $module, $module_id, $FILE['name'], $main)) {
                    $i++;
                }
                $main = false;
            }
        }
        else {
            $old_img = imget()->GetMainImage($module, $module_id);
            if ($old_img and imget()->DelImage($old_img['id']))
                $act = "replace";
            foreach ($_FILES as $FILE) {
                if (imget()->AddImage($FILE['tmp_name'], $module, $module_id, $FILE['name'], true)) {
                    $i++;
                }
                break;
            }
        }

        if($i>0) {
            if ($i>1) $many = 's';
            http_response_code(201);
            echo json_encode(array("message" => "{$i} image{$many} {$act} success"), JSON_UNESCAPED_UNICODE);
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