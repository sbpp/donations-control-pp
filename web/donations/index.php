<?php
define('NineteenEleven', TRUE);
require_once 'includes/config.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
echo "<!--Donations Control v" . VERSION_NEW . " written by NineteenEleven @ 1911.expert-->";
try {
    $sb = new sb;
    $gotDb = true;
} catch (Exception $ex) {
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
?>
<!DOCTYPE html>
<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <!-- Javascript to allow gifting -->
        <script type="text/javascript">
            function gift() {
                document.getElementById('steamid-box').style.display = 'block';
                document.getElementById('id-field').value = '';
                document.getElementById('id-field').placeholder = 'Enter SteamID here';
                document.getElementById('userid').style.display = 'none';
                document.getElementById('infobox').style.display = 'block';
            }
            ;
            function submitDC() {
                document.getElementById('donateForm').submit();
            };
            function changeAmount(dcAmt){
                document.getElementById('donateAmt').value = dcAmt;
            };
        </script>
    </head>
    <body id='original'>
        <style type="text/css">#infobox{font-size: 12px;}</style>
    <center>
        <form action="donate.php" target="blank" id="donateForm" method="post">
            <input type="image" src="images/btn_donateCC_LG.gif" form="donateForm" onclick='submitDC();' />
            <input type="hidden" name="donateForm" value="submit" id='submitBtn' />
            <p>Amount: $<input type="text" name="amount" size="5" class="inputbox" value="5" required id='donateAmt'></p>                
            <?php
                $groups = $sb->listGroups();
                if ($groups !== false) {
                    $i = 0;
                    foreach ($groups as $group) {
			
                        echo "<div class='group' style='display:inline-block;'><input type='radio' name='tier' ";
                        if ($i == 0) {
                            echo "checked";
                        }
                         echo " required value='" . $group['id'] . "' onclick='changeAmount(\"".$group['minimum']."\");' />" . $group['name'] . " </div>";
			$i++;
                    }
                } else {
                    echo "No Groups are set up yet!";
                }

                if ($found_user) {
                    print("<div id='steamid-box' style='display:none;' ><label for='steamid_user'>Steam ID:<br /></label>");
                    print("<input type='text' name='steamid_user' required='true' id='id-field'  value='$steam_id' ></div>");
                    print("<div id='userid'>Welcome back $playername <br />");
                    print("<img src='$avatarmedium' style='border:1px solid black;border-radius:5px;' /><br />");
                    print("<span style='cursor:pointer;' onclick='gift();'> Donate for someone else</span></div>");
                    print("<div id='infobox' style='display:none;'>");
                    print("<p>Acceptable formats:<br />STEAM_0:0:0000000<br />steamcommunity.com/profiles/1234567891011<br />steamcommunity.com/id/{name} or {name}<br /></p>");
                    print("</div>");
                } else {
                    print("<br /><label for='paypaloption1'>Steam ID:<br /></label><input type='text' id='paypaloption1' name='steamid_user' required='true' id='id-box' placeholder='Please enter your SteamID' required='true' size='30'></p>");
                    print("<div id='infobox'>");
                    print("<p>Acceptable formats:<br />STEAM_0:0:0000000<br />steamcommunity.com/profiles/1234567891011<br />steamcommunity.com/id/{name} or {name}<br /></p>");
                    print("</div>");
                }
                ?>
        </form>
    </center>
</body>
</html>
