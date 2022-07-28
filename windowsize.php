<?php
if(isset($_POST['width']) && isset($_POST['height'])) {
    $_SESSION['window_width'] = $_POST['width'];
    $_SESSION['window_height'] = $_POST['height'];
}

