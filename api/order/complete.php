<?php
error_reporting(E_ALL);
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order.php';
include_once '../objects/order_item.php';
include_once '../libs/template_engine.php';
include_once '../libs/ZFmail.php';

// создание объекта
$orderr = new Order($db);
$order_item = new Order_item($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $orderr->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        $order_item->order = $orderr->id;
        if($order_item->read()) {
            $stmt = $order_item->read();
            $num = $stmt->rowCount();
            if ($num=0) {
                http_response_code(400);
                echo json_encode(array("message" => "Empty order"), JSON_UNESCAPED_UNICODE);
                die();
            } else {
                $items_arr=array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $item = array(
                        "id" => $product,
                        "brand" => db()->query_first("SELECT * FROM `products_brands` WHERE `id` = {$brand}"),
                        "order" => $order,
                        "name" => $name,
                        "attr_name" => $attr,
                        "link" => $link,
                        "top" => $top,
                        "topic_name" => db()->query_value("SELECT `name` FROM `products_topics` WHERE `id` = {$top}"),
                        "price" => $price,
                        "inbasket" => $count,
                        "remain" => db()->query_value("SELECT `remain` FROM `products` WHERE `id` = {$product}"),
                        "total_fake" => $total_fake,
                        "external_id" => $external_id,
                        "weight" => $weight,
                        "src" => $src,
                    );
                    $image = $img->GetMainImage('Catalog', $item['product']);
                    $resize = imget()->ResizeImage($image['src'], 400, 700);
                    $item['img'] = $resize;

                    if($item['attr_name']) foreach(explode(',',$item['attr_name']) as $attra) {
                        $attr_parts = explode(':',$attra);
                        $item['attr_selected'][trim($attr_parts[0])] = trim($attr_parts[1]);
                    }

                    array_push($items_arr, $item);
                }
            }
        }

        if ($this_order = $orderr->readOne()) {
            if (isset($this_order['mail'])) {
                if ($this_order['complete'] == 'N') {
                    $orderr->order['complete'] = 'Y';
                    $orderr->order['session_id'] = $orderr->id;
                    $complete = 'N';
                }
            }
        }

        $sitename = db()->query_value("SELECT `value` FROM `settings` WHERE `callname` = 'sitename'");
        $totals = array();
        $totals['summ'] = $this_order['price'];

        if ($query = $orderr->update()) {
            $mailstr = '';
            foreach ($items_arr as $product) {
                $mailstr .= '<tr><td><a href="https://'.$_SERVER['SERVER_NAME'].'/admin/?module=Catalog&method=Info&top='.$product['top'].'#open'.$product['id'].'">'.($product['brand']?$product['brand']['name'].' ':'').$product['topic_name'].' '.$product['name'].($product['attr_name']?', '.$product['attr_name']:'').'</a></td><td>'.$product['inbasket'].'</td><td>'.number_format($product['price'], 0, '', ' ').'</td><td>'.number_format($product['price']*$product['inbasket'], 0, '', ' ')."</td></tr>\r\n";
                $product['attr_selected'];
                if($product['attr_selected'] && $complete == 'N') foreach ($product['attr_selected'] as $k => $s) {
                    $db->query('UPDATE `products_attr` SET remain = remain - '.$product['inbasket'].' WHERE product_id='.$product['id'].' AND value="'.$s.'"');
                }
            }
        }
        //Отправляем сообщение на почту клиенту
        if($this_order['mail']) {
            $mail = new ZFmail($this_order['mail'],
                'noreply@'.$_SERVER['SERVER_NAME'],
                'Ваш заказ #'.$this_order['id'].' на сайте '.$_SERVER['SERVER_NAME'],
                tpl('letter',
                    array('number'=>$this_order['id'],
                        'name'=>$this_order['name'],
                        'sitename'=>$sitename,
                        'order'=>$this_order,
                        'products'=>$items_arr,
                        'totals'=>$totals)), true);
            if ($mail->send()) {
                http_response_code(201);
                echo json_encode(array("message" => "Complete success"), JSON_UNESCAPED_UNICODE);
            }
            else {
                http_response_code(400);
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "В заказе нет электронной почты."), JSON_UNESCAPED_UNICODE);
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