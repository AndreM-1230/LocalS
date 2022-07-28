<?php
session_id();
session_start();
if($_SESSION['sign'] == null){
    $_SESSION['sign'] = 0;
}
if($_SESSION['user'] == null){
    $_SESSION['user'] = '';
}
$_SESSION['reader']=null;
include('functions.php');
include('db.php');
define("_INC", 1);
ini_set("memory_limit","6000M");
ini_set('mysql.connect_timeout', 7200); // таймаут соединения с БД (сек.)
ini_set('max_execution_time', 7200);    // таймаут php-скрипта
ini_set('display_errors','ON');
error_reporting('E_ALL');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <script src="./js/jquery.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <link href="./js/bootstrap-select-1.13.9/dist/css/bootstrap-select.min.css"    rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>
    <!-- <link href="./css/Mystyle.css"                                                 rel="stylesheet">--->
    <script type="text/javascript" src="Myscript.js"></script>
</head>
<header>
    <?php
    echo showheader();
    ?>
</header>
<body>
    <div class="container col-md-8 col-lg-12 col-sm-6">
        <div class="row">
        <h1 class="text-dark" style="color:#2196F3;">Мой локальный сайт </h1>
        <div class=" border rounded col">

            <?php
            echo useronline();
            ?>
        </div>
        <div class="border rounded col">
            <?php
            if($_SESSION['sign'] == 1){
                echo unread_message();
            }
            ?>
        </div>
        </div>
        <div class="clearfix"></div>
    </div>
</body>
<?php
    echo showfooter();
?>
</html>
