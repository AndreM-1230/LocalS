<?php
session_start();
include('./db.php');
$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set names utf8");
$sql_chat =  "SELECT * FROM messages WHERE author = '$_SESSION[reader]' AND reader = '$_SESSION[user]'";
$db_chat = $pdo->query($sql_chat);
foreach ($db_chat as $value){
    if($value['time'] > $_SESSION['chat_check']){
        $_SESSION['chat_check'] = $value['time'];
        echo "<table class='table table-condensed'>
            <tr class='col-xs-2 bg-info'>
                <td class='blockquote text-left text-dark'>$value[author]</td>
                <td class='blockquote text-end'>
                    <blockquote class='blockquote text-end text-break'>
                        <p class='text-dark'>$value[message]</p>
                    </blockquote>
                    <figcaption class='blockquote-footer text-end text-dark'>$value[time]</figcaption>
                </td>
            </tr>
        </table>";
    }

}
