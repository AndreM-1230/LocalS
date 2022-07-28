<?php
define("_INC", 1);
global $db;
include ("../../cmsconf.php");
sql_connect();
if (isset($_POST['request'])){
    if($_POST['request'] == 'FindUsers') {
        echo json_encode(sqltab('SELECT * FROM users WHERE name LIKE "%'. $_POST['name']. '%";'));
    }
    if($_POST['request'] == 'FindVisitors') {
        echo json_encode(sqltab('SELECT * FROM gate_control_visitors WHERE name LIKE "%'. $_POST['name']. '%";'));
    }
    if($_POST['request'] == 'FindCars') {
        echo json_encode(sqltab('SELECT * FROM gate_control_cars WHERE name LIKE "%'. $_POST['name']. '%";'));
    }
    if($_POST['request'] == 'FindGosnomer') {
        echo json_encode(sqltab('SELECT * FROM gate_control_gosnomer WHERE name LIKE "%'. $_POST['name']. '%";'));
    }
    if($_POST['request'] == 'FindOrganization') {
        echo json_encode(sqltab('SELECT * FROM gate_control_organization WHERE name LIKE "%'. $_POST['name']. '%";'));
    }
    if($_POST['request'] == 'FindDisposalProducts') {
        echo json_encode(sqltab('SELECT products.id, products.code as `name` FROM `disassemblies`
            LEFT JOIN disposal_products ON disposal_products.id = disassemblies.disposalProduct_id
            LEFT JOIN products ON products.id = disassemblies.product_id
            WHERE disposal_products.disposal_id = '. $_POST['disposal']. ' AND products.code LIKE "%'. $_POST['name']. '%" GROUP BY products.id;'));
    }


  if($_POST['request'] == 'AddUser') {
    $insert = "INSERT INTO `users`(
          `id`, 
          `hash`, 
          `ip`, 
          `login`, 
          `name`, 
          `password`,
          `department_id`, 
          `role_id`
      ) 
      VALUES 
        (
          NULL, 
          0, 
          0, 
          'auto_". sqltab('SELECT count(*) as count FROM users')[0]['count'] ."', 
          '".$_POST['data']['name']."*',
          '5cb45b85e3aa6ae16ac66e57377299c8',
          1, 
          1
        )";
    echo json_encode(['id' => sqlupd($insert)]);
  }
    if($_POST['request'] == 'AddVisitor') {
        $insert = "INSERT INTO `gate_control_visitors`(
          `id`, 
          `name`
      ) 
      VALUES 
        (
          NULL, 
          '".trim($_POST['data']['name'])."'
        )";
        echo json_encode(['id' => sqlupd($insert)]);
    }
    if($_POST['request'] == 'AddCar') {
        $insert = "INSERT INTO `gate_control_cars`(
          `id`, 
          `name`
      ) 
      VALUES 
        (
          NULL, 
          '".trim($_POST['data']['name'])."'
        )";
        echo json_encode(['id' => sqlupd($insert)]);
    }
    if($_POST['request'] == 'AddGosnomer') {
        $insert = "INSERT INTO `gate_control_gosnomer`(
          `id`, 
          `name`
      ) 
      VALUES 
        (
          NULL, 
          '".str_replace(" ","",trim(mb_strtolower($_POST['data']['name'],"utf-8")))."'
        )";
        echo json_encode(['id' => sqlupd($insert)]);
    }
    if($_POST['request'] == 'AddOrganization') {
        $insert = "INSERT INTO `gate_control_organization`(
          `id`, 
          `name`
      ) 
      VALUES 
        (
          NULL, 
          '".trim($_POST['data']['name'])."'
        )";
        echo json_encode(['id' => sqlupd($insert)]);
    }
}
?>