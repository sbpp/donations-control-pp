<script type="text/javascript">
    function donate(found) {
        document.getElementById('donate-img').style.display = 'none';
        document.getElementById('donateModule').style.display = 'block';
        if (found == '0') {
            document.getElementById('paypaloption1').style.display = 'block';
        }
        ;
    }
    ;
    function gift() {
        document.getElementById('steamid-box').style.display = 'block';
        document.getElementById('id-field').value = '';
        document.getElementById('id-field').placeholder = 'Enter SteamID here';
        document.getElementById('userid').style.display = 'none';
        document.getElementById('infobox').style.display = 'block';
    }
    function closeDonation() {
        document.getElementById('donateModule').style.display = 'none';
        document.getElementById('donate-img').style.display = 'block';
    }
</script>


<?php
if (!defined("NineteenEleven")) {
    define('NineteenEleven', TRUE);
}
echo "<!--Donations Control v" . VERSION_NEW . " written by NineteenEleven @ 1911.expert-->";
require_once 'includes/config.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
$donateLink = '/donations/donate.php';
try {
    $sb = new sb;
    $gotDb = true;
} catch (Exception $ex) {
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    $gotDb = false;
}

$found_user = false;
$timestamp = date('U');

if (PLAYER_TRACKER && $gotDb) {
    $userip = $_SERVER['REMOTE_ADDR'];
    try {
        $stmt = $sb->ddb->prepare("SELECT * FROM `player_analytics` WHERE ip=? ORDER BY id DESC LIMIT 0,1;");
        $stmt->bindParam(1, $userip, PDO::PARAM_STR);
        $stmt->execute();
    } catch (Exception $ex) {

    }

    if ($stmt->rowCount() == 1) {

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $playername = $row['name'];
        $steam_id = $row['auth'];

        $found_user = true;

        //$cacheReturn = $mysqliD->query("SELECT * FROM `cache` WHERE steamid ='" . $steamid . "';");

        $stmt = $sb->ddb->query("SELECT * FROM `cache` WHERE steamid ='$steam_id';");
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($cache)) {

            $cacheExpire = (cache_time * 86400) + $cache['timestamp'];

            if ($timestamp < $cacheExpire) {

                //cache still valid

                $avatarmedium = $cache['avatarmedium'];
            } else {
                //cache expired, updating
                try {
                    $sb->ddb->exec("DELETE FROM `cache` WHERE steamid = '" . $cache['steamid'] . "';");
                    require_once ABSDIR . 'includes/SteamClass.php';
                    $steam = new SteamIDConvert($steam_id);
                    $steam->SteamIDCheck()->fillCache();
                    $playername = $steam->playerSummaries->response->players[0]->personaname;
                    $avatarmedium = $steam->playerSummaries->response->players[0]->avatarmedium;
                    $steam_id = $steam->steam_id;
                } catch (Exception $ex) {
                    $found_user = false;
                }
            }
        } else {
            //nothing in cache, getting stuff
            try {
                $sb->ddb->exec("DELETE FROM `cache` WHERE steamid = '" . $cache['steamid'] . "';");
                require_once ABSDIR . 'includes/SteamClass.php';
                $steam = new SteamIDConvert($steam_id);
                $steam->SteamIDCheck()->fillCache();
                $playername = $steam->playerSummaries->response->players[0]->personaname;
                $avatarmedium = $steam->playerSummaries->response->players[0]->avatarmedium;
                $steam_id = $steam->steam_id;
            } catch (Exception $ex) {
                $found_user = false;
            }
        }
    } else {
        $found_user = false;
    }
}

print("<div id='donate-image' style='display:block;padding-right:20px;float:right;margin-left:35%;'>");
if ($found_user) {
    print("<img src=\"https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif\" id='donate-img' onclick=\"donate('1')\">");
} else {
    print("<img src=\"https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif\" id='donate-img' onclick=\"donate('0')\">");
}
print("</div>");

print("<div id='donateModule' style='display:none;position:absolute;z-index: 999;padding:20px;left:40%;top:400px;width:400px;border:5px solid #333; border-radius:10px;background-color:#242424;'>");
print("<center>");
print("<input type=\"image\" src=\"https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif\" form=\"donate_form\" />");
print("<form action=\"$donateLink\" target=\"blank\" id=\"donate_form\" method=\"post\">");
print("<p>Amount: $<input type=\"text\" name=\"amount\" size=\"2\" class=\"inputbox\" value=\"5\" required=\"true\"></p>");


$groups = $sb->listGroups();

if ($groups !== false) {
    $i=0;
    foreach ($groups as $group) {
        echo "<div class='group' style='display:inline-block;'><input type='radio' name='tier' "; 
        if($i ==0){
            echo "checked";
        }
        echo " required value='" . $group['id'] . "' />" . $group['name'] . " </div>";
        $i++;
    }
} else {
    echo "No Groups are set up yet!";
}


print("<br /><div id='donate-right' style='display:inline-block'>");
if ($found_user) {
    print("<div id='steamid-box' style='display:none;'><label for='steamid_user'>Steam ID:<br /></label>");
    print("<input type=\"text\" name=\"steamid_user\" required=\"true\" id=\"id-field\"  value=\"{$steamid}\" style='width:180px' >");
    print("</div>");
    print("<div id=\"userid\">Welcome back {$playername} <br />");
    print("<img src='{$avatarmedium}' class='avatarD' style ='border:1px solid black;border-radius:5px;height:55px;width:auto;'/><br />");
    print("<a href='#' onclick=\"gift();\"> Donate for someone else </a>");
    print("</div>");

    print("<div id='infobox' style='display:none;font-size:10px;line-height:1em;'>");
    print("Acceptable formats:<br />STEAM_0:0:0000000<br />steamcommunity.com/profiles/1234567891011<br />steamcommunity.com/id/{name} or {name}<br />");
    print("</div>");
} else {


    print("<div id='infobox' style='font-size:10px;line-height:1em;' >");
    print("<input type=\"text\" id=\"paypaloption1\" name=\"steamid_user\" required=\"true\" placeholder=\"Please enter your SteamID\" required=\"true\" size=\"30\" style='display:block'></p>");
    print("Acceptable formats:<br />STEAM_0:0:0000000<br />steamcommunity.com/profiles/1234567891011<br />steamcommunity.com/id/{name} or {name}<br />");
    print("</div>");
}

print("</div>");
print("</form>");
print("<a href='#' onclick='closeDonation()'>Close</a>");
print("</center></div>");
?>