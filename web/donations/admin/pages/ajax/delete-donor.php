<?php

session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}

$session = $_SESSION;
session_write_close();

define('NineteenEleven', TRUE);

require_once '../../../includes/config.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'includes/LanguageClass.php';
require_once ABSDIR . 'includes/LoggerClass.php';

$log = new log;
$language = new language;
if (isset($session['language'])) {
    $lang = $language->getLang($session['language']);
} else {
    $lang = $language->getLang(DEFAULT_LANGUAGE);
}

if ($_POST['action'] != 'delete_user' || $_POST['ajax'] != 1) {
    die();
}


try {
    $sb = new sb;
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}
$steam_id = filter_input(INPUT_POST, 'steam_id', FILTER_SANITIZE_STRING);


try {
    $stmt = $sb->ddb->prepare('SELECT `activated` FROM `donors` WHERE `steam_id` =?;');
    $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $activated = $res['activated'];
} catch (Exception $ex) {
    echo $ex->getMessage();
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}

unset($stmt);
try {
    $sb->ddb->beginTransaction();
    $stmt = $sb->ddb->prepare("DELETE FROM `donors` WHERE `steam_id` =?;");
    $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
    $stmt->execute();
    $sb->ddb->commit();
} catch (PDOException $ex) {
    //Something went wrong rollback!
    $sb->ddb->rollBack();
    echo 'rolled back ' . $ex->getMessage();
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}

if ($activated == 1) {
    try {
        if ($sb->removeDonor($steam_id)) {
            if ($sb->queryServers('sm_reloadadmins')) {
                $log->logAction($lang->sysmsg[0]->succrehash);
            } else {
                $log->logError($lang->sysmsg[0]->failrehash);
            }
        } else {
            printf("<h1 class='error'>" . $lang->error[0]->msg1 . "</h1>", $steam_id);
            $log->logError('Unable to remove $steam_id from sourcebans.');
        }
    } catch (Exception $ex) {
        print $ex->getMessage();
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    }

    unset($stmt);
//$sb->userGroup = array('srv_group' => $srv_group, 'group_id' => $group_id, 'srv_group_id' => $srv_group_id, 'server_id' => $server_id, 'aid' => $aid);
    if (CCC) {
        try {

            $stmt = $sb->ddb->prepare("SELECT `index` FROM `custom_chatcolors` WHERE `identity` =?;");
            $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() == 1) {
                unset($stmt);
                $stmt = $sb->ddb->prepare("DELETE FROM `custom_chatcolors` WHERE `identity` =?;");
                $stmt->bindParam(1, $steam_id, PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (Exception $ex) {
            print $ex->getMessage();
            $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        }
    }
}
print "<div class='alert alert-success' role='alert'>" . sprintf($lang->sysmsg[0]->deleted, $steam_id) . "</div>";
$divId = filter_input(INPUT_POST, 'divId', FILTER_SANITIZE_NUMBER_INT);
printf("<script>$('#%s').effect('explode',1000);</script>", $divId);
$log->logAction(sprintf($lang->logmsg[0]->deleted, $session['username'], $steam_id));
if (STATS) {
    @$log->stats("DEL");
}