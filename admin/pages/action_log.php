<?php

if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}


if (file_exists(ABSDIR . "admin/logs/action.log")) {

    $error = array_reverse($log->getLog('action.log'));

    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">' . $lang->admin[0]->actionlog . '</div>';
    echo '<div class="panel-body">';
    echo "<table class='table logsTable'>";
    echo "<tr><th>Date/Time</th><th>Action</th></tr>";
    foreach ($error as $err) {
        $data = explode("|", $err);
        echo "<tr>";
        echo"<td>" . $data[0] . "</td>";
        echo"<td>" . $data[1] . "</td>";
        //echo"<td>".$data[2]."</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    echo "</div>";
    unset($log);
} else {
    Print '<div class="alert alert-info" role="alert">No Actions Reported</div>';
}
