<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}
require_once ABSDIR . "includes/SourceBansClass.php";

class promotions extends sb {

    public function getActivePromos($activeOnly = true) {
        if ($activeOnly) {
            $where = '`active` = 1';
        } else {
            $where = '1';
        }

        $stmt = $this->ddb->query("SELECT * FROM `promotions` WHERE $where");
        $this->activePromos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->activePromos;
    }

//array(8) { ["id"]=> string(1) "2" ["type"]=> string(1) "1" ["amount"]=> string(1) "9" ["days"]=> string(1) "2"
//["number"]=> string(1) "2" ["code"]=> string(10) "promo-code" ["descript"]=> string(24) "This is a test promotion"
//["active"]=> string(1) "1" }
    public function checkPromo($code) {
        $code = filter_var($code, FILTER_SANITIZE_STRING);
        $stmt = $this->ddb->prepare("SELECT * FROM `promotions` WHERE `code`=?");
        $stmt->bindParam(1, $code);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);
            $promo['redeemed'] = $this->getRedeemedCount($promo['id']);
            $this->promoInfo = $promo;
            return $this->promoInfo;
        } else {
            throw new Exception("That promo is not available");
        }
    }

    public function getRedeemedCount($id) {
        $s = $this->ddb->prepare("SELECT COUNT(*) FROM `promotions_redeemed` WHERE `promo_id` =?;");
        $s->bindParam(1, $id);
        $s->execute();
        $count = $s->fetch(PDO::FETCH_ASSOC);
        return $count["COUNT(*)"];
    }

    public function checkRepeat($steam_id, $promo_id) {
        try {
            $stmt = $this->ddb->prepare("SELECT * FROM `promotions_redeemed` WHERE `steam_id` =? AND `promo_id` =?");
            $stmt->bindParam(1, $steam_id);
            $stmt->bindParam(2, $promo_id);
            $stmt->execute();
            if ($stmt->rowCount() != 0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
    }

}
