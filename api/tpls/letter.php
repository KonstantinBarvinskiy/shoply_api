<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title></title>
    <meta charset="utf-8">
</head>
<body style="margin:0; padding:0;font-family:arial;background:#f3f3f3;color:#333;font-size:14px;">
</table>
<table align="center" border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;max-width:600px;width:100%;">
    <tr>
        <td style="padding-left:16px;border-bottom:solid 1px #ececec;">
            <a href="" style="color:#ce2027;font-size:8px;font-weight:bold;text-decoration:none;display:block;;padding:10px 0;text-align:left;">
                <span style="font-size:20px;font-weight:bold;color:#333;line-height:20px;padding:0 0 2px;display:block;"><?=$sitename?></span>
            </a>
        </td>
        <td style="padding-right:16px;text-align:right;vertical-align:middle;border-bottom:solid 1px #ececec;">
            <a href="" style="color:#ce2028;text-decoration:none;"></a>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding:20px 16px;border-bottom:solid 1px #ececec;">
            <p style="font-size:18px;padding:0 0 20px;margin:0;">Спасибо за ваш заказ!</p>
            <p style="padding:0 0 15px;margin:0;">Здравствуйте, <b><?=$name?></b></p>
            <p style="margin:0;">Ваш заказ №<?=$number?> в интернет-магазине «<?=$sitename?>» принят:</p>
        </td>
    </tr>
    <?php $count = 0; foreach ($products as $p) { $count++;?>
    <tr>
        <td colspan="2" style="border-bottom:solid 1px #ececec;">
            <table style="width:100%;">
                <tr>
                    <td style="padding:11px 10px 14px 16px;">
                        <p style="margin:0;padding:0 0 5px;line-height:18px;font-size:12px;">
                            <a href="<?=$_SERVER['HTTP_HOST']?><?=$p['link']?>" style="color:#333;">
                                <?php if ($p['brand']) {?><?=$p['brand']['name']?>, <?php }?><?=$p['name']?><?php if ($p['attr_name']) {?>, <?=$p['attr_name']?><?php }?>
                            </a>
                        </p>
                        <span style="font-size:12px;color:#646565">Цена: <?=$p['price']?> руб.</span>
                    </td>
                    <td style="padding:11px 30px 14px 0;text-align:right;font-size:12px;white-space:nowrap;vertical-align:top;">
                        <?=$p['inbasket']?> шт.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <?php }?>
    <tr>
        <td colspan="2" style="padding:12px 16px 16px;">
            <p style="margin:0;">Всего <?=$count?> товара на сумму <?=$totals['summ']?> руб.</p>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="">
            <table style="width:100%;">
                <tr>
                    <td style="padding:16px 13px 16px 16px;vertical-align:top;width:60px;">
                        <img src="https://yanka.shop/themes/default/images/delivery-mail.png">
                    </td>
                    <td style="padding:16px 20px 16px 0;">
                        <p style="font-size:12px;margin:0;padding:0 0 5px;max-width:200px;"><?=$order['delivery']?></p>
                        <?php if ($order['delivery']) {?>
                        <p style="margin:0;font-size:12px;padding:0 0 12px;">Адрес доставки: <?=$order['address_m']?></p>
                        <?php }?>
                        <?php if ($order['delivery_cost']) {?>
                        <p style="margin:0;padding:0 0 30px;">Стоимость доставки: <?=$order['delivery_cost']?> </p>
                        <?php }?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding:0 16px 50px;">

            <p style="margin:0;padding:0 0 7px;">Итого к оплате при получении:</p>
            <p style="margin:0;font-size:18px;font-weight: bold"><?=$totals['summ']+$order['delivery_cost']?> руб.</p>
        </td>
    </tr>
</table>

</body>
</html>