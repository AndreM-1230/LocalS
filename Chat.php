<?php
   session_start();
   include('functions.php');
   include('db.php');
   define("_INC", 1);
   ini_set("memory_limit","6000M");
   ini_set('mysql.connect_timeout', 7200); // таймаут соединения с БД (сек.)
   ini_set('max_execution_time', 7200);    // таймаут php-скрипта
   ini_set('display_errors','ON');
   error_reporting('E_ALL');

?>

<html lang="ru">
   <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>
    <script src="./js/jquery.min.js"></script>
    <script src="./js/jquery.maskedinput.js"></script>
    <script type="text/javascript" src="Myscript.js"></script>
   </head>
   <header>
       <?php
       echo showheader();
       ?>
   </header>
   <body style="overflow: hidden;" onload="$(function()
{
    var chat_scroll = $('#chat_result');
    chat_scroll.scrollTop(chat_scroll.prop('scrollHeight'));
});">
      <div class="container col-md-8" style='text-align: center;'>
         <div class="row">
            <div class="table-responsive col-md-2" style='text-align: center;'>
               <?php
               if($_SESSION['sign'] == 1){
                 echo chatu();
               }
               ?>
            </div>
            <div class="table-responsive col-md-8" >
               <?php
               if($_SESSION['sign'] == 1 && $_SESSION['reader'] != null){
                  echo chatf();
               }
               ?>
            </div>
         </div>
      </div>
   </body>
   <?php
   echo showfooter();
   ?>
</html>
