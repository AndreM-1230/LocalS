<?php
session_start();
   $_SESSION['reader'] = $_POST['selectuser'];
include('./db.php');
$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set names utf8");
$sql="UPDATE messages SET unread = '-' WHERE
                        author ='$_SESSION[reader]' 
                        AND reader ='$_SESSION[user]' 
                        AND unread = 'yes'";
$pdo->query($sql);
   header("Location: chat.php");
