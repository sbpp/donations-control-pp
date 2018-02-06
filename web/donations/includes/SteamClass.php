<?php

if (!defined('NineteenEleven')) {
    die('Direct access not premitted');
}

class SteamQuery {

    private function getJson($url) {
        // make cache directory if it doesnt exist
        if (!file_exists(ABSDIR . DIRECTORY_SEPARATOR . 'cache')) {
            mkdir(ABSDIR . DIRECTORY_SEPARATOR . 'cache', 0755, true);
        }
        // cache files are created like cache/abcdef123456...
        $cacheFile = ABSDIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . md5($url);

        if (file_exists($cacheFile)) {
            $fh = fopen($cacheFile, 'r');
            $cacheTime = trim(fgets($fh));

            // if data was cached recently, return cached data
            if ($cacheTime > strtotime('-' . cache_time . ' days')) {
                return fread($fh, filesize($cacheFile));
            }

            // else delete cache file
            fclose($fh);
            unlink($cacheFile);
        }

        $json = file_get_contents($url);
        $fh = fopen($cacheFile, 'w');
        fwrite($fh, time() . "\n");
        fwrite($fh, $json);
        fclose($fh);

        return $json;
    }

    /**
     *
     * @return sets $this->playerSummaries from Steam, or exception if error
     */
    protected function GetPlayerSummaries() {
        $API_link = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . API_KEY . "&format=json&steamids=" . $this->steamId64;
        //$json = file_get_contents($API_link);
        $json = $this->getJson($API_link);
        $this->playerSummaries = json_decode($json);
        //var_dump($this->playerSummaries);
        if (empty($this->playerSummaries->response->players[0])) {
            throw new Exception('Unable verify player with Steam.');
        }
        return $this->playerSummaries;
    }

    protected function ConvertVanityURL($playerName) {
        $API_link = "http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=" . API_KEY . "&format=json&vanityurl=" . $playerName;
        if (!$json = file_get_contents($API_link)) {
            return false;
        }
        $query = json_decode($json);
        if ($query->response->success == 1) {
            $ID64 = $query->response->steamid;
            $this->steamId64 = $ID64;
        } else {
            throw new Exception('Unable to resolve vanity url');
        }
    }

}

class SteamIDConvert extends SteamQuery {

    function __construct($id_input) {
        $this->id_input = $id_input;
    }

    //Get 76561197973578969 from STEAM_0:1:6656620
    protected function IDto64() {
        $iServer = "0";
        $iAuthID = "0";

        $szTmp = strtok($this->steam_id, ":");

        while (($szTmp = strtok(":")) !== false) {
            $szTmp2 = strtok(":");
            if ($szTmp2 !== false) {
                $iServer = $szTmp;
                $iAuthID = $szTmp2;
            }
        }
        if ($iAuthID == "0") {
            throw new Exception('Error converting ID to 64 bit.');
        }

        $steamId64 = bcmul($iAuthID, "2");
        $steamId64 = bcadd($steamId64, bcadd("76561197960265728", $iServer));
        if (strpos($steamId64, ".")) {
            $steamId64 = strstr($steamId64, '.', true);
        }
        $this->steamId64 = $steamId64;
    }

    ////Get STEAM_0:1:6656620 from 76561197973578969
    protected function IDfrom64() {
        $iServer = "1";
        if (bcmod($this->steamId64, "2") == "0") {
            $iServer = "0";
        }
        $steamId64 = bcsub($this->steamId64, $iServer);
        if (bccomp("76561197960265728", $steamId64) == -1) {
            $steamId64 = bcsub($steamId64, "76561197960265728");
        }
        $steamId64 = bcdiv($steamId64, "2");
        if (strpos($steamId64, ".")) {
            $steamId64 = strstr($steamId64, '.', true);
        }
        $this->steam_id = ("STEAM_0:" . $iServer . ":" . $steamId64);
    }

    protected function getSteamLink() {
        $this->steam_link = "http://steamcommunity.com/profiles/" . $this->steamId64;
    }

    protected function checkId() {
        $this->GetPlayerSummaries();

        if ($this->playerSummaries === false) {
            throw new Exception('Unable to fetch data from steam.');
        }
    }

    public function disableIdVerification() {
        $this->verifyId = false;
        return $this;
    }

    //U:1:13313241
    //STEAM_0:1:6656620

    protected function steam3() {
        $id = explode(':', $this->steam_id);
        $id3 = ($id[2] * 2) + $id[1];
        $this->steam3id = '[U:1:' . $id3 . ']';
    }

//[U:1:C] --> is C odd? if yes then A = 1, else A = 0.  B = floor(C / 2) --> STEAM_0:A:B
    protected function steam2() {
        $steam3 = trim($this->steam3id, "[]");
        $s3arr = explode(':', $steam3);
        if ($s3arr[2] % 2 == 1) { //odd
            $this->steam_id = "STEAM_0:1:" . floor($s3arr[2] / 2);
        } elseif ($s3arr[2] % 2 == 0) { //even
            $this->steam_id = "STEAM_0:0:" . floor($s3arr[2] / 2);
        } else {
            throw new Exception('Invalid Steam3 id');
        }
    }

    public function SteamIDCheck() {
        if (isset($this->verifyId)) {
            $this->verifyId = false;
        } else {
            $this->verifyId = true;
        }
        $this->id_input = rtrim($this->id_input, "/"); // remove trailing backslash

        if (preg_match("/^\[?U:[0-9]/i", $this->id_input)) {
            if (strstr($this->id_input, '[') === false) {
                $this->id_input = '[' . $this->id_input;
            }
            if (strstr($this->id_input, ']') === false) {
                $this->id_input = $this->id_input . ']';
            }
            $this->steam3id = $this->id_input;
            $this->steam2();
            $this->IDto64();
            $this->getSteamLink($this->steamId64);
            //Look for STEAM_0:1:6656620 variation
        } elseif (preg_match("/^STEAM_/i", $this->id_input)) {
            $this->steam_id = strtoupper($this->id_input);
            $this->IDto64();
            $this->getSteamLink();
            //look for just steam id 64, 76561197973578969
        } elseif (preg_match("/^[0-9]/i", $this->id_input)) {
            $this->steamId64 = $this->id_input;
            $this->getSteamLink();
            $this->IDfrom64();
        } else {

            if (preg_match('#^http(s)?://#', $this->id_input)) {
                $this->id_input = preg_replace('#^http(s)?://#', '', $this->id_input);
            }

            //Look for characters
            if (preg_match("/^[a-z]/i", $this->id_input)) {

                //Find steamcommunity link
                if (preg_match("/(steamcommunity.com)+/i", $this->id_input)) {

                    //look for 64 url http://steamcommunity.com/profiles/76561197973578969
                    if (preg_match("/(\/profiles\/)+/i", $this->id_input)) {
                        $i = preg_split("/\//i", $this->id_input);
                        $size = count($i) - 1;
                        $this->steamId64 = $i[$size];
                        $this->getSteamLink();
                        $this->IDfrom64();
                    } elseif (preg_match("/(\/id\/)+/i", $this->id_input)) {

                        //look for vanity url http://steamcommunity.com/id/nineteeneleven
                        $i = preg_split("/\//i", $this->id_input);
                        $size = count($i) - 1;
                        $this->ConvertVanityURL($i[$size]);
                        $this->IDfrom64();
                        $this->getSteamLink();
                    } else {
                        throw new Exception('Invalid Steam id');
                    }
                } else {
                    //check if its just vanity url, nineteeneleven

                    $this->ConvertVanityURL($this->id_input);
                    $this->IDfrom64();
                    $this->getSteamLink();
                    if ($this->steam_id == "STEAM_0:0:0") {
                        throw new Exception('Unable to resolve vanity url');
                    }
                }
            } else {
                //found nothing
                throw new Exception('Invalid Steam id');
            }
        }
        if (!isset($this->steam3id)) {
            $this->steam3($this->steam_id);
        }
        if ($this->verifyId) {
            $this->checkId();
        }


        return $this;
    }

    public function fillCache() {
        $timestamp = date('U');

        require_once ABSDIR . 'includes/SourceBansClass.php';
        try {
            $sb = new sb;

            $stmt = $sb->ddb->prepare("SELECT `id` FROM `cache` WHERE steamid = ? ORDER BY id DESC LIMIT 0,1;");
            $stmt->bindParam(1, $this->steam_id);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $id = $stmt->fetch(PDO::FETCH_ASSOC);
                $id = $id['id'];

                $cache = $sb->ddb->prepare("UPDATE `cache` SET `steamid` = :steamid, `avatar` =:avatar, `avatarmedium` = :avatarmedium, `avatarfull` = :avatarfull,"
                        . " `personaname` = :personaname, `timestamp` = :timestamp, `steamid64` =  :steamid86, `steam_link` = :steam_link WHERE `id`=:id;");



                $cache->execute(array(
                    $this->steam_id,
                    $this->playerSummaries->response->players[0]->avatar,
                    $this->playerSummaries->response->players[0]->avatarmedium,
                    $this->playerSummaries->response->players[0]->avatarfull,
                    $this->playerSummaries->response->players[0]->personaname,
                    $timestamp,
                    $this->steamId64,
                    $this->steam_link,
                    $id
                ));
            } else {

                $cache = $sb->ddb->prepare("INSERT INTO `cache` (steamid,avatar,avatarmedium,avatarfull,personaname,timestamp,steamid64,steam_link)
                                        VALUES (:steamid,:avatar,:avatarmedium,:avatarfull,:personaname,:timestamp,:steamid64,:steam_link);");

                $cache->execute(array(
                    $this->steam_id,
                    $this->playerSummaries->response->players[0]->avatar,
                    $this->playerSummaries->response->players[0]->avatarmedium,
                    $this->playerSummaries->response->players[0]->avatarfull,
                    $this->playerSummaries->response->players[0]->personaname,
                    $timestamp,
                    $this->steamId64,
                    $this->steam_link
                ));
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
        return $this;
    }

}
