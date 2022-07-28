<?php
   session_start();
   include('./db.php');
   if(isset($_POST['message']) ) {
       $time=date('d.m.y H:i:s');
       $pdo = db();
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       $pdo->exec("set names utf8");
       $sql = "INSERT INTO messages (author, message, reader, time) VALUES ('$_SESSION[user]','$_POST[message]','$_SESSION[reader]', '$time')";

       if ($pdo->query($sql)) {
           header("Location: Chat.php");
       } else {
           echo "Ошибка" . $pdo->error;
       }
   }
?>