<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("location:index.php");
}
define('NineteenEleven', TRUE);
require_once '../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'scripts/rcon_code.php';
$log = new log;


$username = $_SESSION['username'];
$email = $_SESSION['email'];
$steamid = $_SESSION['steamid'];
$ip = $_SERVER['REMOTE_ADDR'];
$exp = $_SESSION['exp'];
try {
    $sb = new sb;
} catch (Exception $ex) {
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    print "Oops something went wrong, please try again later.";
}

if (isset($_POST['index'])) {

    $index = $_POST['index'];
    $nameColor = str_replace("#", "", $_POST['nameColor']);
    $chatColor = str_replace("#", "", $_POST['chatColor']);

    try {
        $stmt = $sb->ddb->prepare("UPDATE `custom_chatcolors` SET  `namecolor` =  :nameColor,`textcolor` =  :chatColor WHERE `index` = :index;");
        $stmt->execute(array(':nameColor' => $nameColor, ':chatColor' => $chatColor, ':index' => $index));
    } catch (Exception $ex) {
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        Echo "<h3>Something went wrong, please try again later. If the problem persists please contact an administrator</h3>";
        die();
    }
    $log->logAction("$username/$email/$ip changed thier colors to $chatColor(chat) and $nameColor(name).");
    if ($sb->queryServers("sm_reloadccc")) {
        printf("<center><h1>You name color has been changed to %s, and chat color to %s</h1></center>", $nameColor, $chatColor);
        $log->logAction("CCC reloaded successfully");
    }
} else {
    echo "<center><h1> Welcome back $username your donor perks expire on " . date('l F j, Y', $exp) . "</h1></center>";
}
try {
    $stmt = $stmt = $sb->ddb->prepare("SELECT * FROM `custom_chatcolors` WHERE identity = ?;");
    $stmt->bindParam(1, $steamid);
    $stmt->execute();
} catch (Exception $ex) {
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    Echo "<h3>Something went wrong, please try again later. If the problem persists please contact an administrator</h3>";
    die();
}

if ($stmt->rowCount() == 1) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nameColor = $row['namecolor'];
    $chatColor = $row['textcolor'];
    $index = $row['index'];
} else {
    printf("Unable to find chat colors for %s", $username);
}




echo '
<html>
<body>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<script type="text/javascript" src="../js/jscolor/jscolor.js"></script>
</head>
<center>
<form id="color" method="post" action="perks.php">
<p><input class="color" name="nameColor" value="#' . $nameColor . '" id="colorInput">Name Color<input class="color" name="chatColor" value="#' . $chatColor . '" id="colorInput">Chat Color</p>
<input type="hidden" name="index" value="' . $index . '">
<input type="submit" value="Change Colors" form="color">
</form>
</center>
</body>
</html>
';

