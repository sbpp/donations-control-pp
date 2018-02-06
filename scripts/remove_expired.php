<?php

//This script will query the database and remove all expired donors
//set up with cron to call @daily
define('NineteenEleven', TRUE);
require_once'../includes/config.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'scripts/rcon_code.php';
$sysLog = new log;
try {
    $sb = new sb;
} catch (Exception $ex) {
    $sysLog->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

$log = fopen(ABSDIR . 'admin/logs/Remove-Expired-' . date('m-d-Y_G-i-s') . '.log', "a");

$today = date('U');
$tommorow = $today + 1;
$in5days = $today + (86400 * 8);

$query_sb = false;
$i = 0;
//query database
try {
    foreach ($sb->ddb->query("SELECT * FROM donors WHERE expiration_date <= '$today' AND `activated` = 1;") as $donor) {
        $i++;

        $steam_id = $donor['steam_id'];
        $username = $donor['username'];
        $tier = $donor['tier'];
        //change $activated
        //turn off sourcebans

        $sb->ddb->query("UPDATE `donors` SET `activated` = '2' WHERE `steam_id` = '{$steam_id}';");
        $sb->removeDonor($steam_id, $tier);
        $query_sb = true;
        fwrite($log, "$username removed from sourcebans successfully\r\n");
        $sysLog->logAction("AUTOMATIC ACTION: $username Removed (Perks Expired)");
        $group = $sb->getGroupInfo($tier);

        if ($group['ccc_enabled']) {
            @$sb->ddb->query("DELETE FROM `custom_chatcolors` WHERE identity ='" . $steam_id . "';");
        }
    }
} catch (Exception $ex) {
    fwrite($log, "Something went wrong removing $username. " . $ex->getMessage() . " \r\n");
    $sysLog->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}
fwrite($log, $i . " Users expired\r\n");
if ($query_sb) {
    if ($sb->queryServers('sm_reloadadmins')) {
        fwrite($log, "Servers Rehashed\r\n");
    }
}


try {
    foreach ($sb->ddb->query("SELECT * FROM donors WHERE expiration_date BETWEEN '$tommorow' AND '$in5days' AND `activated` = 1;") as $donor) {
        //send email
        if (sys_email && reminder_email) {

            $subject = sprintf($reminder['subject'], $donor['username']);
            $mail_body = sprintf($reminder['body'], $donor['username'], date('m/j/Y', $donor['expiration_date']));
            $mailHeader = "From: " . $mail['name'] . " <" . $mail['email'] . ">\r\n";
            $to = $donor['email'];
            if (!DEBUG) {
                @mail($to, $subject, $mail_body, $mailHeader);
            }
//            @mail($mail['recipient'], $subject, $mail_body, $mailHeader);
        }
    }
} catch (Exception $ex) {
    $sysLog->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}

fwrite($log, "All done here, closing log file....good bye.");
fclose($log);
