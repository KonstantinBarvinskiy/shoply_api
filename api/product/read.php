<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/product.php';

$product = new Product($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->deleted)) $product->deleted = $data->deleted;
        if (isset($data->top)) $product->top = $data->top;

        if($product->read()) {
            $stmt = $product->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $products_arr=array();
                $products_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);

                    $product_item=array(
                        "id" => $id,
                        "external_id" => $external_id,
                        "top" => $top,
                        "order" => $order,
                        "name" => $name,
                        "nav" => $nav,
                        "brand" => $brand,
                        "country" => $country,
                        "price" => $price,
                        "price_old" => $price_old,
                        "show" => $show,
                        "deleted" => $deleted,
                        "created" => stupDate($created),
                        "modified" => stupDate($modified),
                        "anons" => $anons,
                        "remain" => $remain,
                        "rate" => $rate,
                        "discount" => $discount,
                        "hit_sales" => $hit_sales,
                        "new_item" => $new_item,
                        "is_order" => $is_order,
                        "weight" => $weight,
                        "multiplicity" => $multiplicity,
                        "title" => $title,
                        "keywords" => $keywords,
                        "description" => $description,
                        "src" => $src
                    );
                    $main_img = imget()->GetMainImage('Catalog', $product_item['id']);
                    $product_item['src'] = $main_img['src'];
                    $product_item['resize'] = imget()->ResizeImage($product_item['src'], 112, 112);

                    $product->id = $product_item['id'];
                    if($product->getVariants()) {
                        $product->id = $product_item['id'];
                        $stmtattr = $product->getVariants();
                        $numattr = $stmtattr->rowCount();

                        if ($num>0) {
                            $product_item["variants"]=array();
                            while ($rowattr = $stmtattr->fetch(PDO::FETCH_ASSOC)){
                                extract($row);

                                $attr_item=array(
                                    "id" => $rowattr['id'],
                                    "product_id" => $rowattr["product_id"],
                                    "var_primary_id" => $rowattr["var_primary_id"],
                                    "var_primary_name" => $rowattr["var_primary_name"],
                                    "var_primary_val" => $rowattr["var_primary_val"],
                                    "var_primary_value" => $rowattr["var_primary_value"],
                                    "var_second_id" => $rowattr["var_second_id"],
                                    "var_second_name" => $rowattr['var_second_name'],
                                    "var_second_val" => $rowattr["var_second_val"],
                                    "var_second_value" => $rowattr["var_second_value"],
                                    "external_id" => $rowattr["external_id"],
                                    "price" => $rowattr["price"],
                                    "price_old" => $rowattr["price_old"],
                                    "discount" => $rowattr["discount"],
                                    "remain" => $rowattr['remain'],
                                    "weight" => $rowattr["weight"],
                                    "img" => $rowattr["img"]
                                );

                                array_push($product_item["variants"], $attr_item);
                            }
                        }
                    }

                    array_push($products_arr["records"], $product_item);
                }

                http_response_code(200);
                echo json_encode($products_arr, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Товары не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить каталог товаров"), JSON_UNESCAPED_UNICODE);
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