<?php
$file_name = $_POST['file_name'];
$file_type = $_POST['file_type'];
$file = 'uploaded_files/' . $file_name;
header('Content-Type: $file_type');
header('Content-Disposition: attachment; filename=?' . $file_name);
readfile($file);
