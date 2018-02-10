<?php
define('NINETEENELEVEN', true);
require_once '../includes/config.php';
if (isset($_POST['loginSubmit'])) {
    require_once ABSDIR . 'includes/LoggerClass.php';
    $log = new log();

    $_POST['username'] = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $_POST['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_PREFIX);
    $db->query("SELECT password, email, authid FROM `:prefix_admins` WHERE user = :user AND srv_group = :group");
    $db->bind(':user', $_POST['username']);
    $db->bind(':group', SB_ADMINS);
    $admin = $db->single();

    if (password_verify($_POST['password'], $admin['password'])) {
        session_start();
        header("refresh:2;url=show_donations.php");
        $_SESSION = [
            'username' => $_POST['username'],
            'email' => $admin['email'],
            'authid' => $admin['authid'],
            'table' => false
        ];
        Template::render('ui.success', ['message' => 'Welcome back '.$_POST['username']]);
        $log->logAction($_POST['username']." logged in from " . $_SERVER['REMOTE_ADDR']);
        exit();

        /*
        $json = @json_decode(@file_get_contents('http://1911.expert/dc-version/version.php'));

        if (!empty($json) && VERSION_NEW != $json->version) {

            $_SESSION['message'] = "<div class='alert alert-warning' role='alert'>There is an update available. ";

            if (isset($json->msg)) {
                $_SESSION['message'] .= $json->msg;
            }
            $_SESSION['message'] .= "</div>";
        }*/
    }

    Template::render('ui.error', ['message' => 'Wrong Username or Password']);
    $log->logAction("Failed login attempt for user name: ".$_POST['username']." from " . $_SERVER['REMOTE_ADDR']);
}

Template::render('admin.index');
