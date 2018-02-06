<?php

if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}

if (isset($_POST['editGroup_name'])) {




    $gn = filter_input(INPUT_POST, 'editGroup_name', FILTER_SANITIZE_STRING);
    $gid = filter_input(INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT);
    $sgid = filter_input(INPUT_POST, 'srv_group_id', FILTER_SANITIZE_NUMBER_INT);
    $sid = filter_input(INPUT_POST, 'server_id', FILTER_SANITIZE_NUMBER_INT);
    $mult = filter_input(INPUT_POST, 'multiplier', FILTER_VALIDATE_FLOAT, array('options' => array('flags' => FILTER_FLAG_ALLOW_THOUSAND)));
    $c = filter_input(INPUT_POST, 'ccc', FILTER_SANITIZE_NUMBER_INT);
    $ac = filter_input(INPUT_POST, 'active', FILTER_SANITIZE_NUMBER_INT);
    $min = filter_input(INPUT_POST, 'minimum', FILTER_SANITIZE_NUMBER_INT);


    if ($sb->editGroup($gn, $gid, $sgid, $sid, $mult, $c, $ac, $min)) {
        $_SESSION['message'] = "<div class='alert alert-success' role='alert'>" . $_POST['editGroup_name'] . " Group Edited Successfully</div>";
        header('Location: show_donations.php?loc=group_management');
        $log->logAction($Suser_name . " edited the " . $gn . " group.");
    }
    exit();
}

$sb_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$info = $sb->getGroupInfo($sb_id);
//Array ( [id] => 5 [name] => supamanz [group_id] => 6 [srv_group_id] => 9 [server_id] => 9 [multiplier] => 2.5 [ccc_enabled] => 1 [active] => 1 )

echo'
<div class="panel panel-default gradientPanel">
  <div class="panel-heading gradientPHeading"><h3>Edit Group ' . $info['name'] . '</h3></div>
  <div class="panel-body">
				<form action="show_donations.php?loc=edit_groups" method="POST" id="editGroup_form">
                    <div class="input-group" title="Name of the group. This will also be the Tag the player recieves in game with CCC, if applicable.">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-info-sign"></span> Group Name:</span>
                        <input type="text" class="form-control" name="editGroup_name" id="editGroup_name" value="' . $info['name'] . '" readonly required >
                    </div>

                    <div class="input-group">
                        <span class="input-group-addon">srv_group_id:</span>
                        <input type="text" class="form-control" name="srv_group_id" id="srv_group_id" required value="' . $info['srv_group_id'] . '">
                    </div>

                    <div class="input-group">
				  	     <span class="input-group-addon">server_id:</span>
                         <input type="text" class="form-control" name="server_id" id="server_id" required value="' . $info['server_id'] . '">
                    </div>

                     <div class="input-group" title="31 divided by this number is how much perks will cost per month. eg 31/6.2 = $5/month">
				  	    <span class="input-group-addon"><span class="glyphicon glyphicon-info-sign"></span> Multiplier:</span>
                        <input type="text" class="form-control" name="multiplier" id="multiplier" value="' . $info['multiplier'] . '" required>
                    </div>

            <div class="input-group" title="Minimum dontnation that can be recieved.">
                <span class="input-group-addon"><span class="glyphicon glyphicon-info-sign"></span> Minimum $</span>
                <input type="text" name="minimum" class="form-control"  placeholder="5" required value="' . $info['minimum'] . '" >
            </div>

				  	';

if (CCC) {
    echo"
        <div class='panel panel-default panel-small inline half-width'>
            <div class='class='panel-title'>CCC enabled</div>
            <div class='panel-body'>

              <div class='input-group'>
                <span class='input-group-addon'>";
    if ($info['ccc_enabled']) {
        echo"<input type='radio' name='ccc' value='1' checked>";
    } else {
        echo"<input type='radio' name='ccc' value='1'>";
    }

    echo"</span>
                <input type='text' readonly value='Yes' class='form-control' style='cursor:context-menu;'>
            </div>

            <div class='input-group'>
                <span class='input-group-addon'>";
    if (!$info['ccc_enabled']) {
        echo"<input type='radio' name='ccc' value='0' checked>";
    } else {
        echo"<input type='radio' name='ccc' value='0'>";
    }
    echo "</span>
                <input type='text' readonly value='No' class='form-control' style='cursor:context-menu;'>
            </div>
        </div>
        </div>";
} else {
    echo "<input type='hidden' name='ccc' value='0'>";
}

echo "
        <div class='panel panel-default panel-small inline half-width pull-right'>
            <div class='panel-title'>Group enabled</div>
            <div class='panel-body'>

              <div class='input-group '>
                <span class='input-group-addon'>";
if ($info['active']) {
    echo'<input type="radio" name="active" value="1" checked />';
} else {
    echo'<input type="radio" name="active" value="1">';
}

echo"</span>
                <input type='text' readonly value='Yes' class='form-control' style='cursor:context-menu;'>
            </div>

            <div class='input-group '>
                <span class='input-group-addon'>";
if (!$info['active']) {
    echo '<input type="radio" name="active" value="0" checked>';
} else {
    echo '<input type="radio" name="active" value="0">';
}
echo "</span>";
echo "<input type='text' readonly value='No' class='form-control' style='cursor:context-menu;'>";
echo "</div>";
echo "</div>";
echo "</div>";

echo '<input type="hidden" name="group_id" id="group_id" required="true" value="' . $info['group_id'] . '">';
echo '</form>';
echo '<input type="submit" form="editGroup_form" value="Edit Group" class="btn btn-default" />';
echo '</div></div> <!-- Panel -->';

