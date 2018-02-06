<?php
if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}
?>
<script src="../js/Chart/Chart.js" type="text/javascript"></script>
<div class='wide-charts'>
    <div class='history-chart-container'>
        <div class='history-chart'><img src="../images/ajax-loader-big.gif" alt=""/></div>
        <p><span style='color:rgba(220,220,180,1);'>Revenue</span>/<span style='color:rgba(151,187,255,1);'>Current Donors</span>/<span style='color:rgba(220,50,220,1);'>Average Donation</span></p>
        <div class="input-group">
            <div class="input-group-btn drop-down">

                <button type="button" class="btn btn-default  dropdown-toggle pull-left" data-toggle="dropdown">
                    Select Months <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="#" onclick="setMonths('12')">12</a></li>
                    <li><a href="#" onclick="setMonths('11')">11</a></li>
                    <li><a href="#" onclick="setMonths('10')">10</a></li>
                    <li><a href="#" onclick="setMonths('9')">9</a></li>
                    <li><a href="#" onclick="setMonths('8')">8</a></li>
                    <li><a href="#" onclick="setMonths('7')">7</a></li>
                    <li><a href="#" onclick="setMonths('6')">6</a></li>
                    <li><a href="#" onclick="setMonths('5')">5</a></li>
                    <li><a href="#" onclick="setMonths('4')">4</a></li>
                    <li><a href="#" onclick="setMonths('3')">3</a></li>
                    <li><a href="#" onclick="setMonths('2')">2</a></li>
                    <li class="divider"></li>
                    <li><a href="#" onclick="selectOther()">Other</a></li>
                </ul>
            </div>
            <input type='number' class='form-control selOther' style='display:none;' id='otherMonths' onchange='setMonths("0")'>
        </div>
        <input type='hidden' value='3' name="numMonths" id='numMonths'>

    </div>


    <?php
    if (PLAYER_TRACKER) {
        echo "<div class='demographics'>
    <div class='demographics-chart'><img src='../images/ajax-loader-big.gif' alt=''/></div>
    <p>Demographics</p>
    <button class='btn btn-default' title='Caution: may be resource intensive' id='demoBtn' onClick='changeDemo()'>Show all donors</button>
</div>
<input type='hidden' id='demo-changer' value='0'>
";
    }
    ?>
</div>
<div class='skinny-charts'>
    <div class='tier-chart-continater do-nut'>
    <!-- <div class='chartCheckbox'><input type='checkbox' id='allUsers' onchange='getTiers()'><div id='allText'>Show all users</div></div> -->
        <div class='tier-chart'><img src="../images/ajax-loader-big.gif" alt=""/></div>
        <p>Donors per group</p>
        <button class='btn btn-default btn-lg pull-right' onclick='getTiers()' id='allBtn' value='0'>Show all users</button>
    </div>

    <div class='n-vs-r-continater do-nut'>
    <!-- <div class='chartCheckbox'><input type='checkbox' id='allUsers' onchange='getTiers()'><div id='allText'>Show all users</div></div> -->
        <div class='n-vs-r-chart'><img src="../images/ajax-loader-big.gif" alt=""/></div>
        <p>New vs Returning</p>
    </div>
</div>
<script type="text/javascript">
    function setMonths(num) {
        if (num == 0) {
            var other = $('#otherMonths').val();
            if (other < 2) {
                other = 2;
            }
            $('#numMonths').val(other);
        } else {
            $('#numMonths').val(num);
            $('#otherMonths').val('').hide();
        }
        getHist();
    }

    $(document).ready(function() {
        getHist();
        getTiers();
        getnvr();
<?php
if (PLAYER_TRACKER) {
    echo "getDemographics();";
}
?>
    });

    function getHist() {
        var varNumMonths = $('#numMonths').val();
        $.ajax({
            type: 'POST',
            url: 'pages/ajax/history-chart.php',
            data: {numMonths: varNumMonths, ajax: 1},
            success: function(result) {
                $('.history-chart').html(result);
                var ctx = document.getElementById('histCanvas').getContext('2d');
                window.myLine = new Chart(ctx).Line(lineChartData, {
                    responsive: true
                });
            }});
    }
    function selectOther() {
        $('.selOther').show();
    }


    function getTiers() {
        // var varAll = $('#allUsers').prop('checked');
        varAll = $('#allBtn').val();
        if (varAll == 1) {
            $('#allBtn').html('Show active users');
            $('#allBtn').val('0');
        } else {
            $('#allBtn').html('Show all users');
            $('#allBtn').val('1');
        }
        $.ajax({
            type: 'POST',
            url: 'pages/ajax/users-chart.php',
            data: {all: varAll, ajax: 1},
            success: function(result) {
                $('.tier-chart').html(result);


                var ctx = document.getElementById("usersCanvas").getContext("2d");
                window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {responsive: true});

            }});
    }
    function getnvr() {
        $.ajax({
            type: 'POST',
            url: 'pages/ajax/n-vs-r.php',
            data: {ajax: 1},
            success: function(result) {
                $('.n-vs-r-chart').html(result);
                var ctx = document.getElementById("nvrCanvas").getContext("2d");
                window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {responsive: true});
            }});
    }


    function changeDemo() {
        var demoVal = $('#demo-changer').val();
        if (demoVal == 0) {
            $('#demo-changer').val('1');
            $('#demoBtn').html('Show Active Donors');
        } else {
            $('#demo-changer').val('0');
            $('#demoBtn').html('Show all donors');
        }
        getDemographics();
    }

<?php
if (PLAYER_TRACKER) {
    echo "

    function getDemographics() {
        var demoVal = $('#demo-changer').val();
        $('.demographics-chart').html(\" <img src='../images/ajax-loader-big.gif' alt=''/>\");
        $.ajax({
            type: 'POST',
            url: 'pages/ajax/demographics-chart.php',
            data: {ajax: 1, showAll: demoVal},
            success: function(result) {
                $('.demographics-chart').html(result);

            var ctx = document.getElementById('demographics').getContext('2d');
            window.myBar = new Chart(ctx).Bar(barChartData, {
                responsive : true
            });
            }});
    }
        ";
}
?>

</script>



