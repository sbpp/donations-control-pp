<?php

//NEED TO ADD CHECKING TO SEE IF ALL PROMOS ARE REDEEMED!!!!


if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}
define('NineteenEleven', TRUE);
require_once'../includes/config.php';
require_once ABSDIR . 'includes/PromotionsClass.php';
try {
    $promos = new promotions;
} catch (Exception $ex) {
    print "Oops something went wrong, please try again later.";
}
$code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
$steamid_user = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
$amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT);
$expire = filter_input(INPUT_POST, 'expire', FILTER_SANITIZE_NUMBER_INT);
try {
    $promo = $promos->checkPromo($code);
} catch (Exception $ex) {
    printf("<div class='noPromo'><span class='glyphicon glyphicon-remove'></span>%s</div>", $ex->getMessage());
    die();
}
if ($promo) {

    $repeatPromo = $promos->checkRepeat($steamid_user, $promo['id']);
    if ($repeatPromo) {
        die("<div class='noPromo'><span class='glyphicon glyphicon-remove'></span>That code has already been redeemed for this account.</div>");
    }

    $goodMsg = sprintf("<div class='foundPromo'><span class='glyphicon glyphicon-ok'></span> found promo '%s'<br />", $promo['descript']);
    $lockBox = "<script>var ppCustom = $('#ppCustom').val();"
            . "$('#ppCustom').val(ppCustom + '|$code');"
            . "$('#promoCode').attr('readonly', true);</script>";



    if (($promo['timestamp'] + ($promo['days'] * 86400)) >= date('U') || $promo['days'] == 0) {
        $days_check = true; //promo vaild
    } else {
        $days_check = false;
    }

    if ($promo['redeemed'] < $promo['number'] || $promo['number'] == 0) {
        $num_check = true;
    } else {
        $num_check = false;
    }

    if (!$days_check || !$num_check) {
        if ($days_check) {
            die("<div class='noPromo'><span class='glyphicon glyphicon-remove'></span>Sorry that promotion has expired.</div>");
        }
        if ($num_check) {
            die("<div class='noPromo'><span class='glyphicon glyphicon-remove'></span>Sorry this promotion has expired.</div>");
        }
    }

    if ($promo['type'] == '1') {
//precent off
        $amount = round($amount / (100 / (100 - $promo['amount'])), 2);
        echo "<script>$('#ppAmount').val('$amount');</script>";
        echo "$goodMsg Your new payment will be $amount</div>$lockBox";
    } elseif ($promo['type'] == '2') {
        $extraDays = $promo['amount'] * 86400;
        $expire = $expire + $extraDays;
        printf("$goodMsg Your perks will expire on %s</div>$lockBox", date($date_format['front_end'], $expire));
    }
} else {
    echo "<div class='noPromo'><span class='glyphicon glyphicon-remove'></span>Not a vaild code.</div>";
}

