<?php
define("_INC", 1);
global $db;
include ( "../../cmsconf.php");
sql_connect();
if($_POST['request'] === 'product_id_by_code'){
  if($_POST['table'] === 'products'){
    echo json_encode(
        sqltab(
            sprintf(
                  'SELECT * FROM `products` 
                        WHERE products.code = "%s"',
                  addslashes($_POST['product_code'])
            )
        )
    );
  } else {
    echo json_encode(sqltab( sprintf('SELECT * FROM `%s` WHERE code = "%s"', addslashes($_POST['table']), addslashes($_POST['MPNK'])) ));
  }
}
if($_POST['request'] === 'check_row_create'){
  $array = [];
  foreach ( json_decode($_POST['data']) as $key => $value){
    $array[$key] = [];
    $array[$key]['profession_id'] =        sqltab(sprintf(
        "SELECT * FROM `professions` WHERE professions.code = '%s';", $value->PROF
    ))[0]['id'];
    $array[$key]['section_id'] =           sqltab(sprintf(
        "SELECT `sections`.id FROM `sections` LEFT JOIN 
                                                                      `workshops` ON sections.workshop_id = workshops.id
                                                                      WHERE 
                                                                      `sections`.code = '%s' AND 
                                                                      `workshops`.code = '%s'
                                                                      ;",
        $value->CEXU,
        $value->CEXM
    ))[0]['id'];
  }
  echo json_encode($array);
}if($_POST['request'] === 'check_row_norm'){
  $array = [];
  foreach ( json_decode($_POST['data']) as $key => $value){
    $array[$key] = [];
    $array[$key]['category_id'] =         sqltab(sprintf(
        "SELECT id FROM `categories` WHERE categories.code = '%s';",
        $value->RAZR
    ))[0]['id'];

    $array[$key]['markupPercent_id'] =     sqltab(sprintf(
        "SELECT id FROM `markup_percent` WHERE markup_percent.code = '%s';",
        $value->STAV[0]
    ))[0]['id'];

    $array[$key]['tariffRateCode_id'] =    sqltab(sprintf(
        "SELECT * FROM `tariff_rate_codes` WHERE tariff_rate_codes.code = '%s';",
        ($value->RAZR . $value->STAV[1])
    ))[0]['id'];
  }
  echo json_encode($array);
}
?>
