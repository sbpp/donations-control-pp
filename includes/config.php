<?php

/* Donations Control version 3.0.0 by NineteenEleven.
 * http://nineteeneleven.info
 * if you find this helpful please consider donating.
 */
if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}
//Fill in your preferences, and information


/*
 * PayPal Info
 */

define('PP_EMAIL', 'your@paypal.com');              //The Paypal account's email address
define('PP_DESC', 'Donation to your clans servers');  //Paypal purchase description
define('PP_IPN', 'http://yourdomain.com/donation/scripts/ipn.php'); //Address to ipn.php included within the donations folder
define('PP_SUCCESS', 'http://yourdomain.com/');        //Address to send donor to after successful donation
define('PP_FAIL', 'http://yourdomain.com/');       //Address to send donor to after cancel while donating / other error
define('PP_CURRENCY', 'USD'); //https://developer.paypal.com/webapps/developer/docs/classic/api/currency_codes/#id09A6G0U0GYK
/////////////////////////////////////////////////////////////////////////////////////////////////////////
define('PP_SANDBOX', false); //Use PayPal Sandbox for testing?
define('PP_SANDBOX_EMAIL', 'yoursandbox@paypal.com');




/*
 * Donations Database info
 */

define('DB_HOST', 'localhost');        //set MySQL host
define('DB_USER', 'dev');             //MySQL username
define('DB_PASS', 'password2strong');         //MySQL password
define('DONATIONS_DB', 'donations');   //donations database




/*
 * Sourcebans info
 * Sourcebans is required as of 2.x.
 */

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
define('SB_DB', false); //ONLY SET TRUE IF SOURCEBANS IS ON A DIFFERENT MYSQL SERVER
define('SB_SV_HOST', 'localhost');      //set MySQL host ONLY NEEDED IF SOURCEBANS IS ON A DIFFERENT MYSQL SERVER
define('SB_SV_USER', 'dev');         //MySQL username ONLY NEEDED IF SOURCEBANS IS ON A DIFFERENT MYSQL SERVER
define('SB_SV_PASS', 'password2strong');       //MySQL password ONLY NEEDED IF SOURCEBANS IS ON A DIFFERENT MYSQL SERVER
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
define('SOURCEBANS_DB', 'sourcebans'); // sourcebans database, this is needed.
define('SB_PREFIX', 'sb'); //Sourcebans database prefix. Only change this value if you changed your database prefix when setting up SourceBans.
define('SB_SALT', 'SourceBans'); //dont change this unless you changed your salt in sourcebans (if you dont know what salt is, you didnt change it)
define('SB_ADMINS', 'Administrators'); //name of admin group in sourcebans which has access to the donor panel
///////////////////////////////////////////////////////////////////////////////////////////




/*
 * System Emails Settings
 */

define('sys_email', true);              //Turn on system emails?
$mail['name'] = 'Donations';                 //senders name
$mail['email'] = 'donations@yourdomain.com';    //senders e-mail adress
$mail['recipient'] = 'your@email.com';   //recipient
$mail['useBCC'] = true;                         //add BCC
$mail['BCC'] = 'your@friend.com';           //BCC
$mail['donor'] = true; //Send confimation/ thankyou email to the donor?
$mail['donorSubject'] = 'Thank your for your donation';
$mail['donorMsg'] = 'Message to send to your donors';

define('reminder_email', false); //will send an email to donors every day for the last 5 days before their perks expire.
$reminder['subject'] = "%s, your donor perks are going to expire!"; //Subject of the email, %s is the username
$reminder['body'] = "Salutations %s \r\n, your donor perks are set to expire on %s. If you would like to continue to "
        . "recieve your donor benefits please visit http://YOURDOMAIN.com/donate and renew your perks.\r\n "
        . "See your around!\r\n"; //email body, \r\n is new line break the first %s is donors name the second is their expiration date

/*
 * Date formats
 * see http://php.net/manual/en/function.date.php
 */
$date_format['front_end'] = 'l F j Y';
$date_format['back_end'] = 'n/j/Y';
$date_format['log'] = 'm/j/Y g:i:s A';

/*
 * Miscellaneous
 */

define('FORCE_IPN', false); //This will force non-critical errors to keep the IPN script going when a donation is recieved. That includes unconfirmed steamids and other failures.
define('CCC', false); //https://forums.alliedmods.net/showpost.php?p=1738314&postcount=56#MySQLModule
date_default_timezone_set('America/New_York'); //http://php.net/manual/en/timezones.php
define('cache_time', '15'); //days to resolve cache for information from steam, mainly the avatar image, and display name.
define('PLAYER_TRACKER', false); //McKay Analytics plugin integration // https://forums.alliedmods.net/showthread.php?t=230832 tables must be in the donations database.
define('API_KEY', 'YOURAPIKEY'); //http://steamcommunity.com/dev/apikey
define("DEFAULT_LANGUAGE", "en-us"); // name of file in translation folder. dont add .json
$availableLanguages = array('en-us' => 'English', '1337' => '1337 5pEek', 'pt-br' => 'Portuguese (Brazil)', 'es-mx' => 'Spanish (Mexico)'); //set friendly display names here http://msdn.microsoft.com/en-us/library/ms533052(vs.85).aspx
define("STATS", true); //enable stats reporting




/*
 * dont edit this stuff
 */
if (SB_DB) {
    define('SB_HOST', SB_SV_HOST);
    define('SB_USER', SB_SV_USER);
    define('SB_PASS', SB_SV_PASS);
} else {
    define('SB_HOST', DB_HOST);
    define('SB_USER', DB_USER);
    define('SB_PASS', DB_PASS);
}
define('VERSION', '3.1.0'); //unused
define('ABSDIR', substr(__DIR__, 0, stripos(__DIR__, 'includes')));
define('DEBUG', false);
set_exception_handler('exception_handler');

require_once ABSDIR . 'includes/version.php';

function exception_handler($ex) {
    require_once ABSDIR . "includes/LoggerClass.php";
    $log = new log;
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    echo "<div class='alert alert-danger' role='alert'>We ran into an unhandled exception. Exiting, " . $ex->getMessage() . "</div>";
    die();
}
