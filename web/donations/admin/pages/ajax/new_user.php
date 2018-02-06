<?php

session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die('sadas');
}
$session = $_SESSION;
session_write_close();

define('NineteenEleven', TRUE);

require_once '../../../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/SteamClass.php';
require_once ABSDIR . 'includes/LanguageClass.php';
$log = new log;
$language = new language;
if (isset($_SESSION['language'])) {
    $lang = $language->getLang($_SESSION['language']);
} else {
    $lang = $language->getLang(DEFAULT_LANGUAGE);
}

$args = array(
    "steam_id" => FILTER_SANITIZE_STRING,
    "sign_up_date" => FILTER_SANITIZE_STRING,
    "email" => FILTER_SANITIZE_EMAIL,
    "renewal_date" => FILTER_SANITIZE_STRING,
    "current_amount" => array('filter' => FILTER_VALIDATE_FLOAT,
        'flags' => FILTER_FLAG_ALLOW_FRACTION),
    "total_amount" => array('filter' => FILTER_VALIDATE_FLOAT,
        'flags' => FILTER_FLAG_ALLOW_FRACTION),
    "expiration_date" => FILTER_SANITIZE_STRING,
    "notes" => FILTER_SANITIZE_STRING,
    "activated" => FILTER_VALIDATE_BOOLEAN,
    "tier" => FILTER_SANITIZE_NUMBER_INT,
    "new_user_form" => FILTER_SANITIZE_NUMBER_INT
);

$input = filter_input_array(INPUT_POST, $args);
$required = array('steam_id', 'sign_up_date', 'current_amount', 'activated');
$rehash = false;

function checkDates($val) {
    global $input;
    if ($input[$val] == 'Never' || empty($input[$val]) || $input[$val] == 0) {
        $input[$val] = 0;
    } else {
        $input[$val] = strtotime($input[$val]);
    }
}

if (!isset($input['new_user_form'])) {
    die("1");
}

foreach ($required as $val) {
    if (!in_array($val, $input) || empty($input[$val])) {
        die(printf('<div class="alert alert-danger" role="alert">' . $lang->error[0]->msg2 . '</div>', $val));
    }
}


checkDates("sign_up_date");
checkDates("renewal_date");
checkDates("expiration_date");


try {
    $sb = new sb;
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

try {
    $stmt = $sb->ddb->prepare("SELECT username FROM `donors` WHERE steam_id=?;");
    $stmt->bindParam(1, $input['steam_id'], PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    printf('<div class="alert alert-danger" role="alert">There was a problem fetching infomation from MySQL server: %s</div>', $ex->getMessage());
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

if (count($rows) != 0) {
    printf('<div class="alert alert-danger" role="alert">User is already in the system with username %s, and steam id %s.</div>', $rows[0]['username'], $input['steam_id']);
    die();
}

try {
    $steam_id = new SteamIDConvert($input['steam_id']);
    $steam_id->SteamIDCheck();
    $input['steam_id'] = $steam_id->steam_id;
    $input['username'] = $steam_id->playerSummaries->response->players[0]->personaname;
} catch (Exception $ex) {
    print('<div class="alert alert-danger" role="alert">' . $ex->getMessage . '</div>');
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}
if (CCC) {
    $group = $sb->getGroupInfo($input['tier']);

    if ($group['ccc_enabled']) {
        try {
            $stmt = $sb->ddb->prepare("INSERT INTO `custom_chatcolors` (`tag`, `identity`, `namecolor`, `textcolor`) VALUES (:tag,:steamid_user,'FFFF00','009933');");
            $stmt->execute(array(
                ':tag' => $group['name'],
                ':steamid_user' => $input['steam_id']
            ));
            unset($stmt);
        } catch (Exception $ex) {
            echo "<div class='alert alert-danger' role='alert'>Error inserting into CCC database.</div>";
            $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        }
    }
}
try {
    $stmt = $sb->ddb->prepare("INSERT INTO `donors`(`username`,`steam_id`,`sign_up_date`,`email`,`renewal_date`,`current_amount`,`total_amount`,`expiration_date`,`notes`,`activated`,`tier`)VALUES(:username,:steam_id,:sign_up_date,:email,:renewal_date,:current_amount,:total_amount,:expiration_date,:notes,:activated,:tier);");
    $stmt->execute(array(
        ':username' => $input['username'],
        ':steam_id' => $input['steam_id'],
        ':sign_up_date' => $input['sign_up_date'],
        ':email' => $input['email'],
        ':renewal_date' => $input['renewal_date'],
        ':current_amount' => $input['current_amount'],
        ':total_amount' => $input['total_amount'],
        ':expiration_date' => $input['expiration_date'],
        ':notes' => $input['notes'],
        ':activated' => $input['activated'],
        ':tier' => $input['tier']
    ));
    $insertId = $sb->ddb->lastInsertId();
} catch (Exception $ex) {
    print('<div class="alert alert-danger" role="alert">' . $ex->getMessage . '</div>');
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}
try {
    $sb->addDonor($input['steam_id'], $input['username'], $input['tier']);
} catch (Exception $ex) {
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}
printf("<div class='alert alert-success' role='alert'>%s has been added to the system</div>", $input['username']);
$log->logAction(sprintf("%s added %s as new donor", $session['username'], $input['username']));
if (STATS) {
    @$log->stats("ME");
}