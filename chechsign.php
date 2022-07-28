<?php
session_start();
   include('functions.php');
   include('db.php');
   $pdo = db();
   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   $pdo->exec("set names utf8");
   $sql =  "SELECT * FROM users";
   $res = $pdo->query($sql);
   $res->execute();
   $array=$res->fetchAll(PDO::FETCH_ASSOC);
   //print_r($array);
   foreach ($array as $value) {
      if($value['user'] == $_POST['login'] && $value['pass'] == $_POST['pass']){
         $_SESSION['sign'] = 1;
         $_SESSION['user'] = $value['user'];
      }
   }
   if($_SESSION['sign'] != 1){
      header("Location: sign.php");
   }else{

      header("Location: index.php");
   }
?>