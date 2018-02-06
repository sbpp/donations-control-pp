<?php
if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}

if (isset($_POST['group_name'])) {


    $steamid = filter_input(INPUT_POST, 'steamid_user', FILTER_SANITIZE_STRING);
    $group_name = filter_input(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING);
    $CCC_enabled = filter_input(INPUT_POST, 'ccc', FILTER_SANITIZE_NUMBER_INT);
    $multiplier = filter_input(INPUT_POST, 'multiplier', FILTER_VALIDATE_FLOAT, array('options' => array('flags' => FILTER_FLAG_ALLOW_THOUSAND)));
    $minimum = filter_input(INPUT_POST, 'minimum', FILTER_SANITIZE_NUMBER_INT);

    $groupData = $sb->getUserGroup($steamid);

    $sb->addGroup($groupData['srv_group'], $groupData['group_id'], $groupData['srv_group_id'], $groupData['server_id'], $multiplier, $CCC_enabled, $minimum);

    print "<div class='alert alert-success' role='alert'>added successfully</div>";
    $log->logAction($Suser_name . " added the " . $group_name . " group.");
}



echo "<div id='groupMgmt'>";
echo "<div class='groupList'>";
//$groups = $sb->listGroups //geting from parent page
if ($groups) {
    echo "<div class='row'>"
    . "<div class='col-md-6'>";
    foreach ($groups as $group) {
        //echo "";
        if ($group['active'] == '0') {
            echo "<div class='col-md-12' style='padding:0px; margin: 10px;border:5px solid rgba(215, 40, 40, 0.8);'>";
        } else {
            echo "<div class='col-md-12' style='padding:0px;margin: 10px;border:5px solid rgba(67, 130, 44, 0.8);'>";
        }

        echo "<div class='list-group-item'>" . $group['name'] . "<a href='show_donations.php?loc=edit_groups&id=" . $group['id'] . "' title='Edit " . $group['name'] . "' style='cursor:context-menu;' ><span class='glyphicon glyphicon-cog'></span></a></div>";
        echo "<div class='list-group-item'>Group ID: " . $group['group_id'] . "</div>";
        echo "<div class='list-group-item'>Server Group ID: " . $group['srv_group_id'] . "</div>";
        echo "<div class='list-group-item'>Server ID: " . $group['server_id'] . "</div>";
        echo "<div class='list-group-item'>Multiplier: " . $group['multiplier'] . " ($" . round(31 / $group['multiplier'], 2) . "/Month)</div>";
        echo "<div class='list-group-item'>Minimum Donation: " . $group['minimum'] . "</div>";
        if (CCC) {
            echo "<div class='list-group-item'>CCC: ";
            if ($group['ccc_enabled']) {
                echo "<span class='glyphicon glyphicon-ok-circle'>";
            } else {
                echo "<span class='glyphicon glyphicon-ban-circle'></span>";
            }
            echo "</div>";
        }
        //$result = $sb->ddb->query("SELECT * , COUNT( * ) AS cnt FROM donors WHERE `activated` = 1 GROUP BY `tier`;");
        try {
            foreach ($sb->ddb->query("SELECT `tier` , COUNT( * ) AS cnt FROM donors WHERE `activated` = 1 GROUP BY `tier`;") as $row) {
                if ($row['tier'] == $group['id']) {

                    echo "<div class='list-group-item'>" . $row['cnt'] . " Active donors";
                }
            }

            //$result = $mysqliD->query("SELECT * , COUNT( * ) AS cnt FROM donors GROUP BY `tier`;");
            foreach ($sb->ddb->query("SELECT `tier` , COUNT( * ) AS cnt FROM donors GROUP BY `tier`;") as $row) {
                if ($row['tier'] == $group['id']) {
                    echo "(" . $row['cnt'] . " Total)";
                }
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        echo "</div>"; // donors count
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-warning'>No groups are set up yet!</div>";
}
echo "</div>";
echo "</div>";
?>
<div class='col-md-6' >
    <div class="panel panel-default groups-panel" style='min-width:600px;'>
        <div class="panel-heading"><h3>Add new group</h3></div>
        <div class="panel-body">

            <form action="show_donations.php?loc=group_management" method="POST" id="group_form">

                <input type="hidden" name="group_name" id="group_name" value='group'>

                <div class="input-group" title="To add a new group, Create an admin group and a server group in sourcebans. Add a new admin, and assign it to those groups (can be fake steamid). Then whatever Steam ID you put in Sourcebans, put in this box. ">

                    <span class="input-group-addon"><span class='glyphicon glyphicon-info-sign'></span> Steam ID</span>
                    <input type="text" name="steamid_user" class="form-control" id="id-box" placeholder="SteamID in Sourcebans" required size="20">
                </div>

                <div class="input-group" title="31 divided by this number is how much perks will cost per month. eg 31/6.2 = $5/month">
                    <span class="input-group-addon"><span class='glyphicon glyphicon-info-sign'></span> Multiplier</span>
                    <input type="text" name="multiplier" class="form-control" id="multiplier" placeholder="6.2" required>
                </div>

                <div class="input-group" title="Minimum dontnation that can be recieved. Use 0 for no minimum">
                    <span class="input-group-addon"><span class='glyphicon glyphicon-info-sign'></span> Minimum $</span>
                    <input type="text" name="minimum" class="form-control"  placeholder="5" required>
                </div>

                <?php
                if (CCC) {
                    echo"
        <div class='panel panel-default panel-small'>
            <h3 class='panel-title'>CCC enabled?</h3>
            <div class='panel-body'>

              <div class='input-group '>
                <span class='input-group-addon'>
                    <input type='radio' name='ccc' value='1' checked='1'>
                </span>
                <input type='text' readonly value='Yes' class='form-control' style='cursor:context-menu;' >
            </div>

            <div class='input-group '>
                <span class='input-group-addon'>
                    <input type='radio' name='ccc' value='0'>
                </span>
                <input type='text' readonly value='No' class='form-control' style='cursor:context-menu;' >
            </div>
        </div>";
                } else {
                    echo "<input type='hidden' name='ccc' value='0'>";
                }
                ?>
        </div>

        </form>
        <input type="submit" form="group_form" class='btn btn-default' value = "Add New Group"/>
    </div>
</div>
</div>
</div>
