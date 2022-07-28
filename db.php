<?php
	function db() {
        $dsn = "mysql:dbname=db_chat;host=LocalS";
        $user = "root";
        $password = "root";
        return new \PDO($dsn, $user, $password);
    }
?>