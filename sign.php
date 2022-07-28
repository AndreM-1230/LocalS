<?php
   session_start();
   include('functions.php');
   include('db.php');
   $pdo = db();
   $sql =  "SELECT * FROM users";
   $dbchat = $pdo->query($sql);
?>
<html lang="ru">
   <head>
      <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
       <script type="text/javascript" src="scripttime.js"></script>
       <script src="./js/jquery.min.js"></script>
       <script src="./js/bootstrap.min.js"></script>
       <link href="./js/bootstrap-select-1.13.9/dist/css/bootstrap-select.min.css"    rel="stylesheet">

       <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css"
             rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">

       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
               integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>
       <script type="text/javascript" src="Myscript.js"></script>
   </head>
   <header>
       <?php
       echo showheader();
       ?>
   </header>
   <body>
        <?php
        echo sign_form();
        ?>
   </body>
   <?php
   echo showfooter();
   ?>
</html>