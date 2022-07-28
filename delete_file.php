<?php
include('db.php');
$file_name = $_POST['file_name'];
$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set names utf8");
$sql_delete = "DELETE FROM table_file WHERE file_name = '$file_name'";
$pdo->query($sql_delete);
$filepath = dirname(__FILE__).'/uploaded_files/'.$file_name;
unlink($filepath);
header("Location: uploadfile.php");
