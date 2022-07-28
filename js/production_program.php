<?php //please fix
define("_INC", 1);
global $db;
include ("../cmsconf.php");
sql_connect();


function progon(){
	$allTable =
		sqltab_key("
        SELECT * FROM production_program 
        ORDER BY production_program_otchet_id, user_id, position_index
    ", 'id');
	$len = count($allTable);

	$updarr = [];
	foreach ($allTable as $key => $value){
		$updarr[$value['cex_uch_id']][$value['user_id']][] = $value;
	}

	$i = 0;
	$buf_i = 0;
	$sql_str = "";
	foreach ($updarr as $cex_uch_id => $userArr) {
		foreach ($userArr as $user_id => $pp) {
			foreach ($pp as $key => $value) {
				$Prev = array_key_exists($key - 1, $pp) ? $pp[$key - 1]['id'] : 'NULL';
				$Next = array_key_exists($key + 1, $pp) ? $pp[$key + 1]['id'] : 'NULL';
				$sql_str .= "#".$i." - ".$len. "\n UPDATE production_program SET PrevIndex=".$Prev.", NextIndex=".$Next." WHERE id=". $value['id'].";";
				if($buf_i > 5000){
					sqlupd($sql_str);
					$sql_str = "";
					$buf_i = 0;
				}
				$i++;
				$buf_i++;
			}
			sqlupd($sql_str);
			$sql_str = "";
			$buf_i = 0;
		}
	}
}

function getProductionProgramByCexIdPPOid( $cex_id, $production_program_otchet_id, $user_usl){
	$production_program =
		sqltab_key("
    	    SELECT 
    	    	production_program.*, 
    	    	ppus.sequence as 'user_sequence' 
    	    FROM production_program 
    	        LEFT JOIN production_program_cex_uch ppcu on ppcu.id = production_program.cex_uch_id
    	        LEFT JOIN production_program_user_sequence ppus on ppus.user_id = production_program.user_id
    	        WHERE 
    	            ppcu.cex_id=".$cex_id." AND
    	            production_program_otchet_id=".$production_program_otchet_id . " ".  $user_usl ." 
    	        ORDER BY ppus.sequence", 'id');
	$production_program_cex_uch_id = [];
	$unfinded_indexes = [];
	foreach ($production_program as $key => $value){
		$production_program_cex_uch_id[$value['cex_uch_id']][$value['user_sequence']][$key] = $value;
	}

//поиск первого элемента
	$responseArray = [];
	foreach ($production_program_cex_uch_id as $cex_uch_id => $production_program_user_id){
		foreach ($production_program_user_id as $user_id => $production_program){
			foreach ($production_program as $value) {
				if( !isset( $production_program[$value['PrevIndex']] ) ){
					$responseArray[$cex_uch_id][$user_id]['correct'][] = $value;
					break;
				}
			}
		}
	}

//выстраивание массивов
	foreach ( $production_program_cex_uch_id as $cex_uch_id => $production_program_user_id ){
		foreach ($production_program_user_id as $user_id => $production_program){
			$i = 0;
			foreach ($production_program as $value) {
				if(
					is_array( $production_program[ $responseArray[$cex_uch_id][$user_id]['correct'][$i]['NextIndex'] ])
				){
					if( $production_program[ $responseArray[$cex_uch_id][$user_id]['correct'][$i]['NextIndex'] ]['PrevIndex'] ===
						$responseArray[$cex_uch_id][$user_id]['correct'][$i]['id']
					){
						$responseArray[$cex_uch_id][$user_id]['correct'][] = $production_program[ $responseArray[$cex_uch_id][$user_id]['correct'][$i]['NextIndex'] ];
					} else {
						$responseArray[$cex_uch_id][$user_id]['failed'][] = $production_program[ $responseArray[$cex_uch_id][$user_id]['correct'][$i]['NextIndex'] ];
					}
				} else {
					$unfinded_indexes[] = $responseArray[$cex_uch_id][$user_id]['correct'][$i]['NextIndex'];
				}
				$i++;
			}
		}
	}
	return [
		'sheet' => $responseArray,
		'unfinded_indexes' => $unfinded_indexes
	];
}

function SetUserDate( $user_id, $date_html ){
	if( sqltab("SELECT * FROM production_program_user_date WHERE user_id = ". $user_id) ){
		sqlupd("UPDATE production_program_user_date SET html_date = '".$date_html."' WHERE user_id = ". $user_id. ";");
		$msg = 'Дата Обновлена';
	} else {
		sqlupd("INSERT INTO production_program_user_date(user_id, html_date) VALUES (".$user_id.", '". $date_html ."');");
		$msg = 'Дата Создана';
	}
	return [
		'date' => $date_html,
		'msg' => $msg
	];
}

$response = new stdClass;
$indexing_status = sqltab('SELECT * FROM production_program_index_notifier ORDER BY id DESC LIMIT 1')[0];
if( $_POST['request'] !== 'SetUserDate' && $indexing_status['status'] == 'Производится группировка заказов' ){
    echo json_encode(['err' => 'Производится группировка заказов, действие отменено']);
    exit;
}
if( $_POST['request'] === 'position_index_to_links' ){
	progon();
	echo json_encode(['msg' => 'okay']);
}
if( $_POST['request'] === 'ReplaceRowPlace' ){
	if( $_POST['data']['insert']['next']['id'] === ''){
		$_POST['data']['insert']['next']['id'] = 'NULL';
	}

	if( $_POST['data']['insert']['prev']['id'] === ''){
		$_POST['data']['insert']['prev']['id'] = 'NULL';
	}

	if( $_POST['data']['eject']['prev']['id'] === ''){
		$_POST['data']['eject']['prev']['id'] = 'NULL';
	}

	if( $_POST['data']['eject']['prev']['id'] === ''){
		$_POST['data']['eject']['prev']['id'] = 'NULL';
	}

	//old remap
	sqlupd("
		UPDATE production_program set PrevIndex=". $_POST['data']['eject']['prev']['id'] ." WHERE id=". $_POST['data']['eject']['next']['id'] .";
		UPDATE production_program set NextIndex=". $_POST['data']['eject']['next']['id'] ." WHERE id=". $_POST['data']['eject']['prev']['id'] .";");

	//new remap
	sqlupd("
		UPDATE production_program set NextIndex=". $_POST['data']['insert']['next']['id'] ." WHERE id=". $_POST['data']['current']['id'] .";
		UPDATE production_program set PrevIndex=". $_POST['data']['insert']['prev']['id'] ." WHERE id=". $_POST['data']['current']['id'] .";");

	sqlupd("UPDATE production_program set NextIndex=". $_POST['data']['current']['id'] ." WHERE id=". $_POST['data']['insert']['prev']['id'] .";");
	sqlupd("UPDATE production_program set PrevIndex=". $_POST['data']['current']['id'] ." WHERE id=". $_POST['data']['insert']['next']['id'] .";");

	echo json_encode(['response' => 'OK', $_POST['data'] ]);
}
if( $_POST['request'] === 'straighten' ){
		$production_program_index_notifier = sqlupd("INSERT INTO `production_program_index_notifier`(`id`) VALUES (NULL)");
    $users = sqltab("SELECT user_id FROM production_program GROUP BY user_id");
    foreach ( sqltab("SELECT id FROM production_program_otchet") as $value ){
        foreach ($users as $user) {
            $first_index = sqltab("
                SELECT position_index FROM `production_program` 
                WHERE 
                      `user_id` = '". $user['user_id'] ."' AND 
                      `production_program_otchet_id` = '".$value['id']."'
                ORDER BY `production_program`.`position_index`
                LIMIT 1")[0]['position_index'];
            $all_rows = sqltab("
            SELECT * FROM `production_program` 
            WHERE 
                  `user_id` = '". $user['user_id'] ."' AND 
                  `production_program_otchet_id` = '".$value['id']."'
            ORDER BY `production_program`.`position_index`");
            foreach ( $all_rows as $value2 ){
                sqlupd("UPDATE production_program SET position_index='".$first_index."' WHERE id=". $value2['id']);
                $first_index++;
            }
        }
    }
		sqlupd("UPDATE production_program_index_notifier SET class='success', status='Заказы проиндексированы' WHERE id = ". $production_program_index_notifier);
    $msg = 'Переиндексация выполнена';
    echo json_encode(['msg' => $msg]);
}
if( $_POST['request'] == 'NZ_Group' ){
    $production_program_index_notifier = sqlupd("INSERT INTO `production_program_index_notifier`(`id`) VALUES (NULL)");
    $users = sqltab("SELECT user_id FROM production_program GROUP BY user_id");
    sqlupd("
        DELETE `production_program` FROM `production_program`
        WHERE 
            `production_program`.`NZ` = ''
        ");
    foreach ( sqltab("SELECT id FROM production_program_otchet") as $value ){
        foreach ($users as $user) {
            $first_index = sqltab("
                SELECT position_index FROM `production_program` 
                WHERE 
                      `user_id`                         = ". $user['user_id'] ." AND 
                      `production_program_otchet_id`    = ".$value['id']."
                ORDER BY `production_program`.`position_index`
                LIMIT 1")[0]['position_index'];
            $all_NZ = sqltab("
                SELECT * FROM `production_program` 
                WHERE 
                      `user_id` = ". $user['user_id'] ." AND 
                      `production_program_otchet_id` = ".$value['id']."
                GROUP BY `production_program`.`NZ`
                ORDER BY `production_program`.`NZ`");
            foreach ( $all_NZ as $value2 ){
                $all_rows_NZ = sqltab("
                    SELECT * FROM `production_program` 
                    WHERE 
                          `user_id`                         = ". $user['user_id']  ." AND 
                          `production_program_otchet_id`    = ". $value['id']      ." AND 
                          `NZ`                              LIKE '". $value2['NZ']     ."'
                    ORDER BY `production_program`.`NZ`");
                $sql =
                    sprintf("INSERT INTO `production_program`
                        ( `NZ`, `NAIM`, `PRIM`, `user_id`, `cex_uch_id`, `position_index`, `production_program_otchet_id` ) 
                            VALUES 
                        ( '', '', '', %s, %s, %s, %s );
                        ",
                        $all_rows_NZ[0]['user_id'],
                        $all_rows_NZ[0]['cex_uch_id'],
                        $first_index,
                        $all_rows_NZ[0]['production_program_otchet_id']
                    );
                $first_index++;
                foreach ( $all_rows_NZ as $value3 ){
                    $sql .= "UPDATE production_program SET position_index=".$first_index." WHERE id=". $value3['id'].";";
                    $first_index++;
                }
                sqlupd($sql);
            }
        }
    }
    sqlupd("UPDATE production_program_index_notifier SET class='success', status='Заказы сгруппированы' WHERE id = ". $production_program_index_notifier);
    $msg = 'Переиндексация выполнена, Заказы сгруппированы';
    echo json_encode(['msg' => $msg]);
}
if( $_POST['request'] === 'toggleVisibilityOtchet'){
    sqlupd('UPDATE production_program_otchet SET isvisible = '. $_POST['data']['new_value'] .' WHERE id = '. $_POST['data']['otch_id']);
    echo json_encode(
        [
            'err_msg' => '',
            'response' => sqltab("SELECT isvisible FROM production_program_otchet WHERE id = ". $_POST['data']['otch_id'])[0]
        ]
    );
}
if( $_POST['request'] === 'LocksManage' ){
    $err_msg = '';
    if($_POST['data']['lock_id'] != ''){
        //удаляем этот лок id
        sqlupd('DELETE FROM production_program_locks WHERE id ='. $_POST['data']['lock_id']);
        $lock_id = '';
    } else {
        $curr_otch = sqltab("SELECT ppo.* FROM production_program_otchet ppo LEFT JOIN production_program_cex ppc 
                                    ON ppc.cex_group_id = ppo.cex_group_id
                                    WHERE date = '". $_POST['data']['date'] ."-01' AND 
                                    ppc.id = ". $_POST['data']['cex_id'] .";");
        if( count( $curr_otch ) > 0){
            $lock_id =
                sqlupd('INSERT INTO `production_program_locks`(`cex_id`, `otchet_id`) VALUES 
                          ('.$_POST['data']['cex_id'].', '.$curr_otch[0]['id'].')'
                );
        } else {
          $err_msg = 'Ошибка. Проверьте блокируемый цех текущего периода на наличие записей.';
        }
    }
    echo json_encode(['ppl_id' => $lock_id, 'err_msg' => $err_msg]);
}
if( $_POST['request'] === 'SetUserDate'){
	$response = SetUserDate( $_POST['user_id'], $_POST['html_date'] );
    $locks =
	    sqltab("SELECT production_program_locks.* FROM production_program_locks 
                  LEFT JOIN production_program_otchet ppo on production_program_locks.otchet_id = ppo.id
                  WHERE ppo.date = '".$_POST['html_date']."-01'
              ");
	//todo
    echo json_encode(['response' => $response, 'locks' => $locks]);
}
if( $_POST['request'] === 'CopyMonth'){
    $incoming_date_explode = explode('-', $_POST['date']);
    $Month = [];
    $Month['this'] = new DateTime( '01-' . $incoming_date_explode[1] . '-' . $incoming_date_explode[0]);
    $Month['next'] = (clone $Month['this'])->modify('+1 month');
    $Month['this_text'] = $Month['this']->format('Y-m-d');


    $Month['next_text'] = $Month['next']->format('Y-m-d');
    $cex_group_id_count = count( sqltab('SELECT cex_group_id FROM production_program_cex GROUP BY cex_group_id') );
    $production_program_otchet_cur_mes =
        sqltab("SELECT * FROM production_program_otchet WHERE date = '". $Month['this_text']. "'");
    $production_program_otchet_next_mes =
        sqltab("SELECT * FROM production_program_otchet WHERE date = '". $Month['next_text']. "'");
	if( count($production_program_otchet_next_mes) === 0 ){
        if($cex_group_id_count === count($production_program_otchet_cur_mes)){
	        $ppo_i = 0;
            foreach ($production_program_otchet_cur_mes as $ppo_now){
				$count_ppo_i = count($production_program_otchet_cur_mes);
	            $ppo_i++;
                $next_otch_id =
	                sqlupd("
						INSERT INTO production_program_otchet 
					        (
					         	date, 
					         	cex_group_id, 
					         	PRIM
				            ) 
						    VALUES 
                          	(
	  	        				'". $Month['next_text'] ."',
	  	                        '". $ppo_now['cex_group_id'] ."',
	  	                        '". $ppo_now['PRIM'] ."'
	  	                    )
	  	        ");
	            $otchet_data =
		            sqltab("SELECT * FROM production_program WHERE production_program_otchet_id = ". $ppo_now['id'] ." AND PrevIndex IS NULL");
				$len = count($otchet_data) - 1;
				foreach ($otchet_data as $key => $value){
					$last_el = $value;
					$Prev = 'NULL';
					$i = 0;
					while ( $last_el ) {
						$Current =
							sqlupd("
								#".$key." - ".$len."
								
								INSERT INTO
                    		    	production_program(
                    		    	    id,							  NZ,						   NAIM,
                    		    	    POZ, 						  VREM, 					   OPOZ,
                    		    	    OVREM, 						  PRIM, 					   user_id,
                    		    	    DATE,						  cex_uch_id, 				   position_index,
                    		    	    production_program_otchet_id, NextIndex, 				   PrevIndex
                    		    	) VALUES (
                    		    	 	NULL,						  '".$last_el['NZ']."',       '".$last_el['NAIM']."',
                    	        	 	".$last_el['POZ'].",          ".$last_el['VREM'].",       ".$last_el['OPOZ'].",
                    	        	 	".$last_el['OVREM'].",        '".$last_el['PRIM']."',     ".$last_el['user_id'].",
                    	        	 	CURRENT_TIMESTAMP,            ".$last_el['cex_uch_id'].", ".$i.",
                    	        	 	".$next_otch_id.",            NULL,                       ".$Prev."
                            )");
						if( $Prev != 'NULL'){
							sqlupd("UPDATE production_program SET NextIndex = ".$Current." WHERE id = ".$Prev.";");
						}
						if( $last_el['NextIndex'] ){
							$last_el = sqltab("SELECT * FROM production_program WHERE id=". $last_el['NextIndex'] )[0];
						} else {
							$last_el = NULL;
						}
						$Prev = $Current;
						$i++;
					}
				}
            }
            echo json_encode(['msg' => 'Отчеты на следующий месяц перенесены']);
        } else {
            echo json_encode(['err_msg' => 'Количество готовых цехов на текущий месяц не совпадает с общим количеством цехов.']);
        }
    } else {
      echo json_encode(['err_msg' => 'Уже существуют планы на следующий месяц, если вы хотите перезаписать новый месяц данными с текущего - нажмите кнопку ниже "Очистить отчет следующего месяца" и повторите перенос']);
    }
}
if( $_POST['request'] === 'ClearMonth'){
  $Date = $_POST['date'] . '-01';
  $production_program_otchet_arr = sqltab("SELECT * FROM production_program_otchet WHERE date = '". $Date ."'");
  if(count($production_program_otchet_arr) !== 0){
      foreach ($production_program_otchet_arr as $pp){
        sqlupd("
        DELETE FROM production_program WHERE production_program_otchet_id = ". $pp['id']. ";
        DELETE FROM production_program_otchet WHERE id = ". $pp['id']. ";");
      }
    echo json_encode(['msg' => 'Отчеты следующего месяца удалены']);
  } else {
    echo json_encode(['err_msg' => 'Нет отчетов для удаления']);
  }
}
if( $_POST['request'] === 'GetDownloadLink'){
    echo json_encode(
        sqltab("SELECT 
                        ppo.id, 
                        ppcgt.title,
                        ppo.creation_date, 
                        ppo.pdflink 
                    FROM production_program_otchet ppo 
                    LEFT JOIN production_program_cex_group_title ppcgt ON ppo.cex_group_id = ppcgt.id 
                    WHERE date = '". $_POST['data']. "' AND isvisible = 1")
    );
}
if( $_POST['request'] === 'ChangePrim'){
  sqlupd("UPDATE production_program_otchet SET PRIM = '". addslashes($_POST['data']['PRIM']) ."' WHERE id = ". $_POST['data']['id']);
  echo json_encode(['PRIM' => $_POST['data']['PRIM']]);
}
if( $_POST['request'] === 'AddRow'){
    $response->id =
	    sqlupd("
			INSERT INTO `production_program`
				(
					`id`,             				`NZ`,   	`NAIM`, 
					`POZ`,            				`VREM`, 	`OPOZ`, 
					`OVREM`,          				`VPOZ`, 	`VVREM`, 
					`TOVAR`,          				`OBPOZ`,	`OBVREM`, 
					`cex_uch_id`,     				`PRIM`, 	`pr`, 	 
				 	`production_program_otchet_id`, `user_id`,	`PrevIndex`, 
				 	`NextIndex`
				) 
					VALUES 
				(
					NULL, 													'".str_replace(' ', '', $_POST['data']['NZ'])."',  '".$_POST['data']['NAIM']."',
					'".$_POST['data']['POZ']."',                            '".$_POST['data']['VREM']."',                                   '".$_POST['data']['OPOZ']."',
					'".$_POST['data']['OVREM']."',                          '".$_POST['data']['VPOZ']."',                                   '".$_POST['data']['VVREM']."',
					'".$_POST['data']['TOVAR']."',                          '".$_POST['data']['OBPOZ']."',                                  '".$_POST['data']['OBVREM']."',
					'".$_POST['data']['cex_uch_id']."',                     '".$_POST['data']['PRIM']."',                                   '".$_POST['data']['pr']."', 
					'".$_POST['data']['production_program_otchet_id']."',   ".$_POST['data']['user_id'].",                                  ".$_POST['data']['PrevIndex'].",
					".$_POST['data']['NextIndex']."
			    )
			"
    );

	if( $_POST['data']['PrevIndex'] != 'NULL' ){
		sqlupd("UPDATE production_program SET NextIndex = ". $response->id . " WHERE id= ". $_POST['data']['PrevIndex']);
		$response->PrevRow_NextIndex = $response->id;
	} else {
		$response->PrevRow_NextIndex = 'null';
	}

	if( $_POST['data']['NextIndex'] != 'NULL' ){
		sqlupd("UPDATE production_program SET PrevIndex = ". $response->id . " WHERE id= ". $_POST['data']['NextIndex']);
		$response->NextRow_PrevIndex = $response->id;
	} else {
		$response->NextRow_PrevIndex = 'null';
	}


	$response->rowdata  = sqltab("SELECT * FROM production_program WHERE id = ". $response->id)[0];
	$response->text     = 'OK';
	echo json_encode($response);
}
if( $_POST['request'] === 'DeleteFrom_production_program' ){
	$routing = sqltab("SELECT * FROM production_program WHERE id = ". $_POST['data'])[0];

	if( is_array($routing) ){
		if($routing['PrevIndex'] != ''){
			if($routing['NextIndex']){
				sqlupd("UPDATE production_program SET NextIndex = ". $routing['NextIndex'] ." WHERE id = ". $routing['PrevIndex']);
			} else {
				sqlupd("UPDATE production_program SET NextIndex = NULL WHERE id=". $routing['PrevIndex']);
			}
		}
		if($routing['NextIndex'] != ''){
			if($routing['PrevIndex']){
				sqlupd("UPDATE production_program SET PrevIndex = ". $routing['PrevIndex'] ." WHERE id = ". $routing['NextIndex']);
			} else {
				sqlupd("UPDATE production_program SET PrevIndex = NULL WHERE id = ". $routing['NextIndex']);
			}
		}
		sqlupd(
			sprintf("DELETE FROM production_program WHERE id = %s",
				$_POST['data']
			)
		);
	}
  $response->text = 'OK';
  echo json_encode($response);
}
if( $_POST['request'] === 'ChangeDate'){
    try {
        $calculated = new DateTime( '01-'. $_POST["dateInfo"]["month"]  . '-' . $_POST["dateInfo"]["year"] );
    } catch (Exception $e) {
        echo $e;
        exit;
    }
    switch ($_POST['move']){
        case 'left' :
            $calculated->modify('-1 month');
            break;
        case 'right' :
            $calculated->modify('+1 month');
            break;
    }
    $html_date    = $calculated->format('Y-m');
    $calculated   = $calculated->format('Y-m-d');
	$cex_group_id = sqltab("SELECT cex_group_id FROM production_program_cex WHERE id = ". $_POST["cex_id"])[0]['cex_group_id'];
	$production_program_otchet_id  = sqltab("
        SELECT * FROM production_program_otchet 
			WHERE 	
		date 		 = '". $calculated ."' AND 
		cex_group_id = ". $cex_group_id .";")[0]['id'];
    if( $production_program_otchet_id == '' ){
	    $production_program_otchet_id = sqlupd("INSERT INTO production_program_otchet (date, cex_group_id) VALUES ('". $calculated ."', '". $cex_group_id ."')");
    }
	$user_usl = '';
    $user_root = sqltab("SELECT root FROM production_program_user_roots WHERE user_id = ". $_POST['user_id'])[0]['root'];
	if( $user_root === 'Нормировщик' ){
		if( $_POST['show_all'] !== 'true' ){
			$user_usl = ' AND production_program.user_id =  '. $_POST['user_id'] . " ";
		}
	}
	$sheet_info                 = getProductionProgramByCexIdPPOid($_POST["cex_id"], $production_program_otchet_id, $user_usl);
	$response->sheet            = $sheet_info['sheet'];
	$response->unfinded_indexes = $sheet_info['unfinded_indexes'];
    $response->text             = 'OK';
	$response->lock_id          = false;
    if( $user_root != '' && $user_root != 'Редактор' ){
        $response->lock_id = (bool)sqltab('SELECT id FROM production_program_locks WHERE cex_id = ' . $_POST["cex_id"] . ' AND otchet_id = ' . $production_program_otchet_id)[0]['id'];
    }
	$response->timedata = SetUserDate( $_POST['user_id'], $html_date );
	$response->timedata['id'] = $production_program_otchet_id;
    echo json_encode($response);
}
if( $_POST['request'] === 'Update_production_program'){
  $data = json_decode($_POST['data']);
  $keys = sqltab("SELECT ppcu.cex_id as 'cex_id', pp.production_program_otchet_id as 'otch_id' FROM production_program pp
                          LEFT JOIN production_program_cex_uch ppcu on pp.cex_uch_id = ppcu.id
                          WHERE pp.id = ". $data->id)[0];
  $user_root = sqltab("SELECT root FROM production_program_user_roots WHERE user_id = ".  $data->user_id)[0]['root'];
  $locks = sqltab("SELECT * FROM production_program_locks WHERE cex_id = ". $keys['cex_id'] ." AND otchet_id = ". $keys['otch_id']);

  if( count($locks) == 0 || $user_root == 'Редактор' ){
      sqlupd(
          sprintf("
            UPDATE `production_program` SET
                `NZ`    = '%s', `NAIM`  = '%s' , `POZ`    = '%s',
                `VREM`  = '%s', `OPOZ`  = '%s' , `OVREM`  = '%s',
                `VPOZ`  = '%s', `VVREM` = '%s' , `TOVAR`  = '%s',
                `OBPOZ`=  '%s', `OBVREM`= '%s' , `UCH`    = '%s',
                `PRIM`  = '%s', `pr`    = '%s'
            WHERE id = '". $data->id ."'
            ",
            ( str_replace(' ', '', $data->data->NZ ) ),
            ( $data->data->NAIM ),
            ( $data->data->POZ    == '' ? 0 : $data->data->POZ    ),
            ( $data->data->VREM   == '' ? 0 : $data->data->VREM   ),
            ( $data->data->OPOZ   == '' ? 0 : $data->data->OPOZ   ),
            ( $data->data->OVREM  == '' ? 0 : $data->data->OVREM  ),
            ( $data->data->VPOZ   == '' ? 0 : $data->data->VPOZ   ),
            ( $data->data->VVREM  == '' ? 0 : $data->data->VVREM  ),
            ( $data->data->TOVAR  == '' ? 0 : $data->data->TOVAR  ),
            ( $data->data->OBPOZ  == '' ? 0 : $data->data->OBPOZ  ),
            ( $data->data->OBVREM == '' ? 0 : $data->data->OBVREM ),
            ( $data->data->UCH    == '' ? 0 : $data->data->UCH    ),
            ( $data->data->PRIM),
            ( $data->data->pr     == '' ? 0 : $data->data->pr     )
          )
      );
      $response->data = sqltab("SELECT * FROM `production_program` WHERE id = '". $data->id ."'")[0];
      $response->text = 'OK';
  } else {
      $response->err_text = 'Таблица заблокирована';
  }
  echo json_encode($response);
}
if( $_POST['request'] === 'RaschZak'){
	$result = [];
	if( $_POST['data']['cex_uch_id'] ){
	    $result['cex_id'] = sqltab("SELECT cex_id FROM production_program_cex_uch WHERE id = ". $_POST['data']['cex_uch_id'] . " ORDER BY sequence_uch")[0]['cex_id'];
	} else {
	    $result['cex_id'] = $_POST['data']['cex_id'];
	}
	if($_POST['data']['range'] === 'zak'){
	    $result =
	        sqltab("
	    		SELECT 
	    		    pp.NZ           as ppNZ,
	    		    sum(pp.POZ)     as ppPOZ,
	    		    sum(pp.VREM)    as ppVREM,
	    		    sum(pp.OPOZ)    as ppOPOZ,
	    		    sum(pp.OVREM)   as ppOVREM,
	    		    sum(pp.VPOZ)    as ppVPOZ,
	    		    sum(pp.TOVAR)   as ppTOVAR,
	    		    sum(pp.OBPOZ)   as ppOBPOZ,
	    		    sum(pp.OBVREM)  as ppOBVREM,
	    		    sum(pp.VVREM)   as ppVVREM
	    		FROM production_program pp
	    		    LEFT JOIN production_program_cex_uch ppcu on ppcu.id = pp.cex_uch_id
	    		    LEFT JOIN production_program_otchet ppo on pp.production_program_otchet_id = ppo.id
	    		WHERE 
	    		  ppcu.id = ".  $_POST['data']['cex_uch_id']                   ."   AND 
	              ppo.id  = '". $_POST['data']['production_program_otchet_id'] ."'  AND 
	              pp.NZ   = '". $_POST['data']['zak_code']                     ."'  GROUP BY pp.NZ");
	}
    if($_POST['data']['range'] === 'cex'){
        $result =
            sqltab("
                SELECT 
                    pp.NZ           as ppNZ,
                    sum(pp.POZ)     as ppPOZ,
                    sum(pp.VREM)    as ppVREM,
                    sum(pp.OPOZ)    as ppOPOZ,
                    sum(pp.OVREM)   as ppOVREM,
                    sum(pp.VPOZ)    as ppVPOZ,
                    sum(pp.TOVAR)   as ppTOVAR,
                    sum(pp.OBPOZ)   as ppOBPOZ,
                    sum(pp.OBVREM)  as ppOBVREM,
                    sum(pp.VVREM)   as ppVVREM
                FROM production_program pp
                    LEFT JOIN production_program_cex_uch ppcu on ppcu.id = pp.cex_uch_id
                    LEFT JOIN production_program_otchet ppo on pp.production_program_otchet_id = ppo.id
                WHERE 
                    ppcu.cex_id = ". $_POST['data']['cex_id'] ." AND
                    ppo.id      = ". $_POST['data']['production_program_otchet_id'] ." 
                    GROUP BY pp.NZ");
    }
    if($_POST['data']['range'] === 'val'){
        $result =
            sqltab("
                SELECT 
                    ppc.code        as ppcCode,
                    sum(pp.POZ)     as ppPOZ,
                    sum(pp.VREM)    as ppVREM,
                    sum(pp.OPOZ)    as ppOPOZ,
                    sum(pp.OVREM)   as ppOVREM,
                    sum(pp.VPOZ)    as ppVPOZ,
                    sum(pp.TOVAR)   as ppTOVAR,
                    sum(pp.OBPOZ)   as ppOBPOZ,
                    sum(pp.OBVREM)  as ppOBVREM,
                    sum(pp.VVREM)   as ppVVREM
                FROM production_program pp
                    LEFT JOIN production_program_cex_uch ppcu on ppcu.id = pp.cex_uch_id
                    LEFT JOIN production_program_uch ppu on ppu.id = ppcu.uch_id
                    LEFT JOIN production_program_cex ppc on ppcu.cex_id = ppc.id
                    LEFT JOIN production_program_otchet ppo on pp.production_program_otchet_id = ppo.id
                WHERE 
                    ppcu.cex_id = ". $_POST['data']['cex_id'] ." AND
                    ppo.id      = ". $_POST['data']['production_program_otchet_id'] ." 
                GROUP BY ppcu.cex_id
            ");
    }
    if($_POST['data']['range'] === 'uch'){
        $result_buf =
            sqltab("
            	SELECT 
					pp.NZ        	as NZ,
					ppc.code        as ppcCode,
					ppu.title       as ppuTitle,
					ppu.id       	as ppuid,
					sum(pp.POZ)     as ppPOZ,
					sum(pp.VREM)    as ppVREM,
					sum(pp.OPOZ)    as ppOPOZ,
					sum(pp.OVREM)   as ppOVREM,
					sum(pp.VPOZ)    as ppVPOZ,
					sum(pp.TOVAR)   as ppTOVAR,
					sum(pp.OBPOZ)   as ppOBPOZ,
					sum(pp.OBVREM)  as ppOBVREM,
					sum(pp.VVREM)   as ppVVREM
				FROM production_program pp
					LEFT JOIN production_program_cex_uch 	ppcu 	on ppcu.id = pp.cex_uch_id
					LEFT JOIN production_program_uch 		ppu 	on ppu.id = ppcu.uch_id
					LEFT JOIN production_program_otchet 	ppo 	on pp.production_program_otchet_id = ppo.id
					LEFT JOIN production_program_cex 		ppc 	on ppcu.cex_id = ppc.id
				WHERE 
					ppc.id = ". $_POST['data']['cex_id'] ." AND
					ppo.id = ". $_POST['data']['production_program_otchet_id']. " 
				GROUP BY ppu.id, pp.NZ
				ORDER BY ppcu.sequence_uch
				;");
	    $result = array();
		foreach ($result_buf as $value){
			$result[ $value['ppuid'] ][] = $value;
		}
    }
    echo json_encode( array_values($result) );
}
if( $_POST['request'] === 'MakePlan'){
	error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
	require( $_SERVER['DOCUMENT_ROOT'].'/tfpdf/tfpdf.php');
	define("FPDF_FONTPATH", $_SERVER['DOCUMENT_ROOT']. "/tfpdf/font");
	$MonthsRus  = [
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря'
	];
	$MonthsRus2 = [
		1 => 'Январь',
		2 => 'Февраль',
		3 => 'Март',
		4 => 'Апрель',
		5 => 'Май',
		6 => 'Июнь',
		7 => 'Июль',
		8 => 'Август',
		9 => 'Сентябрь',
		10 => 'Октябрь',
		11 => 'Ноябрь',
		12 => 'Декабрь'
	];
	define("lineHeight", 5.5); //5.5

	function makeHeader(&$pdf, $header){
		$pdf->SetTopMargin(15);
		$pdf->SetLeftMargin(15);
		$pdf->SetAutoPageBreak(1, 10);

		//$pdf->AddFont('cour', '', 'cour.ttf', true);
		//$pdf->AddFont('cour', 'B', 'cour.ttf', true);
		$pdf->AddFont('cour','B','DejaVuSans.ttf',true);
		$pdf->SetFont('cour','',11);

		$pdf->SetFont('cour', '', 16);
		$pdf->AddPage();
		$pdf->Cell(120, 9.8, "УТВЕРЖДАЮ", 0, 0, 'C');
		$pdf->SetFont('cour', '', 18);
		$pdf->Cell(240, 9.8, "ПРОИЗВОДСТВЕННАЯ  ПРОГРАММА   ". $header['production_program_cex_group_title'] ."    на  ". $header['date'], 0, 1, 'L');
		$pdf->SetFont('cour', '', 14);
		$pdf->Cell(120, 9.8, "Директор по производству", 0, 1, 'C');

		$pdf->SetFont('cour', '', 12);
		$pdf->Cell(20, 9.8, "", 0, 0, 'C');
		$pdf->Cell(80, 9.8, "", "B", 0, 'C');
		$pdf->Cell(20, 9.8, "", 0, 0, 'C');
		$pdf->Cell(58, 9.8, "Валовая продукция", 0, 0, 'L');
		$pdf->SetFont('cour', '', 16);
		$pdf->Cell(15, 9.8, $header['vp'], 0, 0, 'R');
		$pdf->SetFont('cour', '', 12);
		$pdf->Cell(12, 9.8, " н/ч", 0, 0, 'L');
		$pdf->Cell(58, 9.8, "Товарная продукция", 0, 0, 'L');
		$pdf->SetFont('cour', '', 16);
		$pdf->Cell(15, 9.8, $header['tp'], 0, 0, 'R');
		$pdf->SetFont('cour', '', 12);
		$pdf->Cell(12, 9.8, " н/ч", 0, 1, 'L');

		$pdf->SetFont('cour', '', 14);
		$pdf->Cell(120, 9.8, "(Р.Ф. ЗОТОВ)", 0, 0, 'C');
		$pdf->SetFont('cour', '', 13);
		$pdf->Cell(90, 9.8, "Номенклатурное задание в позициях", 0, 0, 'L');
		$pdf->SetFont('cour', '', 16);
		$pdf->Cell(30, 9.8, $header['nzp'], 0, 0, 'R');
		$pdf->SetFont('cour', '', 13);
		$pdf->Cell(12, 9.8, " на", 0, 0, 'L');
		$pdf->SetFont('cour', '', 16);
		$pdf->Cell(30, 9.8, $header['nzh'], 0, 0, 'R');
		$pdf->SetFont('cour', '', 13);
		$pdf->Cell(12, 9.8, " н/ч", 0, 1, 'L');
	}
	function makeFooter(&$pdf, $footer, $last = false){
		if (!$last){
			$pdf->Cell(20,  9.8, '     ', 'T', 0, 'C');
			$pdf->Cell(50, 9.8, $footer['date'] , '', 0, 'C');
			$pdf->Cell(220, 9.8, $footer['cex'] . ', ' . $footer['uch'],  '', 0, 'C');
			$text = "Лист ".$pdf->PageNo() . ' из ' . '{nb}';
			$pdf->Cell(75, 9.8, $text,  '', 1, 'C');
		} else {
			$pdf->SetY(-30);
			$pdf->SetFont('cour','',11);
			$pdf->Cell(20,  9.8, '     ', 'T', 0, 'C');
			$pdf->Cell(50, 9.8, $footer['date'], 'T', 0, 'C');
			$pdf->Cell(220, 9.8, $footer['cex'] ,  'T', 0, 'C');
			$text = "Лист ".$pdf->PageNo() . ' из ' . '{nb}';
			$pdf->Cell(100, 9.8, $text,  'T', 1, 'C');
		}
	}
	function makeHeader2(&$pdf){
		$pdf->SetFont('cour', '', 12);

		$pdf->Cell(20,  lineHeight, "N", 'LT', 0, 'C');
		$pdf->Cell(100, lineHeight, "Номенклатурное", 'LT', 0, 'C');
		$pdf->Cell(25,  lineHeight, "К-во", "LT", 0, 'C');
		$pdf->Cell(30,  lineHeight, "Норма", "LT", 0, 'C');
		$pdf->Cell(40,  lineHeight, "Остав. объем", "LT", 0, 'C');
		$pdf->Cell(40,  lineHeight, "Валовая", "LT", 0, 'C');
		$pdf->Cell(40,  lineHeight, "Товар. прод.", "LT", 0, 'C');
		$pdf->Cell(40,  lineHeight, "Обязательная", "LT", 0, 'C');
		$pdf->Cell(30,  lineHeight, "", "LTR", 1, 'C');

		$pdf->Cell(20,  lineHeight, "", 'L', 0, 'C');
		$pdf->Cell(100, lineHeight, "", 'L', 0, 'C');
		$pdf->Cell(25,  lineHeight, "позиций", "L", 0, 'C');
		$pdf->Cell(30,  lineHeight, "времени", "L", 0, 'C');
		$pdf->Cell(40,  lineHeight, "на", "LB", 0, 'C');
		$pdf->Cell(40,  lineHeight, "продукция", "LB", 0, 'C');
		$pdf->Cell(40,  lineHeight, "объем", "LB", 0, 'C');
		$pdf->Cell(40,  lineHeight, "номенклатура", "LB", 0, 'C');
		$pdf->Cell(30,  lineHeight, "Примечание", "LR", 1, 'C');

		$pdf->Cell(20,  lineHeight, "заказа", 'LB', 0, 'C');
		$pdf->Cell(100, lineHeight, "задание", 'LB', 0, 'C');
		$pdf->Cell(25,  lineHeight, "", "LB", 0, 'C');
		$pdf->Cell(30,  lineHeight, "на партию", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "кол.поз.", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "н/час", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "% продв", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "н/час", "TB", 0, 'C');
		$pdf->Cell(40,  lineHeight, "н/ч (поз)", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "кол.поз.", "LB", 0, 'C');
		$pdf->Cell(20,  lineHeight, "н/час", "LB", 0, 'C');
		$pdf->Cell(30,  lineHeight, "", "LRB", 1, 'C');
	}
	function DrawEmptyRow(&$pdf){
		$pdf->Cell(20,  lineHeight, "",   '', 0, 'C');
		$pdf->Cell(345, lineHeight, '', "B", 1, 'C');
	}
	function DrawRowZak(&$pdf, $value, $first, $footer){
		//$value['NAIM'] = preg_replace('/\s+/', ' ', $value['NAIM']);
		if($value['NAIM'][0] == ' '){
			$value['NAIM'] = substr($value['NAIM'], 1);
		}
		$value['NAIM'] =  '     ' . str_replace("Р","P", $value['NAIM']);
		if($first){
			check_i($pdf, $footer);
			DrawEmptyRow($pdf);
			check_i($pdf, $footer);
			$pdf->Cell(20,  lineHeight , $value['NZ'],      '',   0, 'C');
			$pdf->Cell(100, lineHeight , $value['NAIM'] ,   'B',  0, 'L');
			$pdf->Cell(25,  lineHeight , $value['POZ']    == 0 ? '' : $value['POZ']   , 'B',  0, 'C');
			$pdf->Cell(30,  lineHeight , $value['VREM']   == 0 ? '' : $value['VREM']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OPOZ']   == 0 ? '' : $value['OPOZ']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OVREM']  == 0 ? '' : $value['OVREM'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['VPOZ']   == 0 ? '' : $value['VPOZ']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['VVREM']  == 0 ? '' : $value['VVREM'] , 'B',  0, 'C');
			$pdf->Cell(40,  lineHeight , $value['TOVAR']  == 0 ? '' : $value['TOVAR'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OBPOZ']  == 0 ? '' : $value['OBPOZ'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OBVREM'] == 0 ? '' : $value['OBVREM'], 'B',  0, 'C');
			$pdf->Cell(30,  lineHeight , $value['PRIM'],   'B',  1, 'C');
		} else {
			check_i($pdf, $footer);
			$pdf->Cell(20,  lineHeight , '',              '',   0, 'C');
			$pdf->Cell(100, lineHeight , $value['NAIM']		,  'B',  0, 'L');
			$pdf->Cell(25,  lineHeight , $value['POZ']    == 0 ? '' : $value['POZ']   , 'B',  0, 'C');
			$pdf->Cell(30,  lineHeight , $value['VREM']   == 0 ? '' : $value['VREM']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OPOZ']   == 0 ? '' : $value['OPOZ']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OVREM']  == 0 ? '' : $value['OVREM'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['VPOZ']   == 0 ? '' : $value['VPOZ']  , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['VVREM']  == 0 ? '' : $value['VVREM'] , 'B',  0, 'C');
			$pdf->Cell(40,  lineHeight , $value['TOVAR']  == 0 ? '' : $value['TOVAR'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OBPOZ']  == 0 ? '' : $value['OBPOZ'] , 'B',  0, 'C');
			$pdf->Cell(20,  lineHeight , $value['OBVREM'] == 0 ? '' : $value['OBVREM'], 'B',  0, 'C');
			$pdf->Cell(30,  lineHeight , $value['PRIM'],   'B',  1, 'C');
		}
	}
	function DrawRowItog(&$pdf, $value, $footer){
		check_i($pdf, $footer);
		DrawEmptyRow($pdf);

		check_i($pdf, $footer);
		$pdf->Cell(20,  lineHeight, '            ',   0, 'L');
		$pdf->Cell(100, lineHeight, $value['title'] , 'B',  0, 'C');
		$pdf->Cell(25,  lineHeight, $value['POZ']   , 'B',  0, 'C');
		$pdf->Cell(30,  lineHeight, $value['VREM']  , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight, $value['OPOZ']  , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight, $value['OVREM'] , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight , $value['VPOZ']   == 0 ? '' : $value['VPOZ']  , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight , $value['VVREM']  == 0 ? '' : $value['VVREM'] , 'B',  0, 'C');
		$pdf->Cell(40,  lineHeight , $value['TOVAR']  == 0 ? '' : $value['TOVAR'] , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight , $value['OBPOZ']  == 0 ? '' : $value['OBPOZ'] , 'B',  0, 'C');
		$pdf->Cell(20,  lineHeight , $value['OBVREM'] == 0 ? '' : $value['OBVREM'], 'B',  0, 'C');

		$pdf->Cell(30,  lineHeight, $value['prim']  , 'B',  1, 'C');

		check_i($pdf, $footer);
		DrawEmptyRow($pdf);
	}
	function DrawRukovodstvo(&$pdf){
		$pdf->SetFont('cour', '', 16);
		$pdf->Cell(0, '15', 'Начальник планово-диспетчерского отдела                                                             А. А. Тучков' , '',  1, 'L');
		$pdf->Cell(0, '15', '' , '',  1, 'C');
		$pdf->Cell(0, '15', 'Главный диспетчер                                                                                                     И. Ю. Сидорин' , '',  1, 'L');
		$pdf->Cell(0, '15', '' , '',  1, 'C');
	}
	function DrawPRIM(&$pdf, $value){
		$pdf->AddPage('L', 'A3');
		$pdf->MultiCell(0, '8', $value, 0,  'C');
		$pdf->Cell(0, '15', '', '', 1);
	}

	$i          = 0;
	$i_first    = 0;
	$firstUch   = true;
	function check_i(&$pdf, $footer, $plus = 0){
		global $i, $i_first;
		if($i_first == 37){
			$i = 0;
			makeFooter($pdf, $footer);
			makeHeader2($pdf);
		}
		if( ($i % 44 == 0 || $i > 44) && $i != 0){
			$i = 0;
			makeFooter($pdf, $footer);
			makeHeader2($pdf);
		}
		if($plus == 0){
			$i++;
			$i_first++;
		} else {
			$i += $plus;
			$i_first += $plus;
		}
	}
	function DrawRowUch(&$pdf, $text, &$footer){
		$footer['uch'] = $text;
		global $firstUch;
		check_i($pdf, $footer);
		DrawEmptyRow($pdf);

		check_i($pdf, $footer);
		$pdf->Cell(20,  lineHeight, '' ,      '',  0, 'C');
		$pdf->Cell(300, lineHeight, $text ,   'B',  0, 'L');
		if(!$firstUch){
			$pdf->Cell(45,  lineHeight, '',   'B',  1, 'L', true);
		} else {
			$pdf->Cell(45,  lineHeight, '',   'B',  1, 'L');
			$firstUch = false;
		}

		check_i($pdf, $footer);
		DrawEmptyRow($pdf);
	}
	function makePlan(&$pdf, $header, $body, $footer){
		makeHeader($pdf, $header);
		makeHeader2($pdf);
		foreach ($body as $uch_sequence => $uch){
			DrawRowUch($pdf, $uch['title'], $footer);
			foreach ($uch['data'] as $user_sequence_key => $userData){
				foreach ($userData as $zak => $zak_body){
					foreach ($zak_body['zakaz'] as $key => $value) {
						if($key == 0){
							DrawRowZak($pdf, $value, true, $footer);
						} else {
							DrawRowZak($pdf, $value, false, $footer);
						}
					}
					if( count($zak_body['zakaz']) > 1 ){
						DrawRowItog($pdf, $zak_body['zak_sum'], $footer);
					}
				}
			}
			DrawRowItog($pdf, $uch['uch_sum'], $footer);
		}
		DrawRowItog($pdf, $footer['cex_sum'], $footer);
		makeFooter($pdf, $footer);
		DrawPRIM($pdf, $footer['PRIM']);
		DrawRukovodstvo($pdf);
		makeFooter($pdf, $footer, true);
		return $pdf;
	}
	function GetPlanData($production_program_otchet_id, $cex_group_id){
		setupPositionIndexes($production_program_otchet_id, $cex_group_id);
		$sql = sqltab("
			SELECT 
			    pp.id							as 'id',
			    pp.NZ							as 'NZ',
			    pp.NAIM							as 'NAIM',
			    pp.POZ							as 'POZ',
			    pp.VREM							as 'VREM',
			    pp.OPOZ							as 'OPOZ',
			    pp.OVREM						as 'OVREM',
			    pp.VPOZ							as 'VPOZ',
			    pp.VVREM						as 'VVREM',
			    pp.TOVAR						as 'TOVAR',
			    pp.OBPOZ						as 'OBPOZ',
			    pp.OBVREM						as 'OBVREM',
			    pp.UCH							as 'UCH',
			    pp.PRIM							as 'PRIM',
			    pp.pr							as 'pr',
			    pp.DATE							as 'DATE',
			    pp.cex_uch_id					as 'cex_uch_id',
			    pp.production_program_otchet_id as 'production_program_otchet_id',
			    pp.user_id						as 'user_id',
			    pp.position_index				as 'position_index',
			    ppu.title						as 'uch_title',
			    ppcu.sequence_uch				as 'uch_sequence',
			    ppus.sequence					as 'user_sequence',
			    ppo.date 						as 'ppo_date',
				ppcgt.title 					as 'production_program_cex_group_title'
			FROM production_program pp
				LEFT JOIN production_program_otchet 		 ppo   ON pp.production_program_otchet_id = ppo.id 
				LEFT JOIN production_program_cex_uch 		 ppcu  ON pp.cex_uch_id = ppcu.id 
				LEFT JOIN production_program_uch 			 ppu   ON ppcu.uch_id = ppu.id 
				LEFT JOIN production_program_cex 			 ppc   ON ppcu.cex_id = ppc.id
				LEFT JOIN production_program_cex_group_title ppcgt ON ppc.cex_group_id = ppcgt.id
				LEFT JOIN production_program_user_sequence 	 ppus on pp.user_id = ppus.user_id
				WHERE 
					ppo.cex_group_id = ". $cex_group_id ." AND 
					ppo.id           = ". $production_program_otchet_id ." 
			ORDER BY position_index"
		);
		global $MonthsRus2;
		$sqlpdf = [];
		$date_explode = explode('-', $sql[0]['ppo_date']);
		$sqlpdf['secondary']['header']['date_otch']	= $MonthsRus2[ (int)$date_explode[1] ] . ' ' . $date_explode[0] . 'г';
		if( (int)$date_explode[1] == 1 ){
			$sqlpdf['secondary']['footer']['date'] = $MonthsRus2[ 12 ] . ' ' . ( (int)$date_explode[0] - 1 ) . 'г.';
		} else {
			$sqlpdf['secondary']['footer']['date'] = $MonthsRus2[ (int)$date_explode[1] - 1 ] . ' ' . $date_explode[0] . 'г.';
		}
		$cex_group_title                            = sqltab("SELECT title from production_program_cex_group_title WHERE id=". $cex_group_id)[0]['title'];
		$sqlpdf['secondary']['footer']['cex_short'] = $cex_group_title;
		$sqlpdf['secondary']['footer']['cex']       = 'Производственная программа '. $cex_group_title;
		$sqlpdf['secondary']['footer']['PRIM']      = sqlsingle("SELECT PRIM FROM production_program_otchet WHERE id=". $production_program_otchet_id)['PRIM'];
		foreach ($sql as $value){
			if($value['NZ'] != '' && $value['NZ'] != '0'){
				$sqlpdf['primary'][ $value['uch_sequence'] ]['data'][ $value['user_sequence'] ][ $value['NZ'] ]['zakaz'][] = $value;
			}
			if( !isset($sqlpdf['primary'][ $value['uch_sequence'] ]['title']) ){
				$sqlpdf['primary'][ $value['uch_sequence'] ]['title'] = $value['uch_title'];
			}
		}

		$allSums    = [
			'vp' =>   0,
			'tp' =>   0,
			'nzp' =>  0,
			'nzh' =>  0
		];
		$sums_cex   = [
			'title' => 'Итого по цеху',
			'POZ'   => 0,
			'VREM'  => 0,
			'OPOZ'  => 0,
			'OVREM' => 0,
			'VPOZ'  => 0,
			'VVREM' => 0,
			'TOVAR' => 0,
			'OBPOZ' => 0,
			'OBVREM'=> 0
		];
		//Сортировка по участкам
		ksort($sqlpdf['primary']);
		foreach ($sqlpdf['primary'] as $uch_sequence => &$uch){
			$sums_uch = [
				'title' => 'Итого по участку',
				'POZ'   => 0,
				'VREM'  => 0,
				'OPOZ'  => 0,
				'OVREM' => 0,
				'VPOZ'  => 0,
				'VVREM' => 0,
				'TOVAR' => 0,
				'OBPOZ' => 0,
				'OBVREM'=> 0
			];

			//Сортировка по пользователям
			ksort($uch['data']);
			foreach ($uch['data'] as $user_sequence => &$user){
				foreach ($user as $user_sequence_key => &$zakazi){
					$sums_zak = [
						'title' => 'Итого по заказу',
						'POZ'   => 0,
						'VREM'  => 0,
						'OPOZ'  => 0,
						'OVREM' => 0,
						'VPOZ'  => 0,
						'VVREM' => 0,
						'TOVAR' => 0,
						'OBPOZ' => 0,
						'OBVREM'=> 0
					];
					foreach ($zakazi['zakaz'] as $n_zak => &$zakaz){
						$allSums['vp']      += (int)$zakaz['VVREM'];
						$allSums['tp']      += (int)$zakaz['TOVAR'];
						$allSums['nzp']     += (int)$zakaz['OBPOZ'];
						$allSums['nzh']     += (int)$zakaz['OBVREM'];

						$sums_zak['POZ']    += (int)$zakaz['POZ'];
						$sums_zak['VREM']   += (int)$zakaz['VREM'];
						$sums_zak['OPOZ']   += (int)$zakaz['OPOZ'];
						$sums_zak['OVREM']  += (int)$zakaz['OVREM'];
						$sums_zak['VPOZ']   += (int)$zakaz['VPOZ'];
						$sums_zak['VVREM']  += (int)$zakaz['VVREM'];
						$sums_zak['TOVAR']  += (int)$zakaz['TOVAR'];
						$sums_zak['OBPOZ']  += (int)$zakaz['OBPOZ'];
						$sums_zak['OBVREM'] += (int)$zakaz['OBVREM'];

						$sums_uch['POZ']    += (int)$zakaz['POZ'];
						$sums_uch['VREM']   += (int)$zakaz['VREM'];
						$sums_uch['OPOZ']   += (int)$zakaz['OPOZ'];
						$sums_uch['OVREM']  += (int)$zakaz['OVREM'];
						$sums_uch['VPOZ']   += (int)$zakaz['VPOZ'];
						$sums_uch['VVREM']  += (int)$zakaz['VVREM'];
						$sums_uch['TOVAR']  += (int)$zakaz['TOVAR'];
						$sums_uch['OBPOZ']  += (int)$zakaz['OBPOZ'];
						$sums_uch['OBVREM'] += (int)$zakaz['OBVREM'];

						$sums_cex['POZ']    += (int)$zakaz['POZ'];
						$sums_cex['VREM']   += (int)$zakaz['VREM'];
						$sums_cex['OPOZ']   += (int)$zakaz['OPOZ'];
						$sums_cex['OVREM']  += (int)$zakaz['OVREM'];
						$sums_cex['VPOZ']   += (int)$zakaz['VPOZ'];
						$sums_cex['VVREM']  += (int)$zakaz['VVREM'];
						$sums_cex['TOVAR']  += (int)$zakaz['TOVAR'];
						$sums_cex['OBPOZ']  += (int)$zakaz['OBPOZ'];
						$sums_cex['OBVREM'] += (int)$zakaz['OBVREM'];
					}
					$sums_zak['VPOZ'] = round( ( ($sums_zak['VREM'] - $sums_zak['OVREM'] + $sums_zak['VVREM'] ) / $sums_zak['VREM'] ) * 100 );

					if( is_infinite($sums_zak['VPOZ']) || is_nan($sums_zak['VPOZ']) ){
						$sums_zak['VPOZ'] = 0;
					}
					$zakazi['zak_sum'] = $sums_zak;
				}
			}
			$sums_uch['VPOZ'] =  round( ( ($sums_uch['VREM'] - $sums_uch['OVREM'] + $sums_uch['VVREM']) / $sums_uch['VREM']) * 100);
			if( is_infinite($sums_uch['VPOZ']) || is_nan($sums_zak['VPOZ']) ){
				$sums_uch['VPOZ'] = 0;
			}
			$uch['uch_sum'] = $sums_uch;
		}
		$sqlpdf['secondary']['footer']['cex_sum']           = $sums_cex;
		$sqlpdf['secondary']['footer']['cex_sum']['VPOZ']   = round( ( ($sums_uch['VREM'] - $sums_uch['OVREM'] + $sums_uch['VVREM']) / $sums_uch['VREM']) * 100 );
		if( is_infinite($sqlpdf['secondary']['footer']['cex_sum']['VPOZ']) ){
			$sqlpdf['secondary']['footer']['cex_sum']['VPOZ'] = 0;
		}
		$sqlpdf['secondary']['header']['sums'] = $allSums;
		return $sqlpdf;
	}
	function setupPositionIndexes($production_program_otchet_id, $cex_group_id){
		$sql_text ="
		    SELECT pp.* 
		    FROM production_program pp
		    	LEFT JOIN production_program_otchet  		ppo  on pp.production_program_otchet_id = ppo.id
		    	LEFT JOIN production_program_cex_uch 		ppcu on pp.cex_uch_id = ppcu.id
		    	LEFT JOIN production_program_cex     		ppc  on ppcu.cex_id = ppc.id
		        LEFT JOIN production_program_user_sequence 	ppus on pp.user_id = ppus.user_id
		    WHERE 
		        PrevIndex IS NULL AND
                production_program_otchet_id=". $production_program_otchet_id ." AND
                ppo.cex_group_id=". $cex_group_id." 
            ORDER BY 
                ppc.sequence,
                ppcu.sequence_uch,
                ppus.sequence
            ";
		$ppo_data = sqltab($sql_text);
		$i = 0;
		foreach ($ppo_data as $ppo_by_user_id) {
			$row = $ppo_by_user_id;
			while( $row ) {
				if( $row['NZ'] != '' ){
					sqlupd("UPDATE production_program SET position_index=".$i." WHERE id=". $row['id']);
				} else {
					sqlupd("UPDATE production_program SET position_index=100000 WHERE id=". $row['id']);
				}
				$i++;
				if( $row['NextIndex'] ){
					$row = sqltab("SELECT * FROM production_program WHERE id=". $row['NextIndex'])[0];
				} else {
					$row = NULL;
				}
			} ;
		}
	}
	$sqlpdf = GetPlanData($_POST['ids']['production_program_otchet_id'], $_POST['ids']['cex_group_id']);
	$pdf    = new tFPDF("L", "mm", "A3");
	$header = makePlan(
		$pdf,
		[
			'production_program_cex_group_title' => $sqlpdf['secondary']['footer']['cex_short'],
			'vp'                                 => $sqlpdf['secondary']['header']['sums']['vp'],
			'tp'                                 => $sqlpdf['secondary']['header']['sums']['tp'],
			'nzp'                                => $sqlpdf['secondary']['header']['sums']['nzp'],
			'date'                               => $sqlpdf['secondary']['header']['date_otch'] . '.',
			'nzh'                                => $sqlpdf['secondary']['header']['sums']['nzh']
		],
		$sqlpdf['primary'],
		$sqlpdf['secondary']['footer']
	);
	$pdf->AliasNbPages();
	$file_name      = $sqlpdf['secondary']['footer']['cex_short'] . ' - План на '.  $sqlpdf['secondary']['header']['date_otch'] .'.pdf';
	$path           = _ROOT . 'documents/production_program/';
	$fullfilename   = $path . $file_name;
	$pdf->Output( utftocp($fullfilename) );
	$pdflink = "http://". $_SERVER['HTTP_HOST'] . '/documents/production_program/' . $file_name;
	sqlupd("UPDATE production_program_otchet SET pdflink = '".$pdflink."', creation_date = now() WHERE id = ". $_POST['ids']['production_program_otchet_id']);

	$button = "
		<a 
			type='button'
			download
			href=''
			class='glyphicon glyphicon-download btn btn-success'></a>";
	echo json_encode(['link' => $pdflink]);
}
?>