<?php

//This script will update your admins table and bans table to the new account id format.
//Feel free to edit the script for other purposes. Just please share it so everyone can use it!
//If you want email it to me and ill host it.
//To use this script fill in your sourcebans database information below and run this file from either
//a web browser or command line.
//please make a back up of your database. I am not responsible if things go wrong!
define('SB_HOST', 'localhost');        //set MySQL host
define('SB_USER', 'dev');             //MySQL username
define('SB_PASS', 'password2strong');         //MySQL password
define('SB_PREFIX', 'sb_');  //sourcebans prefix
define('SOURCEBANS_DB', 'sourcebans'); //sourcebans database.




try {
    $db = new PDO('mysql:host=' . SB_HOST . ';dbname=' . SOURCEBANS_DB . ';charset=utf8', SB_USER, SB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Exception $ex) {
    echo "unable to connect to database";
    die();
}

$stmt = $db->prepare("UPDATE " . SB_PREFIX . "admins SET `authid` = ? WHERE aid =?");

foreach ($db->query("SELECT authid,aid FROM " . SB_PREFIX . "admins WHERE 1;") as $steam2) {
    try {
        $steam3 = steam3($steam2['authid']);
        $stmt->bindParam(1, $steam3);
        $stmt->bindParam(2, $steam2['aid']);
        $stmt->execute();

        printf("Updating %s to %s<br />\n", $steam2['authid'], $steam3);
    } catch (Exception $ex) {
        printf('Updating %s failed<br />\n', $steam2['authid']);
    }
}

unset($stmt);

$stmt = $db->prepare("UPDATE " . SB_PREFIX . "bans SET `authid` = ? WHERE bid =?");

foreach ($db->query("SELECT authid,bid FROM " . SB_PREFIX . "bans WHERE 1;") as $steam2) {
    try {
        if (strpos($steam2['authid'], '[U:') === false) {
            $steam3 = steam3($steam2['authid']);
            $stmt->bindParam(1, $steam3);
            $stmt->bindParam(2, $steam2['bid']);
            $stmt->execute();

            printf("Updating %s to %s<br />\n", $steam2['authid'], $steam3);
        } else {
            printf('skipping %s<br />\n', $steam2['authid']);
        }
    } catch (Exception $ex) {
        printf('Updating %s failed', $steam2['authid']);
    }
}

function steam3($steam2) {
    $id = explode(':', $steam2);
    $id3 = ($id[2] * 2) + $id[1];
    return '[U:1:' . $id3 . ']';
}
