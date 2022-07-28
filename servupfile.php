<?php
session_start();

$message = ''; 
if (isset($_POST['uploadBtn']) && $_POST['uploadBtn'] == 'Загрузить')
{
  if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK)
  {
    // get details of the uploaded file
    $fileTmpPath = $_FILES['uploadedFile']['tmp_name'];
    $fileName = $_FILES['uploadedFile']['name'];
    $fileSize = $_FILES['uploadedFile']['size'];
    $fileType = $_FILES['uploadedFile']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    // sanitize file-name
    //$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
    // check if file has one of the following extensions
    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc','pdf', 'docx');

    if (in_array($fileExtension, $allowedfileExtensions))
    {
        include('./db.php');
        $pdo = db();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("set names utf8");
        $sql ="INSERT INTO table_file (file_name, file_type, file_size) VALUES ('$fileName', '$fileType', '$fileSize')";
        $pdo->query($sql);
        // directory in which the uploaded file will be moved
        $uploadFileDir = './uploaded_files/';
        $dest_path = $uploadFileDir . $fileName;

        if(move_uploaded_file($fileTmpPath, $dest_path))
        {
          $message ='File is successfully uploaded.';
        }
        else
        {
          $message = 'There was some error moving the file to upload directory. Please make sure the upload directory is writable by web server.';
        }
    }
    else
    {
        $message = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
    }
  }
  else
  {
    $message = 'There is some error in the file upload. Please check the following error.<br>';
    $message .= 'Error:' . $_FILES['uploadedFile']['error'];
  }
}
$_SESSION['message'] = $message;
header("Location: uploadfile.php");