<?php
session_start();
if (!isset($_SESSION['username']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die();
}

$session = $_SESSION;
session_write_close();
$all = filter_input(INPUT_POST, 'all', FILTER_VALIDATE_BOOLEAN);

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

$groups = $sb->listGroups();
if ($groups) {
    if ($all == true) {
        $stmt = $sb->ddb->prepare("SELECT count(*) as numUsers FROM `donors` WHERE tier = :id;");
    } else {
        $stmt = $sb->ddb->prepare("SELECT count(*) as numUsers FROM `donors` WHERE activated = '1' and tier = :id;");
    }
    foreach ($groups as $group) {
        $stmt->bindParam(1, $group['id']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $data[$group['name']] = $row['numUsers'];
    }
} else {
    echo "<div class='alert alert-danger' role='alert'>Couldnt fetch groups info from database</div>";
    die();
}

$colors = array(
    array('#F7464A', "#FF5A5E"),
    array('#46BFBD', "#5AD3D1"),
    array('#FDB45C', "#FFC870"),
    array('#949FB1', "#A8B3C5"),
    array('#4D5360', "#616774"),
);
$i = 0;
$doughnut = '';
foreach ($data as $name => $num) {
    $doughnut .= "{
                value: $num,
                color:'" . $colors[$i][0] . "',
                highlight: '" . $colors[$i][1] . "',
                label: '$name'
                },";
    $i++;
}
$doughnut = substr($doughnut, 0, -1);
?>
<!-- <script src="../../../js/Chart/Chart.js"></script> -->
<body>
    <div id="canvas-holder">
        <canvas id="usersCanvas" width="350" height="350"/>
        <div class='chartText'>
        </div>
    </div>


    <script>
        var doughnutData = [<?php echo $doughnut; ?>];
    </script>