<?php

/*
  CREATE TABLE IF NOT EXISTS `donations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `steamId` varchar(24) NOT NULL,
  `itemId` int(10) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `steamId` (`steamId`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 */


define('NineteenEleven', TRUE);
require_once'../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/SteamClass.php';
$log = new log;
$sb = new sb;
$notes = "Key Donation"; //what to add in the notes column of DC when key is traded
$keyId = "5021"; //valve item id for key
$keyVal = '1.50'; //what you value a key at, for accounting in DC.
$keyDays = "7"; //how many days of perks per key
$now = date('U');

function sendmail($subject, $mail_body) {
    global $mail;
    if (sys_email) {
        $mailHeader = "From: " . $mail['name'] . " <" . $mail['email'] . ">\r\n";

        if ($mail['useBCC']) {
            $to = $mail['recipient'] . ', ' . $mail['BCC'];
        } else {
            $to = $mail['recipient'];
        }
        @mail($to, $subject, $mail_body, $mailHeader);
    }
}

//$sql = "SELECT * FROM `donations` WHERE `processed` = '0';";
//
//$result = $mysqli->query($sql) or die($mysqli->error ." ". $mysqli->errono);

foreach ($sb->ddb->query("SELECT * FROM `donations` WHERE `processed` = '0';") as $row) {
    try {
        $steam = new SteamIDConvert($row['steamId']);
        $steam->SteamIDCheck();
    } catch (Exception $ex) {
        $log->logBot("Failed to match Steam ID: " . $row['steamId'] . " " . $ex->getMessage() . "\r\n");
        continue;
    }
    $itemId = $row['itemId'];
    $donateTime = strtotime($row['timestamp']);
    $donorName = $steam->playerSummaries->response->players[0]->personaname;

    if ($itemId == $keyId) {
        $log->logBot("$donorName (" . $steam->steam_id . ") donated one key ");
    } else {
        $log->logBot("Recieved item $itemId from $donorName. Exiting script, not action taken.");
        continue;
    }

    //check Donation Control database for current donor.
//    $DCsql = "SELECT * FROM `donors` WHERE `steam_id`='" . $idArray['steamid'] . "';";
//    $DCresult = $mysqli->query($DCsql);
    try {
        $stmt = $sb->ddb->prepare("SELECT * FROM `donors` WHERE `steam_id`=?;");
        $stmt->bindParam(1, $steam->steam_id);
        $stmt->execute();
    } catch (Exception $ex) {
        $log->logBot($ex->getMessage() . "\r\n");
        $failedSQL = true;
    }
    if ($stmt->rowCount() >= 1 && !isset($failedSQL)) {
        //current donor
        $log->logBot("Found {$donorName} in Donations Control database");
        $DCdonor = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($stmt);
        $DCtier = $DCdonor['tier'];
        $DCstatus = $DCdonor['activated']; //1 = active 2 = inactive
        $total_amount = $keyVal + $DCdonor['total_amount'];

        if ($DCtier == 1 && $DCstatus == 2) {
            $expiration_date = strtotime("+" . $keyDays . " days", $now);
            $sbNew = true;
        } elseif ($DCtier == 1 && $DCstatus == 1) {
            $expiration_date = strtotime("+" . $keyDays . " days", $DCdonor['expiration_date']);
            $sbNew = false;
        } else {

            $subject = "[TRADE BOT] PROBLEM PROCESSING KEY DONATION";
            $mail_body = "{$donorName}:" . $steam->steam_id . " is not in a group that accepts keys , this will cause a conflict with the trade bot.\r\n No perks have been granted, please either return the key or manually enter the donation!";
            sendmail($subject, $mail_body);
            $log->logBot("{$donorName}:" . $steam->steam_id . " is not in a group that accepts keys , EXITING...NO ACTION TAKEN.");
            continue;
        }

        try {
            $stmt = $sb->ddb->prepare("UPDATE `donors` SET `activated` = '1', `total_amount` = '$total_amount', `current_amount` = '$keyVal', `notes` = '$notes',`expiration_date` = '$expiration_date' WHERE `steam_id` = ?;");
            $stmt->bindParam(1, $steam->steam_id);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                $good = true;
            } else {
                $good = false;
            }
        } catch (Exception $ex) {
            $log->logBot($ex->getMessage() . "\r\n");
            die();
        }
    } else {
        //make new donor in Donations Control.

        $expiration_date = strtotime("+" . $keyDays . " days", $now);
        $stmt = $sb->ddb->prepare("INSERT INTO `donors` (username,
										steam_id,
										sign_up_date,
										current_amount,
										total_amount,
										expiration_date,
										steam_link,
										notes,
										activated,
										tier)
										VALUES
										('{$donorName}',
										'" . $steam->steam_id . "',
										'{$now}',
										'{$keyVal}',
										'{$keyVal}',
										'{$expiration_date}',
										'" . $steam->steam_link . "',
										'{$notes}',
										'1',
										'1');");
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $good = true;
        } else {
            $good = false;
        }
        $sbNew = true;
    }
    if ($sbNew && $good) {
        if ($sb->addDonor($steam->steam_id, $donorName, '1')) {
            //$sb->queryServers('sm_reloadadmins');
        } else {
            $subject = "[TRADE BOT] Sourcebans insertion failed.";
            $mail_body = "I was able to add {$donorName}:" . $steam->steam_id . " to the Donations Control database, but was unable to insert them into sourcebans, manual action nessicary.";
            sendmail($subject, $mail_body);
            $log->logBot($mail_body);
            continue;
        }
    }

    $sb->ddb->query("UPDATE `donations` SET `processed` = 1 WHERE id ='" . $row['id'] . "';");

    $subject = "[TRADE BOT] New key donation from {$donorName}";
    $mail_body = "{$donorName}:" . $steam->steam_id . " has traded a key to me, and their perks have been automatically activated.";
    sendmail($subject, $mail_body);
    $log->logBot($mail_body);
    $log->logBot("-------------------------------------------------------------------------");
    $log->logAction("AUTOMAIC ACTION: {$donorName}:" . $steam->steam_id . ": Traded 1 key");
    unset($steam);
}


