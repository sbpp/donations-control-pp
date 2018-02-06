<?php
if (!defined('adminPage')) {
    exit("Direct access not premitted.");
}
if (!defined('NineteenEleven')) {
    define('NineteenEleven', TRUE);
}

if (isset($_POST['toggleView'])) {
    if ($_SESSION['table'] == "Show Tiles") {
        $_SESSION['table'] = false;
    } else {
        $_SESSION['table'] = true;
    }
    if (STATS) {
        @$log->stats("TBL");
    }
}
$table = $_SESSION['table'];

if (isset($_POST['searchInput'])) {
    $search = $_POST['searchInput'];
} else {
    $search = false;
}
if (isset($_POST['show_expired'])) {
    $show_expired = true;
} else {
    $show_expired = false;
}
echo "<div class='listPage'></div>";
?>
<nav class="navbar navbar-inverse navbar-fixed-bottom" role="navigation">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header pull-left">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bottomNav">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bottomNav">
            <?php
            if (isset($_POST['show_expired'])) {
                echo "<form action='show_donations.php' method='POST' id='clear_expired' class='navbar-form navbar-left' >
                 <input id='clearExpired' class='btn btn-default' type='submit' value='" . $lang->admin[0]->hide . "' form='clear_expired' />
             </form>";
            } else {
                echo "<form action='show_donations.php' method='POST' id='show_expired' class='navbar-form navbar-left' >
                 <input id='clearExpired' class='btn btn-default' type='submit' value='" . $lang->admin[0]->show . "' form='show_expired' name='show_expired'/>
             </form>";
            }
            echo "<form action='show_donations.php' method='POST' id='toggleView' class='hide-mobile navbar-form navbar-left' >";
            if ($table) {
                echo "<input class='btn btn-default' type='submit' value='Show Tiles' form='toggleView' name='toggleView'/>";
            } else {
                echo "<input class='btn btn-default' type='submit' value='Show Table' form='toggleView' name='toggleView'/>";
            }
            echo "</form>";
            ?>
            <form class="navbar-form navbar-left" role="search" action="show_donations.php"  method="POST" id="searchForm" >
                <div class="form-group">
                    <input type="text" class="form-control" size="30" placeholder="<?php echo $lang->admin[0]->searchmsg; ?>" id="searchInput" name="searchInput" />
                </div>
                <button type="submit" class="btn btn-default" id="searchButton" form="searchForm">Submit</button>
            </form>
            <?php
            if (isset($_POST['searchInput'])) {
                print("<ul class='nav navbar-nav'>");
                print("<li class='divider'></li>");
                print("<li><a href='show_donations.php'> " . $lang->admin[0]->clear . " </a></li>");
                print("</ul>");
            }

            $tq = $sb->ddb->query("SELECT sum(total_amount) FROM `donors` WHERE 1;");
            $all_total = $tq->fetch(PDO::FETCH_ASSOC);
            $totaltotal = floor($all_total["sum(total_amount)"]);

            $aq = $sb->ddb->query("SELECT sum(current_amount) FROM `donors` WHERE activated = 1;");
            $active_total = $aq->fetch(PDO::FETCH_ASSOC);
            $at = floor($active_total["sum(current_amount)"]);
            ?>
            <p class='navbar-text'><?php echo "Active total: $at | Current total:$totaltotal"; ?> </p>
            <a class="navbar-brand navbar-right" href="http://nineteeneleven.info" target='_BLANK'>Donations Control v<?php echo VERSION_NEW; ?> by NineteenEleven</a>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
<script>
    $(document).ready(function() {
        loadList();
    });
    var options = {
        target: '.alertContainer', // target element(s) to be updated with server response
        //beforeSubmit: replace,
        success: showResponse
    };
    function showEdit(id) {

        $('#' + id).effect("fade", 400);
        $("html, body").animate({scrollTop: 0}, "slow");
        openEdit = id;
        return false;
    }
    ;
    function hideEdit(id) {

        $('#' + id).effect("fade", 400);
    }
    ;
    function showResponse() {
        $('#' + openEdit).effect("fade", 200);
        $('.alertContainer').show(); //.effect("scale", 500);
        $('.buttons').show();
        $('.ajax-loader').hide();
        loadList();
    }
    ;
    function replace() {
        $('.buttons').hide();
        $('.ajax-loader').show();
        return false;
    }
    function deleteConfirm(varSteam_id, varDiv_id) {
        if (confirm("<?php echo $lang->admin[0]->delconf; ?>")) {
            $('#' + openEdit).effect("fade", 200);
            var loading = '<div class="alert alert-info" role="alert"><img src="../images/ajax-loader.gif"> Deleting User, hang on.</div>';
            $('.alertContainer').show().html(loading);

            $.ajax({
                type: 'POST',
                url: 'pages/ajax/delete-donor.php',
                data: {steam_id: varSteam_id, action: 'delete_user', ajax: 1, divId: varDiv_id},
                success: function(result) {
                    $('.alertContainer').show().html(result);

                }});
        }
    }

</script>
