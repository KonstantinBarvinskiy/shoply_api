<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

require_once '../libs/ya-kassa/lib/autoload.php';

use YandexCheckout\Client as YaClient;
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;

include_once '../objects/order.php';
include_once '../objects/order_item.php';
include_once '../objects/user.php';
include_once '../libs/ZFmail.php';

$order = new Order($db);
$order_item = new Order_item($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        // ПОЛУЧЕНИЕ ИНФОРМАЦИИ О ЗАКАЗЕ
        if (isset($data->id)) {
            $order->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if($order->readOne()) {
            $stmt = $order->readOne();
            $num = $stmt->rowCount();
            if ($num>0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "user_id" => $user_id,
                        "admin_id" => $admin_id,
                        "name" => $name,
                        "mail" => $mail,
                        "phone" => $phone,
                        "address" => $address,
                        "complete" => $complete,
                        "comment" => $comment,
                        "delivery_type" => $delivery_type,
                        "delivery_cost" => $delivery_cost,
                        "delivery_term" => $delivery_term,
                        "point_id" => $point_id,
                        "delivery_city" => $delivery_city,
                        "delivery" => $delivery,
                        "pickpoint_id" => $pickpoint_id,
                        "dispatch" => $dispatch,
                        "price" => $price,
                        "promo_code" => $promo_code,
                        "discount" => $discount,
                        "paymethod" => $paymethod,
                        "paymethod_type" => $paymethod_type,
                        "status" => $status,
                        "paid" => $paid,
                        "date" => stupDate($date),
                        "modified" => stupDate($modified),
                        "deleted" => $deleted,
                        "products" => $products
                    );
                    $order_data = $item;
                }
//                http_response_code(200);
//                echo json_encode(unserialize($order_data['products']), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Такого заказа не существует'), JSON_UNESCAPED_UNICODE);
            }
        }
        if($order_data['paymethod_type'] == 'Manual') {
            http_response_code(400);
            echo json_encode(array("message" => 'В данном заказе выбран метод оплаты при получении'), JSON_UNESCAPED_UNICODE);
            die();
        }
        if($order_data['paid'] == 'Y') {
            http_response_code(400);
            echo json_encode(array("message" => 'Этот заказ уже оплачен'), JSON_UNESCAPED_UNICODE);
            die();
        }
        // ПОЛУЧЕНИЕ СПИСКА ПОЗИЦИЙ ПО ЗАКАЗУ
        $order_item->order = $data->id;
        if($order_item->read()) {
            $stmt = $order_item->read();
            $num = $stmt->rowCount();
            if ($num>0) {
                $order_items=array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "product" => $product,
                        "brand" => $brand,
                        "order" => $order,
                        "name" => $name,
                        "attr" => $attr,
                        "link" => $link,
                        "top" => $top,
                        "price" => $price,
                        "count" => $count,
                        "total_fake" => $total_fake,
                        "created" => stupDate($created),
                        "modified" => stupDate($modified),
                        "external_id" => $external_id,
                        "weight" => $weight,
                        "src" => $src
                    );
                    $image = $img->GetMainImage('Catalog', $item['product']);
                    $item['src'] = $image['src'];
                    array_push($order_items, $item);
                }
//                http_response_code(200);
//                echo json_encode($order_items, JSON_UNESCAPED_UNICODE);
//                die();
            } else {
                http_response_code(204);
                echo json_encode(array("message" => "Пустой заказ"), JSON_UNESCAPED_UNICODE);
            }
        }
        // ПОЛУЧЕНИЕ ИНФОРМАЦИИ ПО КЛИЕНТУ

        // ОПЛАТА ЧЕРЕЗ YANDEXKASSA
        if ($order_data['paymethod_type'] == 'YD') {
            $yaid = getSet('Payment', 'shopId');
            $yakey = getSet('Payment', 'ShopPassword');
            $payment_subject = getSet('Payment', 'payment_object_ya');
            $tax_system_code = getSet('Payment', 'sno_ya');
            $vat_code = getSet('Payment', 'vat_ya');

            $yaitems = array(
                "amount" => array(
                    "value" => $order_data['price'],
                    "currency" => "RUB"
                ),
                "confirmation" => array(
                    "type" => "redirect",
                    "return_url" => "https://{$_SERVER["HTTP_HOST"]}/basket/thanks/?status=success"
                ),
                "receipt" => array(
                    "customer" => array(
                        "full_name" => $order_data['name'],
                        "phone" => preg_replace('![^0-9]+!', '', $order_data['phone']),
                        "email" => $order_data['mail']
                    )
                ),
                "metadata" => array(
                    "order_id" => $order_data['id']
                ),
                "capture" => false,
                "description" => "Товары"
            );
            foreach ($order_items as $item) {
                $yaitems['receipt']['items'][] = array(
                    "description" => $item['name'],
                    "quantity" => $item['count'],
                    "amount" => array(
                        "value" => $item['price'],
                        "currency" => "RUB"
                    ),
                    "payment_subject" => $payment_subject,
                    "vat_code" => $vat_code,
                    "payment_mode" => "full_prepayment",
                    "tax_system_code" => $tax_system_code
                );
            }

            $client = new YaClient();
            $client->setAuth($yaid, $yakey);
            $payment = $client->createPayment($yaitems, uniqid('', true));
            $all_array = array(
                'order' => $order_data,
                'mailstr' => '',
//                'totals' => $totals,
//                'products' => $products
            );
//            echo json_encode($all_array, JSON_UNESCAPED_UNICODE);
//            $pr = serialize($all_array);
//            $db->query("UPDATE `shop_orders` SET `transaction_id` = '{$payment['id']}', `products` = '{$pr}' WHERE `id` = '{$order_data['id']}'");
            $mail = new ZFmail($order_data['mail'],
                'noreply@'.$_SERVER['SERVER_NAME'],
                'Ваш заказ #'.$order_data['id'].' на сайте '.$_SERVER['SERVER_NAME'],
                "Здравствуйте!\n\nНа ваше имя в магазине «{$_SERVER['SERVER_NAME']}» выставлен счет №{$order_data['id']} на сумму {$order_data['price']} RUB.\n\nЧтобы оплатить счет, пройдите по ссылке:\n{$payment['confirmation']['confirmation_url']}\n\nСчет необходимо оплатить до 18 октября 2020.");
            if ($mail->send()) {
                http_response_code(201);
                echo json_encode(array("payment_link"=>$payment['confirmation']['confirmation_url']), JSON_UNESCAPED_UNICODE);
            }
            else {
                http_response_code(400);
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
//            header("Location: {$payment['confirmation']['confirmation_url']}");
            die();
        }

        // ОПЛАТА ЧЕРЕЗ MODULBANK
        if ($order_data['paymethod_type'] == 'Modulkassa') {
            $id_modul_shop = getSet('Payment', 'id_modul_shop');
            $sno = getSet('Payment', 'sno');
            $payment_object = getSet('Payment', 'payment_object');
            $vat = getSet('Payment', 'vat');
            $modulkassa_testing = getSet('Payment', 'modulkassa_testing');

            // Товары
            $receipt_items_p = array();
            $receipt_items = array();
            foreach ($order_items as $k => $product) {
                $receipt_items_p['name'] = ''.($product['brand']?db()->query_value("SELECT `name` FROM `products_brands` WHERE `id` = {$product['brand']}").' ':'').$product['name'].($product['attr']?', '.$product['attr']:'').'';
                $receipt_items_p['quantity'] = $product['count'];
                $receipt_items_p['price'] = $product['price'];
                $receipt_items_p['sno'] = $sno;
                $receipt_items_p['payment_object'] = $payment_object;
                $receipt_items_p['payment_method'] = 'full_prepayment';
                $receipt_items_p['vat'] = $vat;
//                if ($_SESSION['promo']) {
//                    $receipt_items_p['discount_sum'] = round(($receipt_items_p['price'] * $receipt_items_p['count'] / 100 * $discount_percent), 2, PHP_ROUND_HALF_UP);
//                    $promo_total += $receipt_items_p['price'] * $receipt_items_p['count'] - $receipt_items_p['discount_sum'];
//                }
                $receipt_items[] = $receipt_items_p;
            }

            $items = array(
                'merchant'=> $id_modul_shop,
                'amount'=> $order_data['price'],
                'description'=> 'Товары',
                'testing' => $modulkassa_testing,
                'client_email' => $order_data['mail'],
                'custom_order_id'=> $order_data['id'],
                'lifetime' => 172800,
                'send_letter' => 1,
                'receipt_contact' => $order_data['mail'],
                'receipt_items'=> json_encode($receipt_items, JSON_UNESCAPED_UNICODE),
                'unix_timestamp'=> time()
            );

            function get_signature(array $params, $key) {
                $key = getSet('Payment', 'shop_secret_key');
                $keys = array_keys($params);
                sort($keys);
                $chunks = array();
                foreach ($keys as $k) {
                    $v = (string) $params[$k];
                    if (($v !== '') && ($k != $key)) {
                        $chunks[] = $k . '=' . base64_encode($v);
                    }
                }
                $data = implode('&', $chunks);
                $sig = double_sha1($data);

                return $sig;
            }
            function double_sha1($data) {
                $key = getSet('Payment', 'shop_secret_key');
                for ($i = 0; $i < 2; $i++) {
                    $data = sha1($key . $data);
                }
                return $data;
            }
            $signature = get_signature($items);

            $postfields = '';
            foreach ($items as $key=>$item) {
                $postfields .= "{$key}={$item}&";
            }
            $postfields .= "signature={$signature}";

            if( $curl = curl_init() ) {
                curl_setopt($curl, CURLOPT_URL, 'https://pay.modulbank.ru/api/v1/bill/');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
                $out = curl_exec($curl);
                curl_close($curl);
                $responce = json_decode($out, true);
//                echo json_encode($responce, JSON_UNESCAPED_UNICODE);
                if (isset($responce['bill']['url'])) {
                    http_response_code(201);
                    echo json_encode(array("payment_link"=>$responce['bill']['url']), JSON_UNESCAPED_UNICODE);
                }
                else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
                }
                die();
            }
        }

        if ($query = $order->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Create success", "id" => $query), JSON_UNESCAPED_UNICODE);
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