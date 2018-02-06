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

try {
//new vs returning
    foreach ($sb->ddb->query("SELECT(SELECT count(*) FROM `donors` WHERE `renewal_date` != 0) as returnDonors,(SELECT count(*) FROM `donors` WHERE `renewal_date` = 0) as uniqueDonors") as $nvr) {
        $unique = $nvr['uniqueDonors'];
        $returing = $nvr['returnDonors'];
    }
} catch (Exception $ex) {
    print($ex->getMessage());
}
$i = 0;


$doughnut = "{
                value: $unique,
                color:'#996600',
                highlight: '#8C7300',
                label: 'Unique Donors'
                },";
$doughnut .= "{
                value: $returing,
                color:'#402699',
                highlight: '#592185',
                label: 'Repeat Donors'
                }";
?>

<body>
    <div id="canvas-holder">
        <canvas id="nvrCanvas" width="350" height="350"/>
    </div>


    <script>
        var doughnutData = [<?php echo $doughnut; ?>];
    </script>