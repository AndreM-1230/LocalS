<?php
global $auth_status;
define("_INC", 1);
global $db;
include ("../cmsconf.php");
if(isset($_POST)){
    $sql =  "UPDATE 
                `". $_POST['data']['table'] . "`
                SET 
                    `".$_POST['data']['table']."`.`". $_POST['data']['field'] ."` = '". $_POST['sqldata'] ."' 
                WHERE 
                    `id` = '". $_POST['data']['whereId'] . "';";
    //echo $sql;
    sqlupd($sql);
    echo 'okay';
}
?>