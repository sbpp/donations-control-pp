<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("location:index.php");
} else {
    $Suser_name = $_SESSION['username'];
    $Semail = $_SESSION['email'];
}
define('NineteenEleven', TRUE);
define('adminPage', TRUE);
require_once '../includes/config.php';
require_once ABSDIR . 'includes/LoggerClass.php';
require_once ABSDIR . 'includes/LanguageClass.php';
require_once ABSDIR . 'includes/SourceBansClass.php';
$_SESSION['ABSDIR'] = ABSDIR;
$language = new language;


$log = new log;
try {
    $sb = new sb;
} catch (Exception $ex) {
    echo "<div class='alert alert-danger' role='alert'>" . $ex->getMessage() . "</div>";
    $log->logError($ex->getMessage(), $ex->getFile(), $ex->getLine());
    die();
}

$groups = $sb->listGroups();

if (isset($_POST['langSelect'])) {
    $_SESSION['language'] = $_POST['langSelect'];
}

if (isset($_SESSION['language'])) {
    $lang = $language->getLang($_SESSION['language']);
} else {
    $lang = $language->getLang(DEFAULT_LANGUAGE);
}
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <meta name='viewport' content="width=device-width, initial-scale=1">
        <!--        <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />-->
        <link href="../js/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
        <link href="../bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css"/>
        <link href="../bootstrap/css/bootstrap-theme.css" rel="stylesheet" type="text/css"/>
        <link type="text/css" rel="stylesheet" href="style.css" />
        <script src="../js/jquery-2.1.0.min.js" type="text/javascript"></script>
        <script src="../js/jquery.form.min.js" type="text/javascript"></script>
        <!-- <script src="http://malsup.github.com/jquery.form.js"></script> -->
<!--        <script src="../js/bootstrap-datepicker.js" type="text/javascript"></script>-->
        <title>Donor List</title>
    </head>
    <body>

        <div class='wrapper'>


            <?php
            $nav = array(
                $lang->admin[0]->home => '',
                //$lang->admin[0]->manualentry => 'manual_entry',
                $lang->admin[0]->manualentry => '<a href="#" onclick="showNewUser()" id="manualEntry" >' . $lang->admin[0]->manualentry . '</a>',
                'Groups' => 'group_management',
                $lang->admin[0]->serverquery => 'nuclear',
                'Analytics' => 'stats',
                'Promos' => 'promotions',
                $lang->admin[0]->logs => array('href' => 'Logs', $lang->admin[0]->actionlog => 'action_log', $lang->admin[0]->errorlog => 'error_log'),
                $lang->admin[0]->logout => 'logout',
            );



            echo'<nav class="navbar navbar-inverse" role="navigation">';
            echo'<div class="container-fluid">';
            echo'<div class="navbar-header">';
            echo'<button type="button" class="navbar-toggle pull-left" data-toggle="collapse" data-target="#top-nav-bar">';
            echo'<span class="sr-only">Toggle navigation</span>';
            echo'<span class="icon-bar"></span>';
            echo'<span class="icon-bar"></span>';
            echo'<span class="icon-bar"></span>';
            echo'</button>';
            echo'<!-- <a class="navbar-brand" href="#">Brand</a> -->';
            echo'</div>';
            echo'<div class="collapse navbar-collapse" id="top-nav-bar">';
            echo'<ul class="nav navbar-nav">';

            foreach ($nav as $name => $loc) {

                if (is_array($loc)) {
                    echo "<li class='dropdown'>
        <a href='#' class='dropdown-toggle' data-toggle='dropdown'>" . $loc['href'] . "<span class='caret'></span></a>";
                    array_shift($loc); //remove the href value of the sub-menu

                    echo "<ul class=\"dropdown-menu\" role=\"menu\">";

                    foreach ($loc as $name => $loc) {
                        echo "<li class='sub-menu'>";
                        echo checkLink($name, $loc);
                        echo "</li>";
                    }

                    echo "</ul></li>";

                    continue;
                }
                echo "<li class='menu'>";
                echo checkLink($name, $loc);
                echo "</li>";
            }

            echo '</div><!-- /.navbar-collapse -->';
            echo '</ul>';


            echo '<form class="navbar-form navbar-right" id="langSelect" method="post" role="search">';
            echo '<div class="form-group">';
            echo '<select name = "langSelect" onchange="change()">';
            $langList = $language->listLang();

            foreach ($langList as $list) {
                if ($list == $lang->language) {
                    printf('<option value="%s" selected>%s</option>', $list, $availableLanguages[$list]);
                } else {
                    printf('<option value="%s">%s</option>', $list, $availableLanguages[$list]);
                }
            }

            unset($i);
            echo'</select>';
            echo '</form>';


            echo "</nav>";
            echo "<div class='main'><div class='content'>";

            function checkLink($name, $path) {

                if (preg_match('#^(<a href|<span)#', $path)) {

                    return $path;
                }

                if (preg_match('#^http(s)?://#', $path)) {

                    return "<a href='$path' target='_BLANK'>$name</a>";
                }

                return "<a href='show_donations.php?loc=$path'>$name</a>";
            }

            echo "<div class=\"alertContainer\" role=\"alert\" >";
            if (isset($_SESSION['message'])) {
                echo $_SESSION['message'];
                unset($_SESSION['message']);
            }
            echo "</div>";

            if (isset($_GET['loc']) && !empty($_GET['loc'])) {

                $loc = "pages/" . $_GET['loc'];

                if (is_file($loc . ".php")) {

                    require_once $loc . ".php";
                } elseif (is_file($loc . ".phtml")) {

                    require_once $loc . ".phtml";
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Sorry we were unable to find the page you requested.</div>";
                    //echo "<div class='error'> Sorry we were unable to find the page you requested.</div>";
                    require_once "pages/list.php";
                }
            } else {
                require_once "pages/list.php";
            }



            echo "<div class='panel panel-default newUser gradientPanel' id='newUserForm' style='display:none;'>";

            echo "<div class='panel-heading gradientPHeading'><h3>" . $lang->admin[0]->newUser . "</h3></div>";
            echo "<div class='panel-body'> ";


            echo "<form action='pages/ajax/new_user.php' method='POST' id='new_user_form'>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->steamid . "</span>";
            echo "<input name='steam_id' class='form-control' type='text' />";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->email . "</span>";
            echo "<input name='email' class='form-control' type='email' />";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->sud . " <span class='glyphicon glyphicon-calendar'></span></span>";
            echo "<input name='sign_up_date' class='form-control date' value='" . date('n/j/Y') . "' type='text' />";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->rd . " <span class='glyphicon glyphicon-calendar'></span></span>";
            echo "<input name='renewal_date' class='form-control date' type='text' />";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->ed . " <span class='glyphicon glyphicon-calendar'></span></span>";
            echo "<input name='expiration_date' class='form-control date' type='text' />";
            echo "</div>";


            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->current . "</span>";
            echo "<input name='current_amount' class='form-control' type='text' />";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->total . "</span>";
            echo "<input name='total_amount' class='form-control' type='text' />";
            echo "</div>";


            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>" . $lang->admin[0]->notes . "</span>";
            echo "<textarea name='notes' class='form-control'></textarea>";
            echo "</div>";

            echo "<div class='panel panel-default panel-small inline'>";
            echo "<h3 class='panel-title'>" . $lang->admin[0]->status . "</h3>";
            echo "<div class='panel-body'> ";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>";
            echo "<input type='radio' name='activated' value='1' />";
            echo "</span>";
            echo "<input type='text' readonly value='" . $lang->admin[0]->perkson . "' class='form-control' style='cursor:context-menu;'>";
            echo "</div>";

            echo "<div class='input-group'>";
            echo "<span class='input-group-addon'>";
            echo "<input type='radio' name='activated' value='2' />";
            echo "</span>";
            echo "<input type='text' readonly value='" . $lang->admin[0]->perksoff . "' class='form-control' style='cursor:context-menu;'>";
            echo "</div>";
            echo "</div>";
            echo "</div>";

            echo "<div class='panel panel-default panel-small inline'>";
            echo "<h3 class='panel-title'>" . $lang->admin[0]->tier . "</h3>";
            echo "<div class='panel-body'>";
            foreach ($groups as $group) {
                echo "<div class='input-group'>";
                echo "<span class='input-group-addon'>";
                echo "<input type='radio' name='tier' value='" . $group['id'] . "' id='tierRadio' />";
                echo "</span>";
                echo "<input type='text' readonly value='" . $group['name'] . "' class='form-control' style='cursor:context-menu;'>";
                echo "</div>";
            }
            echo "</div>";
            echo "</div>";

            echo "<input type='hidden' name='new_user_form' value='1'>";
            echo "</form>";
            echo "<span class='pull-right btn btn-default' onclick='hideNewUser()'>" . $lang->admin[0]->close . "</span>";
            echo "<input type='submit' class='btn btn-default' value='" . $lang->admin[0]->newUser . "' form='new_user_form' />";
            ?>

            <script>
                $(document).ready(function() {

                    $('#new_user_form').ajaxForm(newOptions);

                    $(".date").datepicker({dateFormat: "mm/dd/y"});
                    $(".message").click(function() {
                        $(this).fadeOut();
                    });
                    $('.alertContainer').click(function() {
                        $(this).effect("fade", 400);
                    });
                });
                function loadList() {
                    //$('.alertContainer').html("<div class='alert alert-info' role='alert'><img src='../images/ajax-loader.gif' >Loading Donor List</div>");
<?php
if (isset($_SESSION['table'])) {
    $table = $_SESSION['table'];
} else {
    $table = 0;
}
if (!isset($search)) {
    $search = '';
}
if (!isset($show_expired)) {
    $show_expired = '';
}
?>
                    $.ajax({
                        type: 'POST',
                        url: 'pages/ajax/get-list.php',
                        data: {table: '<?php echo $table; ?>', search: '<?php echo $search; ?>', expired: '<?php echo $show_expired; ?>', ajax: 1},
                        success: function(result) {
                            $('.listPage').html(result);
                            //$('.alertContainer').html('');
                        }});
                }
                ;
                var newOptions = {
                    target: '.alertContainer', // target element(s) to be updated with server response
                    success: newUserResponse,
                    clearForm: true

                };
                function newUserResponse() {
                    $('#newUserForm').effect("fade", 200);
                    $('.alertContainer').show();
                    loadList();
                }
                ;


                function change() {
                    document.getElementById("langSelect").submit();
                }
                ;


                function reloadPage() {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
                ;
                function showNewUser() {

                    $('#newUserForm').effect("fade", 400);
                    $("html, body").animate({scrollTop: 0}, "slow");
                    return false;
                }
                ;
                function hideNewUser() {

                    $('#newUserForm').effect("fade", 400);
                }
                ;
            </script>
            <script src="../js/jquery-ui.min.js" type="text/javascript"></script>
            <script src="../bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <!--        <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>-->

        </div><!-- content  -->
    </div> <!-- main -->
</div> <!-- wrapper -->
</body>
</html>
