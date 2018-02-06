<?php
if (isset($_POST['loginSubmit'])) {
    define('NineteenEleven', TRUE);
    require_once '../includes/config.php';
    require_once ABSDIR . 'includes/LoggerClass.php';
    $log = new log;
    $user_name = $_POST['user_name'];
    $password = sha1(sha1(SB_SALT . $_POST['password']));

    try {
        $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . SOURCEBANS_DB . ';charset=utf8', DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (Exception $e) {
        die('Unable to open connection to MySQL server.');
    }

    try {
        $stmt = $db->prepare("SELECT * FROM " . SB_PREFIX . "_admins WHERE user=? and password=? and srv_group = '" . SB_ADMINS . "';");
        $stmt->execute(array($user_name, $password));
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "<h3>Something went wrong with our system.</h3>";
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    }
    $count = count($row);
    if ($count === 1) {
        $email = $row[0]['email'];
        session_start();
        $_SESSION['username'] = $user_name;
        $_SESSION['email'] = $email;
        $_SESSION['table'] = false;
        ini_set('default_socket_timeout', 10);
        $json = @json_decode(@file_get_contents('http://1911.expert/dc-version/version.php'));

        if (!empty($json) && VERSION_NEW != $json->version) {

            $_SESSION['message'] = "<div class='alert alert-warning' role='alert'>There is an update available. ";

            if (isset($json->msg)) {
                $_SESSION['message'] .= $json->msg;
            }
            $_SESSION['message'] .= "</div>";
        }
        print("<center><h1 class = 'success'> Welcome back $user_name </h1></center>");
        $log->logAction("$user_name logged in from " . $_SERVER['REMOTE_ADDR']);
        print("<script type = 'text/javascript'> setTimeout('reload()', 1000)
                function reload(){
                window.location = 'show_donations.php'
                }</script>");
        exit();
    } else {
        print "<center><h1 class='error'>Wrong Username or Password</h1></center>";
        $log->logAction("Failed login attempt for user name: $user_name from " . $_SERVER['REMOTE_ADDR']);
    }
}
?>
<div id='login'>
    <table width="300" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
        <tr>
        <form id="loginSubmit" method="POST" action="index.php">
            <td>
                <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">
                    <tr>
                        <td colspan="3"><strong>Admin Login </strong></td>
                    </tr>
                    <tr>
                        <td width="78">Username</td>
                        <td width="6">:</td>
                        <td width="294"><input name="user_name" type="text" id="user_name"></td>
                    </tr>
                    <tr>
                        <td>Password</td>
                        <td>:</td>
                        <td><input name="password" type="password" id="password"></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td><input type="submit" name="loginSubmit" value="Login" form='loginSubmit' /><input type='button' id='hideLogin' value='Cancel' /></td>

                    </tr>
                </table>
            </td>
        </form>
        </tr>
    </table>
</div>

