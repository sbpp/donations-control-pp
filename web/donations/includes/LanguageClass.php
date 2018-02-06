<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}
if (!defined('ABSDIR')) {
    die('no directory set.');
}
class language {

    public function __construct() {
        $this->dir = ABSDIR . "translations/";
    }

    public function getLang($lang) {
        $json = file_get_contents($this->dir . $lang . '.json');
        return json_decode($json);
    }

    public function listLang() {
        $scan = scandir($this->dir);
        $i = 0;
        foreach ($scan as $lang) {
            if (!is_dir($lang)) {
                $list[$i] = str_replace(".json", "", $lang);
            }
            $i++;
        }
        return $list;
    }

}
