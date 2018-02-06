<?php

session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}
$session = $_SESSION;
session_write_close();

define('NineteenEleven', TRUE);

require_once '../../../includes/config.php';
require_once ABSDIR . 'includes/SteamClass.php';
require_once ABSDIR . 'includes/LanguageClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/LoggerClass.php';
$log = new log;
$sb = new sb;
$language = new language;
if (isset($session['language'])) {
    $lang = $language->getLang($session['language']);
} else {
    $lang = $language->getLang(DEFAULT_LANGUAGE);
}

$args = array(
    "username" => FILTER_SANITIZE_STRING,
    "steam_id" => FILTER_SANITIZE_STRING,
    "sign_up_date" => FILTER_SANITIZE_STRING,
    "email" => FILTER_SANITIZE_EMAIL,
    "renewal_date" => FILTER_SANITIZE_STRING,
    "current_amount" => array('filter' => FILTER_VALIDATE_FLOAT,
        'flags' => FILTER_FLAG_ALLOW_FRACTION),
    "total_amount" => array('filter' => FILTER_VALIDATE_FLOAT,
        'flags' => FILTER_FLAG_ALLOW_FRACTION),
    "expiration_date" => FILTER_SANITIZE_STRING,
    "steam_link" => FILTER_SANITIZE_URL,
    "notes" => FILTER_SANITIZE_STRING,
    "activated" => FILTER_SANITIZE_NUMBER_INT,
    "tier" => FILTER_SANITIZE_NUMBER_INT,
    "user_id" => FILTER_SANITIZE_NUMBER_INT,
    "edit_user_form" => FILTER_SANITIZE_NUMBER_INT
);

$input = filter_input_array(INPUT_POST, $args);
$required = array('username', 'steam_id', 'sign_up_date', 'current_amount', 'activated');
$rehash = false;
$steam_id = $input['steam_id'];

function checkDates($val) {
    global $input;
    if ($input[$val] == 'Never' || empty($input[$val])) {
        $input[$val] = 0;
    } else {
        $input[$val] = strtotime($input[$val]);
    }
}

if (!isset($input['edit_user_form'])) {
    die("1");
}

foreach ($required as $val) {
    if (!array_key_exists($val, $input) || empty($input[$val])) {
        die(printf('<div class="alert alert-danger" role="alert">' . $lang->error[0]->msg2 . '</div>', $val));
    }
}


checkDates("sign_up_date");
checkDates("renewal_date");
checkDates("expiration_date");


try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DONATIONS_DB . ';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die('Unable to open connection to MySQL server.');
}

try {
    $stmt = $db->prepare("SELECT activated,tier,steam_id FROM `donors` WHERE user_id=?;");
    $stmt->bindValue(1, $input['user_id'], PDO::PARAM_INT);
    $stmt->execute();
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}
$rows = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "<div class='alert alert-danger' role='alert'>" . "Unable to find user in the database." . "</div>";
    $log->logError("Unable to find user in the database");
    die();
}

//see if steam id changed
if ($rows['steam_id'] != $input['steam_id']) {
    try {
        $steam_id = new SteamIDConvert($input['steam_id']);
        $steam_id->SteamIDCheck();
        $input['steam_id'] = $steam_id->steam_id;
    } catch (Exception $ex) {
        $err = sprintf("%s, ignoring steam id changes. ", $ex->getMessage());
        $log->logError($err, $ex->getFile(), $ex->getLine());
        $input['steam_id'] = $rows['steam_id'];
        echo "<div class='alert alert-danger' role='alert'>" . $err . "</div>";
        unset($err);
    }
}


$post_activated = $rows['activated'];
$post_tier = $rows['tier'];


//check to see if there was a change in activation

try {
    if ($post_activated != $input['activated']) {
        if ($input['activated'] == 1) {
            $r = $sb->addDonor($input['steam_id'], $input['username'], $input['tier']);
            if ($r === TRUE) {
                $rehash = true;
            }
            unset($r);
        } else {
            $r = $sb->removeDonor($input['steam_id'], $input['tier']);
            if ($r === TRUE) {
                $rehash = true;
            }
            unset($r);
        }
    }
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}
try {
    if ($post_tier != $input['tier']) {
        $r = $sb->removeDonor($steam_id, $input['tier']);
        if ($r === TRUE) {
            $a = $sb->addDonor($input['steam_id'], $input['username'], $input['tier']);
            if ($a === TRUE) {
                $rehash = true;
            }
        }
        unset($r);
        unset($a);
    }
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}
try {
    $stmt = $db->prepare("UPDATE `donors` SET `username` = :username, `steam_id` = :steam_id, `sign_up_date` = :sign_up_date, `email` = :email, `renewal_date` = :renewal_date, `current_amount` = :current_amount, `total_amount` = :total_amount, `expiration_date` = :expiration_date, `steam_link` = :steam_link, `notes` = :notes, `activated` = :activated, `tier` = :tier WHERE `user_id` = :user_id;");
    $stmt->execute(array(':username' => $input['username'],
        ':steam_id' => $input['steam_id'],
        ':sign_up_date' => $input['sign_up_date'],
        ':email' => $input['email'],
        ':renewal_date' => $input['renewal_date'],
        ':current_amount' => $input['current_amount'],
        ':total_amount' => $input['total_amount'],
        ':expiration_date' => $input['expiration_date'],
        ':steam_link' => $input['steam_link'],
        ':notes' => $input['notes'],
        ':activated' => $input['activated'],
        ':tier' => $input['tier'],
        ':user_id' => $input['user_id']));
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}
$affected_rows = $stmt->rowCount();


if ($rehash) {
    if (!$sb->queryServers('sm_reloadadmins')) {
        $log->logAction(sprintf($lang->logmsg[0]->edit, $session['username'], $input['username']));
        $log->logError('Server rehash failed');
    } else {
        $log->logAction(sprintf($lang->logmsg[0]->edit, $session['username'], $input['username']));

        $log->logAction('Rehashed all servers');
    }
} else {
    $log->logAction(sprintf($lang->logmsg[0]->edit, $session['username'], $input['username']));
}
printf("<div class='alert alert-success' role='alert'>" . $lang->sysmsg[0]->successedit . "</div>", $input['username']);
if (STATS) {
    @$log->stats("EU");
}