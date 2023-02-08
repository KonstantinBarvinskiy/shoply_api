<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../libs/MediaFiles.php';
$mfile = new MediaFiles();

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->module)) $module = $data->module;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'module'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->module_id)) $module_id = $data->module_id;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'module_id'"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($files = $mfile->GetFiles($module, $module_id)) {
            foreach ($files as $type=>$types) {
                foreach ($types as $unit=>$units) {
                    $files[$type][$unit]['date'] = stupDate($files[$type][$unit]['date']);
                    for( $i = 0; $i < 11; $i++) {
                        unset($files[$type][$unit][$i]);
                    }
                }
            }

            http_response_code(200);
            echo json_encode($files, JSON_UNESCAPED_UNICODE);
        }
        else {
            http_response_code(204);
            echo json_encode(array("message" => 'Файлы не найдены'), JSON_UNESCAPED_UNICODE);
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





//            foreach ($imgs as $key=>$img) {
////                $imgs[$key]['resize'] = mediafile($img['src'], 666, 666);
//                $src = $img['src'];
//                $width = 666;
//                $height = 666;
//
//                $src = str_replace($GLOBALS['config']['cs']['path'], '', $src);
//                if($pt = strrpos($src, '.')) $ext = strtolower(substr($src, $pt+1));
//                $filename = $ext ? substr($src, 0, $pt) : $src;
//                $new_file_src = 'thumbs/'.$filename.'_'.$width.'x'.$height.'.'.($ext?$ext:$format);
//
//                if(db()->query_value('SELECT id FROM `thumbs` WHERE src="'.q($src).'" AND width='.intval($width).' AND height='.intval($height))) $imgs[$key]['resize'] = $GLOBALS['config']['cs']['path'].$new_file_src;
//                else {
//                    if(!isset($GLOBALS['container'])) {
//                        $GLOBALS['selectelStorage'] = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
//                        if($GLOBALS['config']['cs']['container']) $GLOBALS['container'] = $GLOBALS['selectelStorage']->getContainer($GLOBALS['config']['cs']['container']);
//                    }
//                    if($GLOBALS['container']->getFileInfo($src)) {
////                        $file = $GLOBALS['container']->getFile($src);
//                        $file = file_get_contents($img['src']);
//                        $temp_file = md5($file.$width.$height);
//                        $file_name = __DIR__.'img.jpg';
//                        file_put_contents($file_name, $file);
//                        system('convert '.$file_name.' -resize '.$width.'x'.$height.'\> '.$file_name);
//                        $GLOBALS['container']->putFile($file_name, $new_file_src);
//                        db()->query('INSERT INTO `thumbs` SET src="'.q($src).'", width='.intval($width).', height='.intval($height).', md5="'.md5($temp_file).'"');
//                        unlink($file_name);
//                    }
//                    $imgs[$key]['resize'] = $GLOBALS['config']['cs']['path'].$new_file_src;
//                }
//            }
?>
