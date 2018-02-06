<?php
session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}
$session = $_SESSION;
session_write_close();

define('NineteenEleven', TRUE);
define('adminPage', TRUE);
require_once '../../../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/LanguageClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
$_SESSION['ABSDIR'] = ABSDIR;
$language = new language;


$log = new log;
try {
    $sb = new sb;
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

$groups = $sb->listGroups();

if (isset($_POST['langSelect'])) {
    $_SESSION['language'] = $_POST['langSelect'];
}

if (isset($_SESSION['language'])) {
    $lang = $language->getLang($_SESSION['language']);
} else {
    $lang = $language->getLang(DEFAULT_LANGUAGE);
}

$total = 0;
$totalC = 0;


$search = $_POST['search'];
$table = $_POST['table'];
$show_expired = $_POST['expired'];

try {

    if ($search) {
        $search = "%" . $search . "%";

        $stmt = $sb->ddb->prepare("SELECT * FROM `donors` WHERE username LIKE ? OR steam_id LIKE ? OR email LIKE ?;");

        $stmt->bindParam(1, $search, PDO::PARAM_STR);
        $stmt->bindParam(2, $search, PDO::PARAM_STR);
        $stmt->bindParam(3, $search, PDO::PARAM_STR);
        $stmt->execute();
    } elseif ($show_expired) {
        $stmt = $sb->ddb->query("SELECT * FROM donors ORDER BY `expiration_date`;");
    } else {
        $stmt = $sb->ddb->query("SELECT * FROM donors  WHERE activated != '2' ORDER BY`expiration_date`;");
    }
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
}


if (PLAYER_TRACKER) {
    try {
        $PTstmt = $sb->ddb->prepare("SELECT ip,country FROM `player_analytics` WHERE auth=:steam_id ORDER BY id DESC LIMIT 0,1;");
    } catch (Exception $ex) {
        echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    }
}

if ($table) {
    echo "<div class='panel panel-default'>";
    echo "<div class='panel-body'>";
    echo "<table class='table'>";
    echo "<tr><th>" . $lang->admin[0]->steamname . "</th><th>" . $lang->admin[0]->info . "</th><th>" . $lang->admin[0]->sud . "</th><th>" . $lang->admin[0]->email . "</th><th>" . $lang->admin[0]->rd . "</th><th>" . $lang->admin[0]->current . "</th><th>" . $lang->admin[0]->total . "</th><th>" . $lang->admin[0]->ed . "</th><th>" . $lang->admin[0]->tier . "</th><th>" . $lang->admin[0]->notes . "</th></tr>";
} else {
    echo "<div class='list-group user-list'>";
}


//loop through rows and print values to the table
$i = 1;
while ($db_field = $stmt->fetch(PDO::FETCH_ASSOC)) {

    if ($db_field['renewal_date'] == "0") {
        $renewal_date = "None";
    } else {
        $renewal_date = date($date_format['back_end'], $db_field['renewal_date']);
    }

    if (PLAYER_TRACKER) {
        $PTstmt->bindParam(1, $db_field['steam_id']);
        $PTstmt->execute();
        $tracker = $PTstmt->fetch(PDO::FETCH_ASSOC);
        $PTstmt->closeCursor();
    }

    $totalC = ($totalC + $db_field['current_amount']);
    $total = ($total + $db_field['total_amount']);
    //change color of expiration date, based on status

    $days_left = floor(($db_field['expiration_date'] - date('U')) / 86400);


    switch ($db_field['activated']) {
        case 1:
            $expiration_date = "<div class='inline green'>" . date($date_format['back_end'], $db_field['expiration_date']) . "</div>";
            break;
        case 2:
            $expiration_date = "<div class='inline red'>" . date($date_format['back_end'], $db_field['expiration_date']) . "</div>";
            break;
        default:
            $expiration_date = "<div class='inline yellow'>" . date($date_format['back_end'], $db_field['expiration_date']) . "</div>";
            break;
    }

    if (empty($db_field['email'])) {
        $db_field['email'] = 'No Email';
    }
    if ($table) {
        echo "<tr><td><span class='badge inline' title='Days before expire'>$days_left</span><a href='" . $db_field['steam_link'] . "' target='_blank'>" . $db_field['username'] . "</a></td>";
        if (PLAYER_TRACKER) {

            echo "<td class='click'><div class='steamid'> " . $db_field['steam_id'] . "</div>"
            . " <div class='ptInfo' > <a href='http://www.geoiptool.com/en/?IP=" . $tracker['ip'] . "' target='blank'>" . $tracker['ip'] . "</a>" . "(" . $tracker['country'] . ")</div></td>";
        } else {
            echo "<td>" . $db_field['steam_id'] . "</td> ";
        }
        echo "<td>" . date($date_format['back_end'], $db_field['sign_up_date'])
        . "</td><td>" . "<a href='mailto:" . $db_field['email'] . "' target='_top'>" . $db_field['email'] . "</a>"
        . "</td><td>" . $renewal_date
        . "</td><td>$" . $db_field['current_amount']
        . "</td><td>$" . $db_field['total_amount']
        . "</td><td>" . $expiration_date;


        foreach ($groups as $group) { //getting $groups from show_donations.php
            if ($group['id'] == $db_field['tier']) {
                echo "</td><td>" . $group['name'];
                break;
            }
        }

        echo "</td><td>" . $db_field['notes']
        . "</td><td><span class='glyphicon glyphicon-cog inline pull-right pointer' onclick='showEdit(\"editForm$i\")'></span></li>"
        . "</td>";
        echo "</tr>";
    } else {
        echo "<div class='user' id='" . $db_field['user_id'] . "'><ul class='list-group'>";

        echo "<li class='list-group-item-heading infName'>";
        echo "<span class='badge inline' title='Days before expire'>$days_left</span>";
        if (!empty($db_field['notes'])) {
            echo "<span class='glyphicon glyphicon-asterisk inline' title='" . $db_field['notes'] . "'></span>";
        }

        echo "<a href='" . $db_field['steam_link'] . "' class='inline'target='_blank'>" . substr($db_field['username'], 0, 25) . "</a>";
        echo "<span class='glyphicon glyphicon-cog inline pull-right pointer' onclick='showEdit(\"editForm$i\")'></span></li>";

        if (PLAYER_TRACKER) {

            printf("<li class='steamid list-group-item-text user-info infSid'>%s<br /><a href='http://www.geoiptool.com/en/?IP=%s' target='blank'>%s</a>(%s)</li>", $db_field['steam_id'], $tracker['ip'], $tracker['ip'], $tracker['country']);
        } else {
            echo "<li class='list-group-item-text user-info infSid'>" . $db_field['steam_id'] . "</li> ";
        }
        echo "<li class='list-group-item-text user-info infSignD'>" . $lang->admin[0]->sud . ': ' . date($date_format['back_end'], $db_field['sign_up_date']) . "</li>"
        . "<li class='list-group-item-text user-info infEmail'>" . "<a href='mailto:" . $db_field['email'] . "' target='_top'>" . $db_field['email'] . "</a></li>"
        . "<li class='list-group-item-text user-info infRenew'>" . $lang->admin[0]->rd . ': ' . $renewal_date . "</li> "
        . "<li class='list-group-item-text user-info inFcurAmt'>" . $lang->admin[0]->current . ': $' . $db_field['current_amount'] . "</li> "
        . "<li class='list-group-item-text user-info infTotal'>" . $lang->admin[0]->total . ': $' . $db_field['total_amount'] . "</li> "
        . "<li class='list-group-item-text user-info infExpD'>" . $lang->admin[0]->ed . ': ' . $expiration_date . "</li>";

        foreach ($groups as $group) { //getting $groups from show_donations.php
            if ($group['id'] == $db_field['tier']) {
                echo "<li class='list-group-item-text user-info infGrpNnm'>" . $lang->admin[0]->tier . ': ' . $group['name'] . "</li> ";
                break;
            }
        }
        echo "</li>";
        echo "</ul></div>";
    }

    /*
     * Begin edit user stuff
     */

    echo "<div class='panel panel-default editUser gradientPanel' id='editForm$i' style='display:none;'>";
    echo "<div class='panel-heading gradientPHeading'><h3>" . $db_field['username'] . "</h3></div>";
    echo "<div class='panel-body'> ";


    echo "<form action='pages/ajax/edit_user.php' method='POST' id='edit_user_form$i'>";
    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->steamname . "</span>";
    echo "<input name='username' class='form-control' value='" . $db_field['username'] . "' type='text' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->steamid . "</span>";
    echo "<input name='steam_id' class='form-control' readonly value='" . $db_field['steam_id'] . "' type='text' style='cursor:context-menu;' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->steamlink . "</span>";
    echo "<input type='text' name='steam_link' class='form-control' value='" . $db_field['steam_link'] . "'/>";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->email . "</span>";
    echo "<input name='email' class='form-control' value='" . $db_field['email'] . "' type='email' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->sud . " <span class='glyphicon glyphicon-calendar'></span></span>";
    echo "<input name='sign_up_date' class='form-control date' value='" . date('n/j/Y', $db_field['sign_up_date']) . "' type='text' />";
    echo "</div>";




    $renewal_date = $db_field['renewal_date'];
    if ($renewal_date == 0) {
        $renewal_date = "Never";
    } else {
        $renewal_date = date('n/j/Y', $db_field['renewal_date']);
    }


    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->rd . " <span class='glyphicon glyphicon-calendar'></span></span>";
    echo "<input name='renewal_date' class='form-control date' value='" . $renewal_date . "' type='text' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->ed . " <span class='glyphicon glyphicon-calendar'></span></span>";
    echo "<input name='expiration_date' class='form-control date' value='" . date('n/j/Y', $db_field['expiration_date']) . "' type='text' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->current . "</span>";
    echo "<input name='current_amount' class='form-control' value='" . $db_field['current_amount'] . "' type='text' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->total . "</span>";
    echo "<input name='total_amount' class='form-control' value='" . $db_field['total_amount'] . "' type='text' />";
    echo "</div>";

    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>" . $lang->admin[0]->notes . "</span>";
    echo "<textarea name='notes' class='form-control'>" . $db_field['notes'] . "</textarea>";
    echo "</div>";

    $activated = $db_field['activated'];
    $tier = $db_field['tier'];
    echo "<div class='panel panel-default panel-small inline'>";
    echo "<h3 class='panel-title'>" . $lang->admin[0]->status . "</h3>";
    echo "<div class='panel-body'> ";
    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>";
    echo "<input type='radio' name='activated' value='1'";
    if ($activated === '1') {
        echo "checked />";
    } else {
        echo "/>";
    }
    echo "</span>";
    echo "<input type='text' readonly style='cursor:context-menu;' value='";
    if ($activated === '1') {
        echo $lang->admin[0]->perksactivated;
    } else {
        echo $lang->admin[0]->addperks;
    }
    echo "' class='form-control'>";
    echo "</div>";


    echo "<div class='input-group'>";
    echo "<span class='input-group-addon'>";
    echo "<input type='radio' name='activated' value='2'";
    if ($activated === '2') {
        echo "checked />";
    } else {
        echo "/>";
    }
    echo "</span>";
    echo "<input type='text' readonly style='cursor:context-menu;' value='";
    if ($activated === '2') {
        echo $lang->admin[0]->perksoff;
    } else {
        echo $lang->admin[0]->noperks;
    }
    echo "' class='form-control'>";
    echo "</div>";
    echo "</div>";
    echo "</div>";


    echo "<div class='panel panel-default panel-small inline'>";
    echo "<h3 class='panel-title'>" . $lang->admin[0]->tier . "</h3>";
    echo "<div class='panel-body'>";
    foreach ($groups as $group) { //getting $groups from show_donations.php
        echo "<div class='input-group'>";
        echo "<span class='input-group-addon'>";

        if ($group['id'] == $tier) {
            echo "<input type='radio' name='tier' value='" . $group['id'] . "' id='tierRadio' checked />";
        } else {
            echo "<input type='radio' name='tier' value='" . $group['id'] . "' id='tierRadio' />";
        }
        echo "</span>";
        echo "<input type='text' readonly style='cursor:context-menu;' value='" . $group['name'] . "' class='form-control'>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";


    echo "<input type='hidden' name='user_id' value='" . $db_field['user_id'] . "'>";
    echo "<input type='hidden' name='edit_user_form' value='1'>";

    echo "</form>";
    echo "<div class='ajax-loader' style='display:none;'><img src='../images/ajax-loader.gif' ></div>";
    echo "<div class='buttons'>";
    echo "<input type='submit' value='Edit User' class='btn btn-default' form='edit_user_form$i'/>";
    printf("<input type='button' class='btn btn-default' onclick='deleteConfirm(\"%s\",\"%s\");' value='Delete %s' />", $db_field['steam_id'], $db_field['user_id'], $db_field['username']);
    echo "</div>";
    echo "<span class='pull-right btn btn-default' onclick='hideEdit(\"editForm$i\")'>" . $lang->admin[0]->close . "</span>";
    echo "</div>"; //panel body
    echo "</div>"; //panel
    echo "<script>


        $(document).ready(function() {
             $('#edit_user_form$i').ajaxForm(options);
        });
        </script>";
    $i++;
}
if ($table) {
    echo "</table></div></div>";
}
?>
<script>
    $(document).ready(function() {
        $(".date").datepicker({dateFormat: "mm/dd/y"});
    });
</script>   
