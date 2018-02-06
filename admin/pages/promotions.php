<?php
if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}
require_once ABSDIR . 'includes/PromotionsClass.php';
if (isset($_POST['promo_form']) || isset($_POST['promo_edit_form'])) {

    $args = array(
        'type' => FILTER_SANITIZE_NUMBER_INT,
        'amount' => array('filter' => FILTER_VALIDATE_FLOAT,
            'flags' => FILTER_FLAG_ALLOW_FRACTION),
        'days' => FILTER_SANITIZE_NUMBER_INT,
        'number' => FILTER_SANITIZE_NUMBER_INT,
        'code' => FILTER_SANITIZE_STRING,
        'descript' => FILTER_SANITIZE_STRING
    );
    $required = array('type', 'amount', 'code', 'descript');
    if (isset($_POST['promo_edit_form'])) {
        $args['active'] = FILTER_SANITIZE_NUMBER_INT;
        $args['id'] = FILTER_SANITIZE_NUMBER_INT;
        array_push($required, 'active', 'id');
    }
    $data = filter_input_array(INPUT_POST, $args, true);
    $data['timestamp'] = date('U');


    if ($data['amount'] == 0) {
        die("<div class='alert alert-danger' role='alert'>Amount cannot be zero</div>");
    }

    foreach ($data as $key => $val) {

        if (is_null($key) && !array_key_exists($key, $required)) { //
            unset($data[$key]);
        } elseif (array_key_exists($key, $data)) {
            continue;
        } else {
            die("<div class='alert alert-danger' role='alert'>Please fill out the $key field</div>");
        }
    }
}
if (isset($_POST['promo_form'])) {
    try {
        $stmt = $sb->ddb->prepare("INSERT INTO `promotions` (`" . implode("`, `", array_keys($data)) . "`) VALUES (:" . implode(", :", array_keys($data)) . ")");
        foreach ($data as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
    } catch (Exception $ex) {
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
        $error = true;
    }
    if (!isset($error)) {
        printf("<div class='alert alert-success' role='alert'>Promotion '%s' Added Successfully</div>", $data['descript']);
    }
}

if (isset($_POST['promo_edit_form'])) {
    $vars = '';
    foreach ($data as $key => $val) {
        if ($key == 'id') {
            continue;
        }
        $vars .= "`$key`=:$key,";
    }
    $vars = substr($vars, 0, -1);
    echo $vars;
    try {
        $stmt = $sb->ddb->prepare("UPDATE `promotions` SET $vars WHERE `id` = :id");
        foreach ($data as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
            echo $key;
        }
        $stmt->execute();
    } catch (Exception $ex) {
        $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
        echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
        $error = true;
    }
    if (!isset($error)) {
        printf("<div class='alert alert-success' role='alert'>Promotion '%s' Edited Successfully</div>", $data['descript']);
    }
}



//<div class='panel panel-default half-width groups-panel pull-left'>
//    <div class='panel-title'><h3>Promotions</h3></div>
//    <div class='panel-body promo-panel'>

$p = new promotions;
$p->getActivePromos();
$i = 0;
echo "<div class='promoPanelContainer pull-left half-width'>";
foreach ($p->activePromos as $pro) {
    $redeemedCount = $p->getRedeemedCount($pro['id']);

    if ($pro['type'] == '1') {
        $type = 'Precent off';
    } else {
        $type = 'Extra Days';
    }


    echo"<div class='panel panel-default gradientPanel'>";
    printf("<div class='panel-title gradientPHeading'><h4>%s</h4></div>", $pro['descript']);
    echo"<div class='panel-body'>";

    echo"<div class='list-group'>";


    if ($pro['active'] == '1') {
        $activeText = "<div class='green'>Active</div>";
    } else {
        $activeText = "<div class='red'>Disabled</div>";
    }

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Status</span>";
    printf("<div class='list-group-item-text'>%s</div>", $activeText);
    echo"</div>";

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Type</span>";
    printf("<div class='list-group-item-text'>%s</div>", $type);
    echo"</div>";

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Amount or %</span>";
    printf("<div class='list-group-item-text'>%s</div>", $pro['amount']);
    echo"</div>";

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Number of days</span>";
    printf("<div class='list-group-item-text'>%s</div>", $pro['days']);
    echo"</div>";

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Number of promotions</span>";
    printf("<div class='list-group-item-text'>%s <span class='badge'>%s redeemed</span></div>", $pro['number'], $redeemedCount);
    echo"</div>";

    echo"<div class='list-group-item'>";
    echo"<span class='list-group-item-heading'>Promo code</span>";
    printf("<div class='list-group-item-text'>%s</div>", $pro['code']);
    echo"</div>";
    echo "<br />";
    echo "<button class='btn btn-default' onclick='toggleEditPromo($i,true)'>Edit This Promotion</button>";


    echo"</div>"; //list-group
    echo"</div>"; //panelbody
    echo"</div>"; //panel
    //echo"</div>"; //panel
    //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //
            //edit promo form below
    echo"<div id='editPromo$i' class='editPromo' style='display:none';>";
    echo"<div class='panel panel-default gradientPanel half-width'>";
    echo"<div class='panel-title gradientPHeading'><h4>Type of promotion: $type</h4></div>";
    echo"<div class='panel-body'>";


    echo"<form action='show_donations.php?loc=promotions' method='POST' id='promo_edit_form$i'>";

    echo"<div class='panel panel-default panel-small inline'>";
    echo"<div class='panel-title'>Status</div>";
    echo"<div class='panel-body'>";
    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>";
    if ($pro['active'] == '1') {
        $active1 = 'checked';
    } else {
        $active1 = '';
    }
    if ($pro['active'] == '0') {
        $active2 = 'checked';
    } else {
        $active2 = '';
    }
    echo"<input type='radio' name='active' value='1' $active1 required />";
    echo"</span>";
    echo"<input type='text' readonly value='Active' class='form-control' style='cursor:context-menu;'>";
    echo"</div>";

    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>";
    echo"<input type='radio' name='active' value='0' $active2 required />";
    echo"</span>";
    echo"<input type='text' readonly value='Disabled' class='form-control' style='cursor:context-menu;'>";
    echo"</div>";
    echo"</div>";
    echo"</div>";

    //this code allows changing of promtion type after creation.
    //probably not a good idea. should make new one instead
    // if ($pro['type'] == '1') {
    //     $type1 = 'checked';
    // } else {
    //     $type1 = '';
    // }
    // if ($pro['type'] == '2') {
    //     $type2 = 'checked';
    // } else {
    //     $type2 = '';
    // }
    // echo"<div class='panel panel-default panel-small inline'>";
    // echo"<h3 class='panel-title'>Type of promotion</h3>";
    // echo"<div class='panel-body'>";
    // echo"<div class='input-group'>";
    // echo"<span class='input-group-addon'>";
    // echo"<input type='radio' name='type' value='1' $type1 required />";
    // echo"</span>";
    // echo"<input type='text' readonly value='% off' class='form-control' style='cursor:context-menu;'>";
    // echo"</div>";
    // echo"<div class='input-group'>";
    // echo"<span class='input-group-addon'>";
    // echo"<input type='radio' name='type' value='2' $type2 required />";
    // echo"</span>";
    // echo"<input type='text' readonly value='Extra Days' class='form-control' style='cursor:context-menu;'>";
    // echo"</div>";
    // echo"</div>";
    // echo"</div>";



    printf("<input type='hidden' class='form-control' name='type' value='%s'>", $pro['type']);


    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>Amount or %</span>";
    printf("<input type='number' class='form-control' name='amount' required min='1' max='99999' value='%s'>", $pro['amount']);
    echo"</div>";

    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>Number of days</span>";
    printf("<input type='number' class='form-control' name='days' min='0' max='99999' value='%s'>", $pro['days']);

    echo"</div>";

    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>Number of promotions</span>";
    printf("<input type='number' class='form-control' name='number' min='0' max='99999' value='%s' >", $pro['number']);
    echo"</div>";


    //probably shouldnt let them change the code either
    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>Promo code</span>";
    printf("<input type='text' class='form-control' name='code' readonly value='%s'>", $pro['code']);
    echo"</div>";

    echo"<div class='input-group'>";
    echo"<span class='input-group-addon'>Description</span>";
    printf("<input type='text' class='form-control' name='descript' required maxlength='128' value='%s'>", $pro['descript']);
    echo"</div>";
    echo"<input type='hidden' name='promo_edit_form' value='1'>";
    printf("<input type='hidden' name='id' value='%s'>", $pro['id']);
    echo"<input type='submit' class='btn btn-default' value='Edit Promotion' form='promo_edit_form$i' />";
    echo"</form>";
    echo "<button onclick='toggleEditPromo($i, false)' class='btn btn-default pull-right'>Close</button>";
    echo"</div>"; //panelbody
    echo"</div>"; //panel
    echo"</div>"; //edit promo
    $i++;
}
echo "</div>"; //promoPanelContainer
?>


<script>
    function toggleEditPromo(div, scroll) {
        $('#editPromo' + div).effect("fade", 400);
        if (scroll === true) {
            $("html, body").animate({scrollTop: 0}, "slow");
        }
    }
</script>
<div class='panel panel-default half-width groups-panel pull-right'>
    <div class='panel-title'><h3>New Promotion</h3></div>
    <div class='panel-body'>

        <form action='show_donations.php?loc=promotions' method='POST' id='promo_form'>
            <div class='panel panel-default panel-small inline'>
                <div class='panel-title'>Type of promotion</div>
                <div class='panel-body'>
                    <div class='input-group'>
                        <span class='input-group-addon'>

                            <input type='radio' name='type' value='1' required />
                        </span>
                        <input type='text' readonly value='Precent off' class='form-control' style='cursor:context-menu;'>
                    </div>

                    <div class='input-group'>
                        <span class='input-group-addon'>
                            <input type='radio' name='type' value='2' required />
                        </span>
                        <input type='text' readonly value='Extra Days' class='form-control' style='cursor:context-menu;'>
                    </div>
                </div>
            </div>

            <div class='input-group'>
                <span class='input-group-addon'>Amount or %</span>
                <input type='number' class='form-control' name='amount' required min='1' max='99999' placeholder='Amount of extra days or % off'>
            </div>

            <div class='input-group'>
                <span class='input-group-addon'>Number of days</span>
                <input type='number' class='form-control' name='days' min='0' max='99999' placeholder='Lenth in days to run promotion. Blank to run forever'>

            </div>

            <div class='input-group'>
                <span class='input-group-addon'>Number of promotions</span>
                <input type='number' class='form-control' name='number' min='0' max='99999' placeholder='How many promotions to give before stopping. Blank to run forever'>
            </div>

            <div class='input-group'>
                <span class='input-group-addon'>Promo code</span>
                <input type='text' class='form-control' name='code' required placeholder='Code to be entered at checkout'>
            </div>
            <div class='input-group'>
                <span class='input-group-addon'>Description</span>
                <input type='text' class='form-control' name='descript' required maxlength='128' placeholder='Promotion description'>
            </div>
            <input type='hidden' name='promo_form' value='1'>
        </form>
        <input type='submit' class='btn btn-default' value='Create Promotion' form='promo_form' />
    </div>
</div>
