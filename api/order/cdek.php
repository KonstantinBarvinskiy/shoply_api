<?php
include_once '../config/core.php';
new headers_api();

$data = json_decode(file_get_contents("php://input"), true);

if($curl = curl_init()) {
    curl_setopt($curl, CURLOPT_URL, $data['url']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (isset($data['body'])) {
//        $postfields = http_build_query($data['body']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data['body'], JSON_UNESCAPED_UNICODE));
    }
    $out = curl_exec($curl);
    curl_close($curl);
    $responce = json_decode($out, true);
    if (!empty($responce)) {
        http_response_code(200);
        echo json_encode($responce, JSON_UNESCAPED_UNICODE);
    }
    else {
        http_response_code(400);
        echo json_encode(array("message"=>"Невозможно выполнить операцию"), JSON_UNESCAPED_UNICODE);
    }
}
else {
    http_response_code(400);
    echo json_encode(array("message"=>"Невозможно выполнить операцию"), JSON_UNESCAPED_UNICODE);
}
?>