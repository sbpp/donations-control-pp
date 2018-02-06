<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}

class tools {

    public function convertToHoursMins($time, $format = '%d:%d') {
        settype($time, 'integer');
        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = $time % 60;
        return sprintf($format, $hours, $minutes);
    }

    public function dollaBillz($data) {
        if (strpos($data, "$") === 0) {
            return substr($data, 1);
        } else {
            return $data;
        }
    }

    public function cleanUser($username) {
        $username = $this->cleanInput($username);
        return $username;
    }

    public function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    public function randomPassword($length) {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    public function checkOnline($host) {
        if ($socket = @ fsockopen($host, 80, $errno, $errstr, 30)) {
            fclose($socket);
            return true;
        } else {
            return false;
        }
    }

}
