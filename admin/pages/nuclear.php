<?php
if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}
?>

<div class="panel panel-default">
    <div class="panel-body">
        <div class='rcon'>
            <div id='rconResponse' class='rconResponse'><textarea readonly id='rconOutput'></textarea></div>

            <div class="input-group">
                <span class="input-group-addon">
                    <select name='server' id='rconCombo'>
                        <option value='0'>Select a server</option>
                        <?php
                        foreach ($sb->sdb->query("SELECT `ip`,`port`,`sid` from `" . SB_PREFIX . "_servers` WHERE 1;") as $server) {

                            printf("<option value='%s'>%s</option>", $server['sid'], $server['ip'] . ":" . $server['port']);
                        }
                        ?>
                    </select>
                </span>
                <span class="input-group-addon">
                    <input type='checkbox' value='1' id='allServers'> Query all servers
                </span>
            </div>
            <div class="input-group">

                <input type='text' placeholder='Rcon command' class="form-control" id='rconCmd' />
                <span class="input-group-btn">
                    <button class='btn btn-default' type="button" onclick='sendRcon()' id='submitRcon'>Send Command</button>
                </span>
            </div>
        </div>


        <script>
            $(document).ready(function() {
                $('#rconCmd').keypress(function(e) {
                    if (e.which == 13) { //submit on enter
                        sendRcon();
                    }
                });
            });

            function sendRcon() {
                $(document).ready(function() {
                    var rconCmd = $('#rconCmd').val();
                    var srvId = $('#rconCombo').val();
                    var allSrv = $('#allServers').prop('checked');
                    //var varToken = $('#token').val();
                    console.log(rconCmd + " " + srvId + " " + allSrv); //debug code

                    if (srvId == 0 && !allSrv) {
                        $('#rconOutput').val('Please select a server first');
                        return;

                    }
                    ;

                    $.ajax({
                        type: 'POST',
                        url: 'pages/ajax/rcon.php',
                        //data: {token: varToken, id: srvId, action: 'rcon', cmd: rconCmd, allServers: allSrv, ajax: 1},

                        data: {id: srvId, action: 'rcon', cmd: rconCmd, allServers: allSrv, ajax: 1},
                        success: function(result) {
                            var currentText = $('#rconOutput').val();
                            $('#rconResponse').show();
                            currentText = currentText + '\n' + result;
                            console.log(currentText);
                            $('#rconOutput').val(currentText);
                            $('#rconOutput').scrollTop($('#rconOutput')[0].scrollHeight);
                            $('#rconCmd').val('');
                        }});
                });
            }
        </script>