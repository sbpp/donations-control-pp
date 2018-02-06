<?php
session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}

$session = $_SESSION;
session_write_close();

define('NineteenEleven', TRUE);

require_once '../../../includes/config.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
try {
    $sb = new sb;
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

$all = filter_input(INPUT_POST, 'showAll', FILTER_VALIDATE_BOOLEAN);

//demographics
if ($all) {
    $where = '1';
} else {
    $where = '`activated` =1';
}

$i = 0;
try {
    $stmt = $sb->ddb->prepare("SELECT `country`,`id` FROM `player_analytics` WHERE `auth` = ? ORDER BY id DESC LIMIT 0,1;");
    foreach ($sb->ddb->query("SELECT `steam_id`,`total_amount` FROM `donors` WHERE $where") as $auth) {
        $stmt->bindParam(1, $auth['steam_id']);
        $stmt->execute();
        $country = $stmt->fetch(PDO::FETCH_ASSOC);
        //$stmt->closeCursor();
        if (empty($country['country'])) {
            $country['country'] = 'Unknown';
        }

        $data2[$i] = array($country['country'], $auth['total_amount']); //dev
        $data[$i] = $country['country'];
        $i++;
    }
} catch (Exception $ex) {
    print($ex->getMessage());
}

$e = array_count_values($data);

$ctrys = "'" . implode("','", array_keys($e)) . "'";

$count = implode(',', $e);

//dev
$amount = array();


foreach (array_keys($e) as $country) {
    $amount[$country] = null;
    foreach ($data2 as $d) {
        if ($d[0] == $country) {
            //$amount[$country] = $amount[$country] + ($d[1]/10);
            $amount[$country] = $amount[$country] + $d[1];
        }
    }
}
$amount = implode(',', $amount);

//echo "$ctrys || $amount ||$count";
?>

<div id='demographics-holder'>
    <canvas id='demographics'></canvas>
</div>


<script>
    var barChartData = {
        labels: [<?php echo $ctrys ?>],
        datasets: [
            {
                label: 'Number of donors',
                fillColor: "rgba(203, 117, 92,0.5)",
                strokeColor: "rgba(203, 117, 92,0.8)",
                highlightFill: "rgba(203, 117, 92,0.75)",
                highlightStroke: "rgba(203, 117, 92,1)",
                data: [<?php echo $count; ?>]
            },
            {
                label: 'Amount donated',
                fillColor: "rgba(203, 188, 92, .5);",
                strokeColor: "rgba(203, 188, 92, .8);",
                highlightFill: "rgba(203, 188, 92, .75);",
                highlightStroke: "rgba(151,187,205,1)",
                data: [<?php echo $amount; ?>]
            }
        ]
    };


</script>