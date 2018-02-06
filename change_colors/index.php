<?php
define('NineteenEleven', TRUE);
require_once '../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
require_once ABSDIR . 'scripts/rcon_code.php';
if (isset($_POST['loginSubmit'])) {

    $log = new log;
    try {
        $sb = new sb;
    } catch (Exception $ex) {
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        print "Oops something went wrong, please try again later.";
    }

    $steamid = filter_input(INPUT_POST, 'steamid', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    try {
        $stmt = $sb->ddb->prepare("SELECT * FROM `donors` WHERE steam_id=? AND email=? AND activated ='1';");
        $stmt->bindParam(1, $steamid);
        $stmt->bindParam(2, $email);
        $stmt->execute();
    } catch (Exception $ex) {
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        print "Oops something went wrong, please try again later.";
    }
    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $group = $sb->getGroupInfo($row['tier']);

        if ($group['ccc_enabled'] != 1) {
            print "<center><h1 class='error'>Sorry you are not in a group that has access to CCC, please consider upgrading if you would like to use this feature.</h1></center>";
            die();
        }
        $username = $row['username'];
        $exp = $row['expiration_date'];



        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['steamid'] = $steamid;
        $_SESSION['exp'] = $exp;
        $ip = $_SERVER['REMOTE_ADDR'];

        print("<center><h1 class='success'> Welcome back {$username} </h1></center>");
        $log->logAction("$username/$email/$ip logged into panel.");
        print("<script type='text/javascript'> setTimeout('reload()' , 1000)
		function reload(){
			window.location='perks.php'
		}</script>");
        exit();
    } else {
        print "<center><h1 class='error'>Wrong Username or Password</h1></center>";
        unset($mysqliD);
        unset($log);
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
                        <td colspan="3"><strong><center>Donor Login</center> </strong></td>
                    </tr>
                    <tr>
                        <td width="120">Steam ID</td>
                        <td width="6">:</td>
                        <td width="294"><input name="steamid" type="text" id="steamid"></td>
                    </tr>
                    <tr>
                        <td>PayPal Email</td>
                        <td>:</td>
                        <td><input name="email" type="email" id="email"></td>
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

