<?php

session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}
$session = $_SESSION;
session_write_close();
if (!defined('NineteenEleven')) {
    define('NineteenEleven', TRUE);
}

//Filter the inputs from POST
$args = array(
    'action' => FILTER_SANITIZE_STRING,
    'cmd' => FILTER_SANITIZE_STRING,
    'allServers' => FILTER_VALIDATE_BOOLEAN,
    'ajax' => FILTER_SANITIZE_NUMBER_INT,
    'id' => FILTER_SANITIZE_NUMBER_INT
);

$data = filter_input_array(INPUT_POST, $args);
//var_dump($data);
if (isset($data['ajax']) && isset($data['id'])) {
    require_once $session['ABSDIR'] . "includes/config.php";
    require_once $session['ABSDIR'] . "scripts/rcon_code.php";
    require_once $session['ABSDIR'] . "includes/SourceBansClass.php";
    require_once ABSDIR . 'includes/LoggerClass.php';
    $log = new log;
    $srcds_rcon = new srcds_rcon();
    try {
        $sb = new sb;
    } catch (Exception $ex) {
        echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        die();
    }

    if ($data['allServers'] == 'true') {
        $fail = 0;
        $success = 0;
        foreach ($sb->sdb->query("SELECT * from `" . SB_PREFIX . "_servers` WHERE 1;") as $server) {
            $OUTPUT = @$srcds_rcon->rcon_command($server['ip'], $server['port'], $server['rcon'], $data['cmd']);
            if ($OUTPUT === false) {
                $OUTPUT = "Unable to connect!\n";
                $fail = $fail + 1;
            } else {
                $success = $success + 1;
            }
            echo $server['ip'] . ":" . $server['port'] . " Response\n" . $OUTPUT;
        }
        if ($fail === 0) {
            print("$success Game Servers Successfully Queried.\n");
        } else {
            print("$success Game Servers Successfully Queried. \n $fail servers were unable to connect.");
        }
        $log->logAction(sprintf("%s send command '%s' to all servers", $session['username'], $data['cmd']));
    } else {

        $stmt = $sb->sdb->prepare("SELECT * FROM `" . SB_PREFIX . "_servers` WHERE sid=:id;");
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        $OUTPUT = @$srcds_rcon->rcon_command($server['ip'], $server['port'], $server['rcon'], $data['cmd']);
        if ($OUTPUT === false) {
            $OUTPUT = "Unable to connect!";
        }

        echo $server['ip'] . ":" . $server['port'] . " Response\n" . $OUTPUT;

        $log->logAction(sprintf("%s send command '%s' to %s", $session['username'], $data['cmd'], $server['ip'] . ":" . $server['port']));
    }
}
if (STATS) {
    @$log->stats("SQ");
}