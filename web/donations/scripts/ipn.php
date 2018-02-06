<?php

define('NineteenEleven', TRUE);
require_once'../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/SteamClass.php';
require_once ABSDIR . 'scripts/rcon_code.php';
require_once ABSDIR . 'includes/PromotionsClass.php';
$sysLog = new log;
$log = fopen(ABSDIR . 'admin/logs/IPN-' . date('d-m-Y_G-i-s') . '.log', "a");

$req = 'cmd=_notify-validate';

$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
if (PP_SANDBOX) {
    $fp = @fsockopen('ssl://sandbox.paypal.com', 443, $errno, $errstr, 30);
} else {
    $fp = @fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
}
if (!$fp) {
    fclose($log);
    die($sysLog->logError('Error contacting PayPal. Probaby some l33t h4x3r.'));
} else {
//if (true) { //used for debuggin
    //http://192.168.1.22/dc/scripts/ipn.php?mc_gross=3.75&payer_email=da@fad.com&txn_id=232323&custom=STEAM_0:1:37569671|3|abcde
    fputs($fp, $header . $req);

    fwrite($log, date('m/j/Y g:i:s A') . ": PayPal IPN recieved \r\n");

    $userInfo = explode("|", $_POST['custom']);
    $steamid_user = $userInfo[0];
    if (isset($userInfo[2])) {//promo-code
        $promoCode = $userInfo[2];
    } else {
        $promoCode = null;
    }
    $amount = $_POST['mc_gross'];
    $sign_up_date = date('U');

    $tier = $userInfo[1];
    $email = $_POST['payer_email'];
    $txn_id = $_POST['txn_id'];

    try {
        $sb = new sb;
        $promos = new promotions;
    } catch (Exception $ex) {
        //we need the database here so dump the log and leave.
        fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
        fwrite($log, "Steam ID:$steamid_user\r\n amount:$amount\r\n" .
                "Tier:$tier\r\n Promo Code:$promoCode \r\n Email:$email\r\n Transaction ID:$txn_id\r\n");
        die();
    }
    if (!DEBUG) {
        $stmt = $sb->ddb->prepare("SELECT steam_id FROM `donors` WHERE `txn_id` = ?");
        $stmt->bindParam(1, $txn_id);
        $stmt->execute();
        if ($stmt->rowCount() != 0) {
            fwrite($log, "Duplicate transaction ID. Quitting.\r\n");
            die();
        }
    }
    try {
        $steam_id = new SteamIDConvert($steamid_user);
        $steam_id->SteamIDCheck();
        $steamID64 = $steam_id->steamId64;
        $steam_link = $steam_id->steam_link;
        $steamid_user = $steam_id->steam_id;
        $username = $steam_id->playerSummaries->response->players[0]->personaname;
    } catch (Exception $ex) {
        fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");

        $username = 'Unknown';
        $steamID64 = 'Unknown';
        $steam_link = 'Unknown';
        if (FORCE_IPN) {
            $sysLog->logError('New donation taken but unable to verify Steam ID, Forcing Steam ID.');
            $steamid_user = $userInfo[0];
            $errors = true;
        } else {
            $sysLog->logError('New donation taken but unable to verify Steam ID, aborting.');
            $steamid_user = $userInfo[0] . '(UNVERIFIED)';
            $killScript = true;
        }
    }

    try {
        $group = $sb->getGroupInfo($tier);
        $srv_group = $group['name'];
        $group_id = $group['group_id'];
        $srv_group_id = $group['srv_group_id'];
        $server_id = $group['server_id'];
        $tag = $group['name'];
    } catch (Exception $ex) {
        fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
        $sysLog->logError('New donation taken but unable to find group, aborting.');
        die();
    }


    $extraDays = 0;
    if (!is_null($promoCode)) {
        $promo = $promos->checkPromo($promoCode);
        if ($promo) {
            if (DEBUG) {
                fwrite($log, "////////////////////////////////////////\r\n");
            }
            fwrite($log, "Found valid promo code: $promoCode \r\n");
            $repeatPromo = $promos->checkRepeat($steamid_user, $promo['id']);
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
            if ($days_check && $num_check && !$repeatPromo) {
                if (DEBUG) {
                    fwrite($log, "Passed checks\r\n");
                }
                if ($promo['type'] == '1') {
                    //precent off
                    if (DEBUG) {
                        fwrite($log, "Type 1\r\n");
                    }
                    $amount = $amount * (100 / (100 - $promo['amount']));
                    if (DEBUG) {
                        fwrite($log, "amount: $amount\r\n");
                    }
                    $validPromo = true;
                } elseif ($promo['type'] == '2') {
                    if (DEBUG) {
                        fwrite($log, "Type 2\r\n");
                    }
                    $extraDays = $promo['amount'];
                    if (DEBUG) {
                        fwrite($log, "Extra Days: $extraDays\r\n");
                    }
                    $validPromo = true;
                }
            } else {
                if ($repeatPromo) {
                    fwrite($log, "Repeat promo code from $steamid_user \r\n");
                } else {
                    fwrite($log, "All promos were taken, skipping \r\n");
                }
                $validPromo = false;
            }
            if ($validPromo) {
                $stmt = $sb->ddb->prepare("INSERT INTO `promotions_redeemed` (promo_id,promo_code,steam_id)VALUES(:promo_id,:promo_code,:steam_id);");
                $stmt->execute(array(':promo_id' => $promo['id'], ':promo_code' => $promo['code'], ':steam_id' => $steamid_user));
                if (STATS) {
                    @$sysLog->stats("PC|" . $promo['type']);
                }           
            }
            if (DEBUG) {
                fwrite($log, "////////////////////////////////////////\r\n");
            }
        } else {
            fwrite($log, "Found invalid promo code: $promoCode \r\n");
        }
    }


    $days_purchased = round(($amount * $group['multiplier']) + $extraDays);
    if (DEBUG) {
        fwrite($log, "Days Purchased: $days_purchased\r\n");
    }
    unset($extraDays);
    $amount = $_POST['mc_gross'];
    $dp_string = "+" . $days_purchased . " days";
    $expire = strtotime($dp_string, $sign_up_date);

    if (CCC && $group['ccc_enabled']) {
        if (isset($_POST['option_name1']) && isset($_POST['option_name2'])) {

            $nameColor = str_replace("#", "", $_POST['option_name1']);
            $chatColor = str_replace("#", "", $_POST['option_name2']);
        } else {
            $nameColor = 'FFFF00';
            $chatColor = '009933';
        }
        $useCCC = true;
    } else {
        $useCCC = false;
    }

    fwrite($log, "Steam ID: $steamid_user($username)\r\n amount: $amount\r\n Sign Up Date: $sign_up_date\r\n" .
            "Days Purchased: $days_purchased\r\n Promo Code:$promoCode \r\n Email: $email\r\n Transaction ID: $txn_id\r\n Tier: $tier\r\n");
    if ($useCCC) {
        fwrite($log, "Tag: $tag\r\nName Color: $nameColor\r\nChat Color: $chatColor\r\n");
    }
    if (isset($killScript)) {
        die();
    }



    //checking if donor already exists
    try {
        $stmt = $sb->ddb->prepare("SELECT * FROM `donors` WHERE steam_id=?");
        $stmt->bindParam(1, $steamid_user, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        if (FORCE_IPN) {
            $errors = true;
            fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
            fwrite($log, "Attempting insertion as new donor.\r\n");
        } else {
            fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
            die();
        }
    }
    //if donor exists change the purchase dates around.
    if ($stmt->rowCount() == 1 && isset($row) && !empty($row)) {

        if ($row['tier'] != $tier && $row['activated'] == "1") { //check if donor is changing level.
            $grpChange = true; //user is changing groups

            $now = date('U');

            $oldGroup = $sb->getGroupInfo($row['tier']); //get the old groups info

            $cDaysLeft = round(($row['expiration_date'] - $now) / 86400, 2, PHP_ROUND_HALF_UP); //amount of days left until perks expire

            $change = round($cDaysLeft / $oldGroup['multiplier'], 2, PHP_ROUND_HALF_UP); //prorated dollar amount

            $extraDays = round($change * $group['multiplier'], 2, PHP_ROUND_HALF_UP); // how many days to be added to new level

            $expiration_date = round(($days_purchased + $extraDays) * 86400, 0, PHP_ROUND_HALF_UP) + $now; //setting the new expiration date
        } elseif ($row['activated'] == "2") {
            $expiration_date = $expire; //if its an expired donor use amount paid
        } else {
            $expiration_date = strtotime($dp_string, $row['expiration_date']); //return donor, same tier
        }

        unset($dp_string);
        $total_amount = $row['total_amount'];
        $total_amount = $total_amount + $amount;
        $renewal_date = $sign_up_date;
        $current_amount = $amount;
        $user_id = $row['user_id'];
        $last_txn_id = $row['txn_id'];
        $activated = $row['activated'];
    }
    if (isset($stmt)) {
        unset($stmt);
    }
    if (isset($row)) {
        unset($row);
    }

    // if we have a repeat donor check if its just a duplicate IPN from PayPal.
    if (isset($user_id)) {
        fwrite($log, "User is already in database.\r\n");
        if ($activated == '1') {
            $sbAdd = false;
        } else {
            $sbAdd = true;
        }
        //Update the donors information.
        try {
            $stmt = $sb->ddb->prepare("UPDATE `donors` SET `renewal_date` = :renewal_date,
                        `current_amount` = :current_amount,
                        `total_amount` = :total_amount,
                        `expiration_date` = :expiration_date,
                        `activated` = '1',
                         `txn_id` = :txn_id,
                         `tier` = :tier
                         WHERE `steam_id` = :steamid_user;");

            $stmt->execute(array(':renewal_date' => $renewal_date,
                ':current_amount' => $current_amount,
                ':total_amount' => $total_amount,
                ':expiration_date' => $expiration_date,
                ':txn_id' => $txn_id,
                ':tier' => $tier,
                ':steamid_user' => $steamid_user
            ));
            if ($stmt->rowCount() != 1) {
                throw new Exception("Something went wrong updating the donor in the database");
            }
            unset($stmt);
        } catch (Exception $ex) {
            fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
            die();
        }
    } else {
        fwrite($log, "User is not in the database, New donor.\r\n");
        $sbAdd = true;
        try {
            $stmt = $sb->ddb->prepare("INSERT INTO donors (username,steam_id,sign_up_date,email,renewal_date,current_amount,total_amount,expiration_date,steam_link,activated,txn_id,tier)
                        VALUES (:username,:steamid_user, :sign_up_date, :email, '0',:amount,:amount2,:expire,:steam_link, '1', :txn_id,:tier);");

            $stmt->execute(array(
                ':username' => $username,
                ':steamid_user' => $steamid_user,
                ':sign_up_date' => $sign_up_date,
                ':email' => $email,
                ':amount' => $amount,
                ':amount2' => $amount,
                ':expire' => $expire,
                ':steam_link' => $steam_link,
                ':txn_id' => $txn_id,
                ':tier' => $tier
            ));
        } catch (Exception $ex) {
            fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
            die();
        }
    }
    if (isset($stmt)) {
        unset($stmt);
    }
    if ($useCCC) {
        try {
            $stmt = $sb->ddb->prepare("SELECT * FROM `custom_chatcolors` WHERE identity = :steamid_user;");
            $stmt->bindParam(1, $steamid_user, PDO::PARAM_STR);
            $stmt->execute();
            $goodToGo = true;
        } catch (Exception $ex) {
            fwrite($log, date('m/j/Y g:i:s A') . ": Problem getting information from CCC database, moving on. \r\n");
            $errors = true;
        }
        if ($stmt->rowCount() == 1 && isset($goodToGo)) {
            unset($stmt);
            unset($goodToGo);
            try {
                $stmt = $sb->ddb->prepare("DELETE FROM `custom_chatcolors` WHERE identity = :steamid_user;");
                $stmt->bindParam(1, $steamid_user, PDO::PARAM_STR);
                $stmt->execute();
                $goodToGo = true;
            } catch (Exception $ex) {
                fwrite($log, date('m/j/Y g:i:s A') . ": Problem deleting user from CCC database, moving on. \r\n");
                $errors = true;
            }
        }
        if (isset($goodToGo)) {
            unset($stmt);
            unset($goodToGo);
            try {
                $stmt = $sb->ddb->prepare("INSERT INTO `custom_chatcolors` (`tag`, `identity`, `namecolor`, `textcolor`) VALUES (:tag,:steamid_user,:nameColor,:chatColor);");
                $stmt->execute(array(
                    ':tag' => $tag,
                    ':steamid_user' => $steamid_user,
                    ':nameColor' => $nameColor,
                    ':chatColor' => $chatColor
                ));
                $goodToGo = true;
            } catch (Exception $ex) {
                fwrite($log, date('m/j/Y g:i:s A') . ":Problem inserting user into CCC database, moving on. \r\n");
                $errors = true;
            }

            if ($stmt->rowCount() == 1) {
                if ($sb->queryServers("sm_reloadccc")) {
                    fwrite($log, "reloaded CCC successfully.\r\n");
                } else {
                    fwrite($log, "reloading CCC failed.\r\n");
                }
            }
        }
    }

    fwrite($log, "Finished inserting into the donor database.\r\n");
    if ($sbAdd) {
        fwrite($log, "Preparing for sourcebans insertion.\r\n");
        try {
            $sb->addDonor($steamid_user, $username, $tier);
        } catch (Exception $ex) {
            fwrite($log, date('m/j/Y g:i:s A') . ": " . $ex->getMessage() . " Line:" . $ex->getLine() . " \r\n");
            if (!FORCE_IPN) {
                die();
            }
        }
    } else {
        fwrite($log, "User is already active, skipping sourcebans.\r\n");
    }
    if (isset($errors)) {
        $errors = 'with errors';
    } else {
        $errors = '';
    }
    $sysLog->logAction("AUTOMATIC ACTION: $username Added $errors(New Donation)");

    if (sys_email) {
        try {
            $mail_body = "{$username} Has made a donation of \${$amount} though PayPal, and their donor perks have been automatically activated";
            $subject = "New \${$amount} donation from {$username} $errors";
            $mailHeader = "From: " . $mail['name'] . " <" . $mail['email'] . ">\r\n";


            if ($mail['useBCC']) {
                $to = $mail['recipient'] . ', ' . $mail['BCC'];
            } else {
                $to = $mail['recipient'];
            }
            @mail($to, $subject, $mail_body, $mailHeader);


            if ($mail['donor']) {
                @mail($email, $mail['donorSubject'], $mail['donorMsg'], $mailHeader);
            }
        } catch (Exception $ex) {
            $sysLog->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        }
    }

    fclose($fp);
}
if (STATS) {
    @$sysLog->stats("IPN");
}
fwrite($log, "All done here $errors, closing log file....good bye.");
fclose($log);
