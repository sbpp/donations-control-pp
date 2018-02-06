<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}
if (!defined('ABSDIR')) {
    die('no directory set.');
}

class log {

    public function __construct() {
        $this->dir = ABSDIR . "admin/logs/";

        if (!file_exists($this->dir)) {
            try {
                @mkdir($this->dir, 0755, true);
                $htaccess = @fopen($this->dir . '.htaccess', 'a');
                @fwrite($htaccess, "Options -Indexes");
                @fclose($htaccess);
            } catch (Exception $ex) {
                die($ex->getMessage());
            }
        }
    }

    public function logError($data, $script = false, $line = false) {
        if (!$script) {
            $script = $_SERVER['SCRIPT_NAME'];
        } else {
            $script = $script . " line:" . $line;
        }
        $error = fopen($this->dir . 'error.log', 'a');
        fwrite($error, date($GLOBALS['date_format']['log']) . "|" . $data . "|" . $script . "\r\n");
        fclose($error);
        if (STATS) {
            $this->stats("ER|" . $script . "-" . $data);
        }
    }

    public function logAction($data) {
        $action = fopen($this->dir . 'action.log', 'a');
        fwrite($action, date($GLOBALS['date_format']['log']) . "|" . $data . "|" . $_SERVER['SCRIPT_NAME'] . "\r\n");
        fclose($action);
    }

    public function logBot($data) {
        $bot = fopen($this->dir . 'bot.log', 'a');
        fwrite($bot, date($GLOBALS['date_format']['log']) . ": " . $data . "\r\n");
        fclose($bot);
    }

    public function getLog($log) {
        return file($this->dir . $log);
    }

    public function stats($data) {
        if (STATS && !DEBUG) {
            ini_set('default_socket_timeout', 10);
            $data = urlencode(sha1($_SERVER['SERVER_ADDR']) . "|" . date('U') . "|" . $data);
            @file_get_contents('http://nineteeneleven.info/stats/get.php?data=' . $data);
        }
    }

}
