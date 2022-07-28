<?php

function set_files(){
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
    $sql_files="SELECT * FROM table_file";
    $tb_files = $pdo->query($sql_files);
    return $tb_files;
}

function unread_message(){
    $return = '';
    $fl = 0;
    if($_SESSION['user'] != null) {
        $pdo = db();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("set names utf8");
        $sql_chat = "SELECT * FROM messages WHERE
                        author <>'$_SESSION[user]' 
                        AND reader ='$_SESSION[user]' 
                        AND unread = 'yes'";
        $db_chat = $pdo->query($sql_chat);
        foreach ($db_chat as $value) {
            if($value['unread'] == 'yes'){
                $fl = 1;
                break;
            }
        }
        if($fl == 1){
            $return .="<p class='fw-bold text-center fs-5'>Новые сообщения в чате!</p>";
        }
    }
    return $return;
}

function chatf(){
    //ВЫВОД ЧАТА
    $dbchat = dbchat_get();
    $return ='';

    $return .= "<div><div style='overflow-y: auto;' id='chat_result'>
        
        <table class='table table-condensed'>";
        //Получение всех сообщений и вывод в цикле
        foreach ($dbchat as $message) {
             if($message['author'] == $_SESSION['user']){
                $return .= "<tr class='success col-xs-2'>
                    <td class='blockquote text-left fw-bold'>$message[author]</td>
                    <td class='blockquote text-end'><blockquote class='blockquote text-end text-break'>
                            <p>$message[message]</p>
                        </blockquote>
                        <figcaption class='blockquote-footer text-end'>
                            $message[time]
                        </figcaption></td>
                    <td></td>
                        
              </tr>";
             }else{
                 $_SESSION['chat_check'] = $message['time'];
                $return .= "<tr class='active col-xs-2'>
                    <td class='blockquote text-left'>$message[author]</td>
                    <td class='blockquote text-end'><blockquote class='blockquote text-end text-break'>
                            <p>$message[message]</p>
                        </blockquote>
                        <figcaption class='blockquote-footer text-end'>
                            $message[time]
                        </figcaption></td> 
              </tr>";}
        }
    $return .= "</table></div>";
    $return .=chatsend();
    $return .= "</div><script>chatsize();</script>";
    return $return;
}

function chatsend(){
    //ФОРМА ОТПРАВКИ СООБЩЕНИЙ В ЧАТ
    $return ='';
    $return .= "<div class='input-group mb-3' style='text-align: center; bottom: 0;'>
            <form method='post' id='chatsend' action='./postmessage.php'></form>
            <input class='form-control' form='chatsend'  type='text' name='message'>
            <input class='btn btn-success' form='chatsend' type='submit' value='Отправить'>
            
        </div>";
    return $return;
}

function chatu(){
    //ВЫВОД ПОЛЬЗОВАТЕЛЕЙ
    $dbuser = dbuser_get();
    $return ='';
    $return .= "<table class='table table-condensed' style='margin: auto; text-align: center; overflow: hidden;'>
            <tr class='active'>
              <td colspan='2'>
                 Пользователи
              </td>
            </tr>
            <form id='selectuser' action='./selectuser.php' method='post'></form>";
              foreach($dbuser as $user){
                if($user['user'] != $_SESSION['user']){
                    if($_SESSION['reader'] !=null && $_SESSION['reader'] == $user['user']){
                        $return .= "<tr class='active'>
                      <td><input type='submit' style='width: 100% !important;' class='btn btn-success' form='selectuser' name='selectuser' value='$user[user]' /></td>
                  </tr>";
                    }else{
                        $return .= "<tr class='active'>
                      <td><input type='submit' style='width: 100% !important;' class='btn btn-default' form='selectuser' name='selectuser' value='$user[user]' /></td>
                  </tr>";
                    }

                }
              }
    $return .= "</table>";

    return $return;
}

function dbchat_get(){
    //ПОЛУЧЕНИЕ ВСЕХ СООБЩЕНИЙ
    $pdo = db();
    $sqlchat =  "SELECT * FROM messages WHERE 
                       author IN ('$_SESSION[user]', '$_SESSION[reader]') 
                    AND reader IN ('$_SESSION[reader]','$_SESSION[user]')";
    $dbchat = $pdo->query($sqlchat);
    return $dbchat;
}

function dbuser_get(){
    // ПОЛУЧЕНИЕ ПОЛЬЗОВАТЕЛЕЙ
    $pdo = db();
    $sqluser = "SELECT * FROM users";
    $dbuser = $pdo->query($sqluser);
    return $dbuser;
}

function showheader(){
    online_set();
    $return ='';
    $return .= "     <nav class='navbar navbar-expand-lg navbar-dark bg-dark text-white' id='size_header'>
    <div class='container-fluid col-md-8'>
        <div class='collapse navbar-collapse' id='navbarExample01'>
            <ul class='nav nav-tabs me-auto mb-2 mb-lg-0'>
                <li class='nav-item active'>
                    <a class='nav-link fs-2 text-white' aria-current='page' href='index.php'>Home</a>
                </li>
                <li class='nav-item'>";
                    if($_SESSION['sign'] == 1){
                        $return .= "<li><a class='nav-link fs-2 text-white' href='chat.php'>Чат</a></li>";
                    }else{
                        $return .= "<li><a class='nav-link fs-2 text-white disabled'  href='#'>Чат</a></li>";
                    }
                $return .= "</li>
                <li class='nav-item'>";
                if($_SESSION['sign'] == 1){
                            $return .= "<a class='nav-link fs-2 text-white' href='uploadfile.php'>Файлы</a>";
                }else{
                            $return .= "<a class='nav-link fs-2 text-white disabled' href='#'>Файлы</a>";
                }
                $return .= "</li>
            </ul>
            <ul class='nav nav-tabs-right me-auto mb-2 mb-lg-0'>
                <li class='nav-item'>";
                        if($_SESSION['user'] == ''){
                            $return .= "<a class='nav-link fs-2 text-white'  href='sign.php'>Войти</a>";
                        }else{
                            $return .= "<a class='nav-link fs-2 text-white' disabled href='#' >Привет, ". $_SESSION['user'] ."!</a>";
                        }
                $return .= "</li>
            </ul>
            <ul class='nav nav-tabs-right me-auto mb-2 mb-lg-0'>
                <li class='nav-item'>
                    <input class='bg-dark text-white fs-4' placeholder='Disabled input' id='timetext' readonly disabled type='text' style = 'visibility: hidden'/>
                </li>
            </ul>
        </div>
    </div>
</nav><script>sizeheader();</script>";
 return $return;
}

function showfooter(){
    $return ='';
    $return .= "<footer class='text-center text-white fixed-bottom bg-dark'>
        <div class='text-center p-3' style='background-color: rgba(0, 0, 0, 0.2);'>
            <a class='nav-link fs-6 text-white' href='https://github.com/merkulov-1230'>© 2022: Github</a>
        </div>
    </footer>";
    return $return;
}

function sign_form(){
    $return ='';
    $return .= "<div class='position-absolute top-50 start-50 translate-middle col-4 h-25 border rounded'>
        <form style='text-align: center'; id='post_user' action='chechsign.php' method='post'></form>
        <input form='post_user' name='table' value='users' hidden/>
        <div class='input-group mb-3 text-center'>
             <label class='fw-light w-50 fs-5'>Введите логин:</label>
             <input class='form-control fw-light' form='post_user' style='text-align: center !important' type='text' name='login' value=''/>
        </div>
        <div class='input-group mb-3 text-center'>
             <label class='fw-light w-50 fs-5'>Введите пароль:</label>
             <input class='form-control fw-light' form='post_user' style='text-align: center !important' type='password' name='pass' value=''/>
        </div>
        <br />
        <div class='text-center'>
            <input class='btn btn-success fw-light w-50' form='post_user' type='submit' value='Отправить'>
        </div>
    </div>";
    return $return;
}

function online_set(){
    $pdo = db();
    $time = time();
    $sql="UPDATE users SET online_time = $time, online_stat = 'online' WHERE user IN ('$_SESSION[user]')";
    $pdo->query($sql);
}

function useronline(){
    $return ='';
    $return .="<h4 class='text-dark' style='color:#2196F3;'>Пользователи в сети: </h4>";
    $wine = 300;
    $dbuser = dbuser_get();
    foreach($dbuser as $user){
        if(($user['online_time'] + $wine) >= time()){
            $currentDate = date('h:m:s', $user['online_time']);
            $return .="<p class='fw-bold text-center'>".$user['user']."</p><br>" ;
        }else{
            $pdo = db();
            $sql="UPDATE users SET online_stat = 'offline' WHERE user IN ('$user[user]')";
            $pdo->query($sql);
        }
    }
    $dbuser = dbuser_get();
    $return .="<h4 class='text-dark' style='color:#2196F3;'>Пользователи оффлайн: </h4>
        ";
    foreach($dbuser as $user){
        if($user['online_stat'] == 'offline' && $user['online_time'] > strtotime('-1 week')){
            $return .="";
            $currentDate = date('d-H:m', $user['online_time']);
            $return .="<div class='row'>
                <div class='fw-bold text-center col'>$user[user]</div>
                <div class='fw-bold col'>последний вход: ".$currentDate."</div>
            </div><br>";
        }else if($user['online_stat'] == 'offline' && $user['online_time'] <= strtotime('-1 week')){
            $return .="<div class='row'>
                <div class='fw-bold text-center col'>$user[user]</div>
                <div class='fw-bold col'>последний вход: больше недели назад</div>
            </div><br>";
        }
    }
    return $return;
}

function upload_files(){
    $return ='';
        $return .="<form method='POST' 
                        id='upload_files' 
                        action='servupfile.php' 
                        enctype='multipart/form-data'>
                    </form>
            <div class= 'text-center bg-light border border-dark rounded'>
                    <input type='file' name='uploadedFile' form='upload_files' class='btn btn-default'/>
                    <input class='btn btn-success' type='submit' form='upload_files' name='uploadBtn' value='Загрузить'/>
            </div>";
    return $return;
}

function show_files(){
    $return ='';
    $tb_files = set_files();
    $return .= "<div style='overflow-y: auto; height: 35rem;' id='file_result' class='col-12 row'>
        ";
    //Получение всех сообщений и вывод в цикле
    foreach ($tb_files as $value) {
        $idformdow = $value['id'];
        $idformdel = $value['id'] . 'del';
        $src = "./uploaded_files/" . $value['file_name'];
        $file_size = $value['file_size']/ 1048576;
            $return .= "<div class='card col-3 border border-dark' style='width: 18rem; height: 25rem; margin: 10px; overflow: hidden;'>
                <form method='POST' id='$idformdow' action='download_file.php'></form>
                <form method='POST' id='$idformdel' action='delete_file.php'></form>
                <input type='text' name='file_name' form='$idformdow' class='btn btn-default' style='visibility: hidden; position: absolute' value='$value[file_name]'/>
                <input type='text' name='file_type' form='$idformdow' class='btn btn-default' style='visibility: hidden; position: absolute' value='$value[file_type]'/>
                <input type='text' name='file_name' form='$idformdel' class='btn btn-default' style='visibility: hidden; position: absolute' value='$value[file_name]'/>
                <img src='$src' class='card-img-top' alt='...' style='max-height: 11rem; max-width: 16rem;'>
                <div class='card-body'>
                    <h5 class='card-title text-break' style='height: 3rem; overflow: hidden;'>$value[file_name]</h5>
                    <p class='card-text' style='height: 3rem; overflow: hidden;'>$file_size мб</p>
                </div>
                <div class='btn-group' style='margin-bottom: 1rem;'>
                    <input class='btn btn-success align-text-bottom' type='submit' form='$idformdow' name='downloadBtn' value='скачать'/>
                    <input class='btn btn-dark align-text-bottom' type='submit' form='$idformdel' name='deleteBtn' value='удалить'/>
                </div>
            </div>";
    }
    $return .= "</div><script>filesize();</script>";
    return $return;
}
