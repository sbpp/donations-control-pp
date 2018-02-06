<?php
define('NineteenEleven', true);
require_once 'includes/config.php';

$days = 30;
$days = $days * 86400;
$today = date('U');
$dayLookup = $today - $days;
?>

<!DOCTYPE html>
<html>
<meta http-equiv="Content-Type"content="text/html;charset=UTF8">
<head>
	<style type="text/css">
	body{
		width: 90%;
	}
	.name, .amount{
		display: inline-block;
		word-wrap:break-word;
	}
	.amount{
		float: right;
	}
	.tenDivider{
		height: 2px;
		background-color: #C7231A;
		width: 100%;
		margin-bottom: 10px;
	}
	</style>
</head>
<body>
<form action="">
<input type='radio' id='cb1' name='i' onclick="e()" checked > Top10 <input type='radio' name='i' id='cb2' onclick="e()"> Last 30 Days
</form>
<div class='tenDivider'></div>
<?php
$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DONATIONS_DB);

echo "<div class='topTen' id='top'>";
//top 10
$sql = "SELECT `username`,`total_amount`,`steam_link`, `steam_id` FROM `donors` ORDER BY `total_amount` DESC LIMIT 0 , 10;";

$result = $mysqli->query($sql)or die($mysqli->error);

while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
	$q = $mysqli->query("SELECT `personaname` FROM `cache` WHERE `steamid` = '".$row['steam_id']."';")or die($mysqli->error);
	if ($q->num_rows > 0) {
		$name = $q->fetch_array(MYSQLI_ASSOC);
		echo "<a href='" . $row['steam_link'] . "' target='_BLANK'><div class='name'>" . $name['personaname'] . "</div></a><div class='amount'> $" . $row['total_amount'] . "</div><br />";
	}else{
		echo "<a href='" . $row['steam_link'] . "' target='_BLANK'><div class='name'>" . $row['username'] . "</div></a><div class='amount'> $" . $row['total_amount'] . "</div><br />";
	}
}
echo "</div>";

//last month
echo "<div class='lastMonth' id='lm'>";
$sql = "SELECT `user_id`, `username`,`total_amount`,`steam_link` FROM `donors` WHERE `renewal_date` >= $dayLookup OR `sign_up_date` >= $dayLookup ORDER BY `user_id` DESC";
$result = $mysqli->query($sql);

while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
	$q = $mysqli->query("SELECT `personaname` FROM `cache` WHERE `steamid` ='" .$row['steam_id']."';")or die($mysqli->error);
	if ($q->num_rows > 0) {
		$name = $q->fetch_array(MYSQLI_ASSOC);
		echo "<a href='" . $row['steam_link'] . "' target='_BLANK'><div class='name'>" . $name['personaname'] . "</div></a><div class='amount'> $" . $row['total_amount'] . "</div><br />";
	}else{
		echo "<a href='" . $row['steam_link'] . "' target='_BLANK'><div class='name'>" . $row['username'] . "</div></a><div class='amount'> $" . $row['total_amount'] . "</div><br />";
	}
}

echo "</div>";
?>

	<script type="text/javascript">
	function e(){
		if(document.getElementById('cb1').checked){
			document.getElementById('lm').style.display = 'none';
			document.getElementById('top').style.display = 'block';
		}
		if (document.getElementById('cb2').checked) {
			document.getElementById('top').style.display = 'none';
			document.getElementById('lm').style.display = 'block';
		}
	}
	e();
	</script>
</body>
</html>