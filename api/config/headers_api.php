<?php

class headers_api
{
    public function __construct() {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header("Content-Type: application/json; charset=UTF-8");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE");
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            header("Content-Type: application/json; charset=UTF-8");
            exit(0);
        }
        if ($_SERVER['HTTP_USER_AGENT'] == 'PostmanRuntime/7.26.5') {
            header("Access-Control-Allow-Origin: *");
            header("Content-Type: application/json; charset=UTF-8");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE");
            header("Access-Control-Max-Age: 86400");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        }


//        // код ответа
//        header("Access-Control-Allow-Origin: *");
//        header("Content-Type: application/json; charset=UTF-8");
//        header("Access-Control-Allow-Methods: OPTIONS, {$method}");
//        header("Access-Control-Max-Age: 86400");
//        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }
}