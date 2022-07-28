<?php
define("_INC", 1);
global $db;
include ("../cmsconf.php");
sql_connect();

//Требуется для функции get_kod
$auth_status["page"]["id"]  = '0';
$auth_status["id"]          = '0';
//

if( count($_POST) == 0 ){
	$raw_input = file_get_contents('php://input');
	if( $json_input = json_decode($raw_input, true) ){
		$_POST = $json_input;
	}
}
if (isset($_POST['request']) || isset($_POST['requestArr'])){
	if ( isset($_POST['requestArr']) && count($_POST['requestArr']) > 0 ){
		$requestArr = $_POST['requestArr'];
	} else {
		$requestArr = array( 0 => $_POST['request']);
	}
	foreach ($requestArr as $key => $query){
		if($query == 'inventory_of_equipment'){
			$response = [
				'err' => [],
				'info' => [
					'equipment' => [
						'title_conflict' => [],
						'not_exist' => []
					],
					'product' => []
				],
				'actions' => []
			];
			$workArray = [];

			$storages = sqltab_key('SELECT * FROM equipment_storages', 'title');
			require_once ('../phpExcel/Classes/PHPExcel.php');
			$path = $_SERVER['DOCUMENT_ROOT']. "\\" . $_POST['file'];
			$fileObj = \PHPExcel_IOFactory::load( $path );
			$sheetObj = $fileObj->getActiveSheet()->toArray(null,true,true,true);
			array_splice($sheetObj,0,3);
			foreach ( $sheetObj as &$value ){
				$value['A'] = str_replace(' ', '', $value['A']);
				$value['B'] = str_replace('  ', ' ', $value['B']);

			}
			unset($value);
			foreach ( $sheetObj as $key2 => $value ){
				if( $value['A'] == '') continue;
				if( !$storages[ $value['C'] ] ){
					$response['err'][] = 'Ошибка: не существует склада "'. $value['C'] .'" на строке '. ( (int)$key2 + 2 );
				}

				$workArray[ $key2 ]['code']         = $value['A'];
				$workArray[ $key2 ]['storage_id']   = $storages[ $value['C'] ]['id'];
				$workArray[ $key2 ]['count']        = (int)$value['D'];
				$workArray[ $key2 ]['room']         = $value['E'] ?: '';
				$workArray[ $key2 ]['rack']         = $value['F'] ?: '';
				$workArray[ $key2 ]['shelf']        = $value['G'] ?: '';
				$workArray[ $key2 ]['cell']         = $value['H'] ?: '';
				$workArray[ $key2 ]['place']        = $value['I'] ?: '';
				$workArray[ $key2 ]['note']         = $value['K'] ?: '';
				$workArray[ $key2 ]['products']     = [];


				$equipment = sqltab("SELECT * FROM equipment WHERE code = '". $value['A'] ."';")[0];
				if( !$equipment['id'] ){
					$response['info']['equipment']['not_exist'][] = $value['A'];
					$workArray[ $key2 ]['id'] = null;
					$workArray[ $key2 ]['new_title'] = $value['B'];
				} else {
					$workArray[ $key2 ]['id'] = $equipment['id'];
					if( $equipment['title'] != $value['B'] ){
						$selected = ( strlen($equipment['title']) > strlen($value['B']) ) ? $equipment['title'] : $value['B'];
						$response['info']['equipment']['title_conflict'][] =
							[
								'id'    => $equipment['id'],
								'DB'    => $equipment['title'],
								'Excel' => $value['B'],
								'SELECTED' => $selected
							];
						$workArray[ $key2 ]['new_title'] = $selected;
					}
				}

				if( $value['J'] ){ //Перебор изделий, в которых используется оснастка
					$products_in_cell = explode(';', $value['J'] );
					foreach ($products_in_cell as &$product_in_cell_row) { //Перебор изделий построчно
						$product_in_cell_row = str_replace("\n", '', $product_in_cell_row);
						$product_id = sqltab("SELECT id FROM products WHERE code = '". $product_in_cell_row ."'")[0]['id'];
						if($product_id){
							$workArray[ $key2 ]['products'][] = [
								'code'  => $product_in_cell_row,
								'id'    => $product_id
							];
						} else {
							$mpnk = get_kod_fixed($product_in_cell_row);
							if( get_dse($mpnk) == $product_in_cell_row && $mpnk ){
								$workArray[ $key2 ]['products'][] = [
									'code'  => $product_in_cell_row,
									'mpnk'    => $mpnk
								];
							} else {
								$response['err'][] = 'Ошибка: не распознано изделие '. $product_in_cell_row .' - '. $mpnk .' на строке '. ( (int)$key2 + 4 );
							}
						}
					}
				}
			}
			if( count($response['err']) > 0 ){
				echo json_encode($response);
				exit;
			} else {
				foreach ($workArray as &$equipment){
					if( $equipment['id'] == ''){ //Добавляем оснастку, если ее нет в бд
						$query = "
							INSERT INTO `equipment`
							    (`id`, `code`, `title`, `notice`, `active`, `status`) VALUES
                                (NULL, '".$equipment['code']."', '".$equipment['new_title']."', '', '1', '-')";
						$equipment['id'] = sqlupd($query);
					}
					if( count($equipment['products']) > 0 ){ //Добавляем применяемость в изделиях
						foreach ($equipment['products'] as &$product){
							if( $product['id'] == ''){
								$query = "
									INSERT INTO products
										(`id`, `MPNK`, `active`, `assembly`, `code`, `date`, `efficiency`, `shelfLife`, `title`, `control_id`, `document_id`, `manufacture_id`, `okp_id`, `type_id`) VALUES
										(NULL, '". get_kod($product['code']) ."', 	b'1', b'1', '".$product['code']."', 0, 0, 0, 'Автоматически добавлено при загрузке Excel (наполнение складов)', 1, 2496, 1, 1, 9);
									";
								$product['id'] = sqlupd($query);
							}
							if( sqltab("SELECT * FROM equipment_products WHERE product_id = ".$product['id']." AND equipment_id = ".$equipment['id'].";") === [] ){
								$query = "INSERT INTO `equipment_products`(`id`, `product_id`, `equipment_id`) VALUES (NULL,".$product['id'].",".$equipment['id'].")";
								sqlupd($query);
							}
						}
					}
					$query = "
							SELECT 
								equipment_management.id 	as 'management_id',
								equipment_events.id 		as 'event_id',
								equipment_events.storage_id as 'storage_id'
							FROM `equipment_management` 
							    LEFT JOIN equipment_story_folder ON equipment_management.id = equipment_story_folder.management_id 
							    LEFT JOIN equipment_events ON equipment_story_folder.event_id = equipment_events.id 
							WHERE 
					      		equipment_management.equipment_id = ". $equipment['id'] ." AND 
					      		equipment_story_folder.active = 'Активно' AND 
					      		equipment_management.id IS NOT NULL
						";
					$equipment_on_storage = sqltab($query);
					foreach ($equipment_on_storage as $real_equipment) {
						if( $real_equipment['storage_id'] == $equipment['storage_id'] ){
							if( $equipment['count'] > 0 ){
								//UPDATE STATUS
								$equipment_event =
									sqlupd("
										INSERT INTO `equipment_events`
										    (
										     	`event_type_id`, 	`storekeeper_id`, 	`recipient_id`, 
										     	`storage_id`, 		`room`, 			`rack`, 		
										     	`shelf`, 			`cell`, 			`place`, 
										     	`note`
										    ) VALUES 
                                      		(
                                      		 	6,					10000,				10000,  
                                      		 	'". $equipment['storage_id'] ."', '". $equipment['room'] ."', '". $equipment['rack'] ."',
                                      		 	'". $equipment['shelf'] ."',      '". $equipment['cell'] ."', '". $equipment['place'] ."',
                                      		 	'". $equipment['note'] ."'
                                      		)
                                    ");
								sqlupd("UPDATE equipment_story_folder SET active = 'Неактивно' WHERE management_id = ". $real_equipment['management_id'] .";");
								sqlupd("INSERT INTO `equipment_story_folder`(`management_id`, `event_id`, `active`) VALUES (". $real_equipment['management_id'] .",". $equipment_event .", 'Активно')");
								$equipment['count'] = $equipment['count'] - 1;
							} else {
								//DELETE OTHERS
								sqlupd("
									DELETE equipment_events, equipment_story_folder 
										FROM equipment_story_folder 
										LEFT JOIN equipment_events ON 
										    equipment_story_folder.event_id = equipment_events.id
									WHERE equipment_story_folder.management_id = ". $real_equipment['management_id']);
								sqlupd("DELETE FROM equipment_management WHERE id = ". $real_equipment['management_id']);
							}
						}
					}
					//INSERT NEW
					while( $equipment['count'] > 0 ){
						$query = "
							INSERT INTO `equipment_events`
							    (
							        `event_type_id`, 	`storekeeper_id`, 	`recipient_id`, 
							        `storage_id`, 		`room`, 			`rack`, 		
							        `shelf`, 			`cell`, 			`place`, 
							        `note`
							    ) VALUES 
                                (
                                    6,					10000,				10000,  
                                    '". $equipment['storage_id'] ."', '". $equipment['room'] ."', '". $equipment['rack'] ."',
                                    '". $equipment['shelf'] ."',      '". $equipment['cell'] ."', '". $equipment['place'] ."',
                                    '". $equipment['note'] ."'
                                )";
						$equipment_event = sqlupd($query);

						$query = "INSERT INTO `equipment_management`(`equipment_id`) VALUES (".$equipment['id'].")";
						$equipment_management_id = sqlupd($query);

						$query = "INSERT INTO `equipment_story_folder`(`management_id`, `event_id`, `active`) VALUES (". $equipment_management_id .",". $equipment_event .", 'Активно')";
						sqlupd($query);

						$equipment['count'] = $equipment['count'] - 1;
					}
				}
			}
			echo json_encode($response);
		}
		if($query == 'equipment_product_xlsx_js'){
			$response = [
				'err' => [],
				'info' => [
					'equipment' => [
						'title_conflict' => [],
						'not_exist' => []
					],
					'product' => []
				],
				'actions' => []
			];
			$workArray = [];
			require_once ('../phpExcel/Classes/PHPExcel.php');
			$path = $_SERVER['DOCUMENT_ROOT']. "\\" . $_POST['file'];
			$fileObj = \PHPExcel_IOFactory::load( $path );
			$sheetObj = $fileObj->getActiveSheet()->toArray(null,true,true,true);
			array_splice($sheetObj,0,3);
			foreach ( $sheetObj as &$value ){
				$value['A'] = str_replace(' ', '', $value['A']);
				$value['B'] = str_replace('  ', ' ', $value['B']);
			}
			unset($value);
			foreach ( $sheetObj as $key2 => $value ){
				if( $value['A'] == '') continue;
				$workArray[ $key2 ]['code']         = $value['A'];
				$workArray[ $key2 ]['products']     = [];
				$equipment = sqltab("SELECT * FROM equipment WHERE code = '". $value['A'] ."';")[0];
				if( !$equipment['id'] ){
					$response['info']['equipment']['not_exist'][] = $value['A'];
					$workArray[ $key2 ]['id'] = null;
					$workArray[ $key2 ]['new_title'] = $value['B'];
				} else {
					$workArray[ $key2 ]['id'] = $equipment['id'];
					if( $equipment['title'] != $value['B'] ){
						$selected = ( strlen($equipment['title']) > strlen($value['B']) ) ? $equipment['title'] : $value['B'];
						$response['info']['equipment']['title_conflict'][] =
							[
								'id'    => $equipment['id'],
								'DB'    => $equipment['title'],
								'Excel' => $value['B'],
								'SELECTED' => $selected
							];
						$workArray[ $key2 ]['new_title'] = $selected;
					}
				}

				if( $value['J'] ){ //Перебор изделий, в которых используется оснастка
					$products_in_cell = explode(';', $value['J'] );
					foreach ($products_in_cell as &$product_in_cell_row) { //Перебор изделий построчно
						$product_in_cell_row = str_replace("\n", '', $product_in_cell_row);
						$product_id = sqltab("SELECT id FROM products WHERE code = '". $product_in_cell_row ."'")[0]['id'];
						if($product_id){
							$workArray[ $key2 ]['products'][] = [
								'code'  => $product_in_cell_row,
								'id'    => $product_id
							];
						} else {
							$mpnk = get_kod_fixed($product_in_cell_row);
							if( get_dse($mpnk) == $product_in_cell_row && $mpnk ){
								$workArray[ $key2 ]['products'][] = [
									'code'  => $product_in_cell_row,
									'mpnk'    => $mpnk
								];
							} else {
								$response['err'][] = 'Ошибка: не распознано изделие '. $product_in_cell_row .' - '. $mpnk .' на строке '. ( (int)$key2 + 4 );
							}
						}
					}
				}
			}
			if( count($response['err']) > 0 ){
				echo json_encode($response);
				exit;
			} else {
				foreach ($workArray as &$equipment){
					if( $equipment['id'] == '' ){ //Добавляем оснастку, если ее нет в бд
						$query = "
							INSERT INTO `equipment`
							    (`id`, `code`, `title`, `notice`, `active`, `status`) VALUES
                                (NULL, '".$equipment['code']."', '".$equipment['new_title']."', '', '1', '-')";
						$equipment['id'] = sqlupd($query);
					}
					if( count($equipment['products']) > 0 ){ //Добавляем применяемость в изделиях
						foreach ($equipment['products'] as &$product){
							if( $product['id'] == ''){
								$query = "
									INSERT INTO products
										(`id`, `MPNK`, `active`, `assembly`, `code`, `date`, `efficiency`, `shelfLife`, `title`, `control_id`, `document_id`, `manufacture_id`, `okp_id`, `type_id`) VALUES
										(NULL, '". get_kod($product['code']) ."', 	b'1', b'1', '".$product['code']."', 0, 0, 0, 'Автоматически добавлено при загрузке Excel (наполнение складов)', 1, 2496, 1, 1, 9);
									";
								$product['id'] = sqlupd($query);
							}
							if( sqltab("SELECT * FROM equipment_products WHERE product_id = ".$product['id']." AND equipment_id = ".$equipment['id'].";") === [] ){
								$query = "INSERT INTO `equipment_products`(`id`, `product_id`, `equipment_id`) VALUES (NULL,".$product['id'].",".$equipment['id'].")";
								sqlupd($query);
							} elseif( $_POST['delete'] == true) {
								$query = "DELETE FROM `equipment_products` WHERE product_id = ".$product['id']." AND equipment_id = ".$equipment['id']."";
								sqlupd($query);
							}
						}
					}
				}
			}
			echo json_encode($response);
		}
		if($query == 'FindEquipmentByUserId'){
			$condidion = '';
			$Storages_id = [];
			foreach ($_POST['storages'] as $value){
				$Storages_id[] = $value['storage_id'];
			}
			$condidion .= '('. implode(',', $Storages_id) . ')';
			echo
				json_encode(
					sqltab(" 
            	        SELECT 
            	            e.code, 
            	            e.id, 
            	            ee.storage_id,
            	            count(*) as 'count',
            	            es.title as 'title'
            	        FROM equipment e
            	            LEFT JOIN equipment_management em on e.id = em.equipment_id
            	            LEFT JOIN equipment_story_folder esf on esf.management_id = em.id
            	            LEFT JOIN equipment_events ee on ee.id = esf.event_id
            	            LEFT JOIN equipment_storages es on ee.storage_id = es.id
            	        WHERE 
            	            esf.active = 2 AND 
            	            e.code LIKE '%". $_POST['equipment_code'] ."%' AND
                            ee.storage_id IN ". $condidion ." GROUP BY ee.storage_id, e.code;")
				);
		}
		if($query == 'FindProductsByEquipmentId'){
			echo json_encode(sqltab("SELECT products.title, products.code FROM products 
              LEFT JOIN equipment_products on products.id = equipment_products.product_id 
              WHERE equipment_products.equipment_id = ". $_POST['id']));
		}
		if($query == 'FixEquipmentCodeAndEquipmentManagement'){
			$equipment_managements = sqltab("SELECT id FROM equipment_management WHERE equipment_id = '". $_POST['equipment_id']. "';");
			$equipment = sqltab("SELECT id FROM equipment WHERE equipment.code = '". $_POST['equipment_code']. "';");
			if(count($equipment) > 0){
				$equipment_id = $_POST['equipment_id'];
			} else {
				$equipment_id = sqlupd("INSERT INTO equipment (`code`) VALUES ('".$_POST['equipment_code']."')");
			}
			foreach ($equipment_managements as $value){
				sqlupd("UPDATE equipment_management SET equipment_id = ". $equipment_id ." WHERE equipment_management.id = ". $value['id']);
			}
			echo json_encode(['reply' => 'ok']);
			exit;
		}
		if($query == 'EquipmentManagementRequestDELETE'){
			$story_folder_id = json_decode(sqltab("SELECT story_folder_id FROM equipment_management_request WHERE id = ". $_POST['id'])[0]['story_folder_id']);
			if($story_folder_id){
				foreach ($story_folder_id as $value){
					sqlupd('UPDATE equipment_story_folder SET active = 2 WHERE id = '. $value);
				}
			}
			sqlupd('DELETE FROM equipment_management_request WHERE id = '. $_POST['id']);
			echo json_encode(['response' => 'DELETED']);
		}
		if($query == 'EquipmentManagementRequestProceed'){
			if(
				$_POST['SendData']['decision'] == 'accept' &&
				is_array($_POST['SendData']['coords'])
			){
				sqlupd("
        		UPDATE equipment_management_request emr SET
        			emr.storage_id 	= '". $_POST['SendData']['coords']['storage_id']    ."',
        			emr.room 	    = '". $_POST['SendData']['coords']['room']          ."',
                	emr.rack 	    = '". $_POST['SendData']['coords']['rack']          ."',
                	emr.shelf	    = '". $_POST['SendData']['coords']['shelf']         ."',
                	emr.cell 	    = '". $_POST['SendData']['coords']['cell']          ."',
                	emr.place 	    = '". $_POST['SendData']['coords']['place']         ."'
                WHERE emr.id = '". $_POST['SendData']['equipment_management_request_id'] ."'");
			}
			$sql = sqltab("
            SELECT
                emr.id,
                emr.equipment_id,
                emr.sender_id,
                emr.storekeeper_id,
                emr.storage_id,
                emr.event_type_id,
                emr.story_folder_id,
                emr.date,
                emr.count,
                emr.status,
                emr.note,
                   
                emr.room,
                emr.rack,
                emr.shelf,
                emr.cell,
                emr.place,
                   
                e.id  		as 'equipment_id',
                e.code  	as 'equipment_code',
                e.title 	as 'equipment_title',
                u.name 		as 'production_user_code',
                u2.name 	as 'storekeeper_code',
                es.title 	as 'storage_code',
                ee.title 	as 'equipment_event'
            FROM equipment_management_request emr
                LEFT JOIN equipment e ON emr.equipment_id = e.id
                LEFT JOIN users u on emr.sender_id = u.id
                LEFT JOIN users u2 on emr.storekeeper_id = u2.id
                LEFT JOIN equipment_storages es on emr.storage_id = es.id
                LEFT JOIN equipment_event_types ee on emr.event_type_id = ee.id
            WHERE emr.id = '". $_POST['SendData']['equipment_management_request_id'] ."'")[0];
			if( $_POST['SendData']['decision'] == 'accept' ){
				$department_id  = 1;
				$workshop_id    = 1;
				if( $sql['story_folder_id'] === '[]' ){
					for($i = 0; $i < $sql['count']; $i++){
						$equipment_management_id =
							sqlupd("INSERT INTO 
                            `equipment_management`(
                                `id`,  
                                `equipment_id`
                                )
                            VALUES ( 
                                NULL, 
                                '". $sql['equipment_id']  . "');
                            ");
						$equipment_equipment_event_id =
							sqlupd("
                            INSERT INTO `equipment_events`(
                                `id`,
                                `timestamp`,
                                `event_type_id`,
                                `storekeeper_id`,
                                `recipient_id`,
                                `storage_id`, 
                                `room`, 
                                `rack`, 
                                `shelf`, 
                                `cell`, 
                                `place`, 
                                `note`
                            ) VALUES (
                                NULL,
                                NOW(), 
                                '". $sql['event_type_id']   ."', 
                                '". $sql['storekeeper_id']  ."', 
                                '10000',
                                '". $sql['storage_id']      . "', 
                                '". $sql['room']            . "', 
                                '". $sql['rack']            . "', 
                                '". $sql['shelf']           . "', 
                                '". $sql['cell']            . "', 
                                '". $sql['place']           . "', 
                                '". $sql['note']            ."');
                            ");
						sqlupd("
                        INSERT INTO `equipment_story_folder` (
                            `id`, 
                            `management_id`, 
                            `event_id`, 
                            `active`
                        ) VALUES (
                            NULL,
                            '". $equipment_management_id      ."',
                            '". $equipment_equipment_event_id ."',
                            '2'
                        )
                    ");
					}
				} else {
					foreach ( json_decode($sql['story_folder_id'] ) as $value){
						$eq_mng_id =
							sqltab("
                            SELECT equipment_management.id 
                            FROM equipment_management 
                            LEFT JOIN equipment_story_folder esf on equipment_management.id = esf.management_id
                            WHERE esf.id = ". $value
							)[0]['id']; //todo КАЖЕТСЯ ТУТ БРЕД
						sqlupd('UPDATE equipment_story_folder SET active = 1 WHERE id = '. $value);
						$ins_id =
							sqlupd("INSERT INTO equipment_events 
                            (
                                `storage_id`,        `event_type_id`,    `recipient_id`, 
                                `storekeeper_id`,    `room`,             `rack`, 
                                `shelf`,             `cell`,             `place`, 
                                `timestamp`,         `note`
                            ) VALUES 
                            (
                               '". $sql['storage_id']     ."', 
                               '". $sql['event_type_id'] ."', 
                               '10000', 
                               '". $sql['storekeeper_id'] ."', 
                               '1', 
                               '1', 
                               '1', 
                               '1', 
                               '1', 
                               now(), 
                               '". $sql['note'] ."'
                            )"
							);
						sqlupd("INSERT INTO equipment_story_folder (`event_id`, `management_id`, active) VALUES ('".$ins_id."', '".$eq_mng_id."', 2)");
					}
				}
				sqlupd('UPDATE 
                  	`equipment_management_request` 
                  SET 
                  	`status` = 2
                  WHERE 
                  	`id` = '. $_POST['SendData']['equipment_management_request_id'] .';');
				echo json_encode(['response' => 'accepted']);
			}
			if($_POST['SendData']['decision'] == 'deny'){
				sqlupd('UPDATE 
                	`equipment_management_request` 
                SET 
                	`status` = 3
                WHERE 
                	`id` = '. $_POST['SendData']['equipment_management_request_id']);
				if($sql['story_folder_id'] != '[]'){
					foreach (json_decode($sql['story_folder_id']) as $value){
						sqlupd('UPDATE equipment_story_folder SET active = 2 WHERE id ='. $value);
					}
				}
				echo json_encode(['response' => 'denied']);
			}
		}
		if($query == 'GetRequestsByStorekeeperId'){
			echo json_encode(
				sqltab("
                	SELECT
                	    emr.id,
                	    emr.equipment_id,
                	    emr.storage_id,
                	    emr.story_folder_id,
                	    emr.date,
                	    emr.count,
                	    emr.note,
                	    emr.status,
                	       
                	    emr.room,
                	    emr.rack,
                	    emr.shelf,
                	    emr.cell,
                	    emr.place,
                	       
                	    equipment.code  as 'equipment_code',
                	    equipment.title as 'equipment_title',
                	    u.id 			as 'storekeeper_id',
                	    u.name 			as 'storekeeper_name',
                	    eet.id 			as 'event_type_id',
                	    eet.title 		as 'event_type_title',
                	    IFNULL(es.title, 'Инструментальное производство') as 'storage_code'
                	FROM equipment_management_request emr
                	    LEFT JOIN equipment 				ON emr.equipment_id = equipment.id
                	    LEFT JOIN equipment_storages es 	ON emr.storage_id = es.id
                	    LEFT JOIN equipment_event_types eet ON emr.event_type_id = eet.id
                	    LEFT JOIN users u 					ON u.id = emr.sender_id
                	WHERE
                	    emr.storekeeper_id = '". $_POST['storekeeper_id'] ."' AND 
                        emr.status = 1
                    ORDER BY emr.date DESC;")
			);
		}
		if($query == 'FindStorekeeperByStoreId'){
			$sql_storekeepers = 'SELECT equipment_storages_access.user_id,
                                  users.name
                              FROM equipment_storages_access 
                              LEFT JOIN users ON users.id = equipment_storages_access.user_id
                           WHERE equipment_storages_access.storage_id = '. $_POST['storage_id'];
			echo json_encode(
				sqltab($sql_storekeepers)
			);
		}
		if($query == 'RemoveProductFromSet'){
			$data = json_decode( $_POST['data'] );
			$sql_equipment_management = "
          		DELETE FROM `equipment_products` WHERE 
          			`equipment_products`.`product_id` 	= '". $data->product_id ."' AND 
                    `equipment_products`.`equipment_id` = '". $data->equipment_id ."' LIMIT 1";
			sqlupd($sql_equipment_management);
			echo "OK";
		}
		if($query == 'RemoveTTPFromSet'){
			$data = json_decode( $_POST['data'] );
			$sql_equipment_management = "
          DELETE FROM `equipment_ttp` WHERE 
                      `equipment_ttp`.`ttp_id` =  '". $data->ttp_id ."' AND 
                      `equipment_ttp`.`equipment_id`= '". $data->equipment_id ."';";
			sqlupd($sql_equipment_management);
			echo 'OK';
		}
		if($query == 'AddProductToProductSetById') {
			$data = json_decode($_POST['data']);
			$CheckSql = "SELECT count(*) as count FROM `equipment_products` WHERE product_id = '". $data->product_id ."' AND equipment_id = '".$data->equipment_id."';";
			$CheckForDouble = intval(sqltab($CheckSql)[0]['count']);
			if($CheckForDouble === 0){
				$sql_equipment_management = '
          INSERT INTO `equipment_products`(`product_id`, `equipment_id`) VALUES ('. $data->product_id .','. $data->equipment_id .')';
				sqlupd($sql_equipment_management);
				echo json_encode(['response' => 'ok']);
			} else {
				echo json_encode(['response' => 'Повторная запись']);
			}
		}
		if($query == 'AddTTPToTTPSetById'){
			$data = json_decode( $_POST['data'] );
			$CheckSql = "SELECT count(*) as count FROM `equipment_ttp` WHERE ttp_id = '". $data->ttp ."' AND equipment_id = '".$data->equipment."'";
			$CheckForDouble = intval(sqltab($CheckSql)[0]['count']);
			if($CheckForDouble === 0){
				$sql_equipment_management = '
          INSERT INTO `equipment_ttp`(`ttp_id`, `equipment_id`) VALUES ('. $data->ttp .','. $data->equipment .')';
				sqlupd($sql_equipment_management);
				echo json_encode(['response' => 'ok']);
			} else {
				echo json_encode(['response' => 'Повторная запись']);
			}
		}
		if($query == 'InsertOsnastka') {
			$equipment_id = sqltab("SELECT id FROM equipment WHERE code = '". $_POST['obj']['code'] ."';")[0]['id'];
			if( $_POST['obj']['status'] == '' ) $_POST['obj']['status'] = '-';
			if( !$equipment_id ){
				$equipment_id =
					sqlupd("
					INSERT INTO `equipment`
					    (`id`, `code`, `title`, `notice`, `active`, `status`) VALUES 
						(
						 	NULL,
						 	'". $_POST['obj']['code'] ."',
						 	'". $_POST['obj']['title'] ."',
						 	'". $_POST['obj']['notice'] ."',
						 	'1',
						 	'". $_POST['obj']['status'] ."'
						)"
					);
				$response = 'Добавлена новая Оснастка';
				$new = true;
			} else {
				sqlupd("
				UPDATE equipment SET 
			    	title = '".     $_POST['obj']['title']  ."',
			        status = '".    $_POST['obj']['status'] ."',
			        notice = '".    $_POST['obj']['notice'] ."'
			    WHERE code = '".  $_POST['obj']['code'] ."';
			  ");
				$response = 'Оснастка изменена';
				$new = false;
			}
			echo json_encode(
				[
					"message" => $response,
					"new" => $new,
					"id" => $equipment_id,
				]
			);
		}
		if($query == 'InsertNewEquipmentManagementID') {
			$SendData = $_POST['SendData'];
			if(
				isset($SendData['equipment_new']) &&
				$SendData['equipment_new'] === 'true'
			){
				$sql_equipment = "
				INSERT INTO `equipment`(`id`, `code`, `title`, `notice`, `active`) VALUES 
      			(
      				NULL, 
      				'".$SendData['equipment_code']."' ,
                    '".$SendData['equipment_title']."',
                    '".$SendData['equipment_notice']."',
                    '1'
                )";
				$SendData['equipment_id'] = sqlupd($sql_equipment);
			}
			if(
				isset($SendData['recipient_new']) &&
				$SendData['recipient_new'] === 'true'
			){
				$recipient_id = '';
				$insert = "INSERT INTO `equipment_workers`(
      		    `id`,
      		    `name`
      		) 
      		VALUES 
      		  (
      		    NULL,
      		    '".$SendData['recipient_text']."'
              )";
				$recipient_id = sqlupd($insert);
			} else
				if( isset($SendData['recipient_id']) ){
					$recipient_id = $SendData['recipient_id'];
				} else {
					$recipient_id = 10000;
				}
			$storage_id     = $SendData['storage_id'];
			$equipment_id   = $SendData['equipment_id'];
			for($i = 0; $i < $SendData['quantity']; $i++){
				$equipment_management_id =
					sqlupd("
					INSERT INTO 
      					`equipment_management`  
      					(
      						`id`,  
      						`equipment_id`
      					)
      					VALUES
      					( 
      						NULL, 
      						'". $equipment_id   . "');
                        ");
				$equipment_equipment_event_id =
					sqlupd("
					INSERT INTO `equipment_events`(
    					`id`,
    					`timestamp`,
    					`event_type_id`,
    					`storekeeper_id`,
    					`recipient_id`,
    					`storage_id`, 
    					`room`, 
    					`rack`, 
    					`shelf`, 
    					`cell`, 
    					`place`,
    					`note`
    				)
    				  VALUES
    				(
    					'NULL',
    					NOW(), 
    					'". $SendData['event_type']     ."', 
                        '". $SendData['storekeeper_id'] ."', 
                        '". $SendData['recipient_id']   ."',
                        '". $storage_id                 ."',
                        '". $SendData['room']           ."',
                        '". $SendData['rack']           ."', 
                        '". $SendData['shelf']          ."', 
                        '". $SendData['cell']           ."', 
                        '". $SendData['place']          ."', 
                        '". $SendData['note']           ."'
                    );
                ");
				sqlupd("INSERT INTO `equipment_story_folder`
    			(
    			  `id`, 
    			  `management_id`, 
    			  `event_id`, 
    			  `active`
    			) VALUES (
    			  'NULL',
    			  '". $equipment_management_id      ."',
                  '". $equipment_equipment_event_id ."',
                  '1'
                )"
				);
			}
			echo 'okay_insert';
		}
		if($query == 'InsertEquipmentInStorageQueue') {
			$data = $_POST['SendData'];
			$response = [];
			$errInsert = 'Вставка отменена: ';
			if( $data['equipment_id'] == ''){
				$response['err'] = $errInsert. 'Выберите оснастку';
				echo json_encode($response);
				exit;
			}
			if( $data['storekeeper_id'] == ''){
				$response['err'] = $errInsert. 'Выберите кладовщика';
				echo json_encode($response);
				exit;
			}
			if( $data['storage_id'] == ''){
				$response['err'] = $errInsert. 'Выберите склад';
				echo json_encode($response);
				exit;
			}
			if( $data['event_type_id'] == ''){
				$response['err'] = $errInsert. 'Выберите статус';
				echo json_encode($response);
				exit;
			}
			if( $data['count'] == '' || (int)$data['count'] <= 0){
				$response['err'] = $errInsert. 'Недопустимое количество';
				echo json_encode($response);
				exit;
			}
			if( $data['sender_id'] == '' ){
				$response['err'] = $errInsert. 'Отправитель не найден, обратитесь к Администратору';
				echo json_encode($response);
				exit;
			}
			$id = sqlupd("
			INSERT INTO `equipment_management_request`
      			(
      				`equipment_id`, 
      				`story_folder_id`, 
      				`storekeeper_id`, 
      				`storage_id`, 
      				`event_type_id`, 
      				`count`, 
      				`note`, 
      				`sender_id`, 
      				`date`, 
      				`status`
      			) 
      				VALUES 
      			('".
				$data['equipment_id']."', '[]' ,'".
				$data['storekeeper_id']."','".
				$data['storage_id']."','".
				$data['event_type_id']."','".
				$data['count']."','".
				$data['note']."','".
				$data['sender_id']."',
                    now(),
                    '1'
                )
                 ");
			$response['response_text'] = 'Заявка Создана';
			$response['insert_id'] = $id;
			echo json_encode($response);
		}
		if($query == 'ChangeRecipientByEquipmentManagementID') {
			$recipient_id = '';
			$data = $_POST['SendData'];
			$sql = "
					SELECT 
                    	em.id   as 'em_id', 
                    	ee.rack           ,
                    	ee.shelf          ,
                    	ee.cell           ,
                    	ee.place          ,
                    	ee.storage_id     ,
                    	em.equipment_id   ,
                    	ee.recipient_id   ,
                    	ee.storekeeper_id ,
                    	ee.event_type_id  ,
                    	esf.id as 'esf_id',
                    	ee.timestamp      
                    FROM equipment_management em
                    	LEFT JOIN equipment_story_folder esf on em.id = esf.management_id
                    	LEFT JOIN equipment_events ee on esf.event_id = ee.id
                    WHERE 
                      em.equipment_id   = '". $data['equipment_id']."'                  AND
                      ee.rack           = '". $data['rack_default']."'                  AND
                      ee.shelf          = '". $data['shelf_default']."'                 AND
                      ee.cell           = '". $data['cell_default']."'                  AND
                      ee.place          = '". $data['place_default']."'                 AND
                      ee.room           = '". $data['room_default']."'                  AND
                      ee.note           = '". $data['note_default']."'                  AND
                      ee.storage_id     = '". $data['storage_id']."'                    AND
                      ee.event_type_id  = '". $data['event_type_id_default']."'         AND
                      esf.active        = 2                                             AND
                      ee.recipient_id   = '". $data['recipient_default']."'
                    ";
			$SelectEqIds  =
				sqltab($sql);
			if( count($SelectEqIds) < $data['quantity'] ){
				echo 'Ошибка, нельзя поменять расположение оснастки в большем количестве, чем имеется';
				exit;
			}
			if($data['recipient_type'] == 'user'){
				if($data['recipient_new'] == 'true'){
					$recipient_id = sqlupd("INSERT INTO `equipment_workers`(
              `id`,  
              `name`
          ) 
          VALUES 
          (
            NULL,  
            '".$data['recipient_text']."'
          )");
				} else {
					$recipient_id = $data['recipient_id'];
				}
				foreach ($SelectEqIds as $value){
					if($data['quantity'] == 0){
						break;
					}
					sqlupd('UPDATE `equipment_story_folder` 
                    SET `active` = 1 
                  WHERE id = '. $value['esf_id']);
					$ins_id = sqlupd("INSERT INTO equipment_events 
                      (`event_type_id`, `storekeeper_id`, `storage_id`, `recipient_id`, `rack`, `shelf`, `cell`, `place`, `room`, `timestamp`, `note`) VALUES 
                      (".
						$data['event_type_id'] .", ".
						$value['storekeeper_id'] .", ".
						$value['storage_id'] .", '".
						$recipient_id."', '".
						$data['rack'] ."', '".
						$data['shelf'] ."', '".
						$data['cell'] ."', '".
						$data['place'] ."', '".
						$data['room'] ."', now(), '".
						$data['note'] ."')");
					sqlupd("INSERT INTO equipment_story_folder (`event_id`, `management_id`, `active`) VALUES (".$ins_id.", ".$value['em_id'].", 2)");
					$data['quantity']--;
				}
			} else {
				$story_folder = [];
				$quantity = $data['quantity'];
				foreach ($SelectEqIds as $value){
					if($quantity == 0){
						break;
					}
					$story_folder[] = $value['esf_id'];
					sqlupd('UPDATE `equipment_story_folder` 
                    SET `active` = 3 
                  WHERE id = '. $value['esf_id']);
					$quantity--;
				}
				$id = sqlupd("INSERT INTO `equipment_management_request`
                ( `equipment_id`,   `story_folder_id`,  `storekeeper_id`, 
                  `storage_id`,     `event_type_id`,    `count`,            `note`, 
                  `sender_id`,      `date`,             `status`) VALUES 
                  ('".
					$data['equipment_id']."','".
					json_encode($story_folder)."','".
					$data['new_storekeeper_id']."','".
					$data['new_storage_id']."','".
					$data['event_type_id']."','".
					$data['quantity']."','".
					$data['note']."','".
					$data['storekeeper_id']."',
                  now(),
                  '1'
                  )
                 ");
			}
			echo 'okay_insert';
		}
		if($query == 'SelectEquipmentEventTypes') {
			$sqlselect = "SELECT * FROM `equipment_event_types` WHERE title LIKE '%". $_POST['title']. "%';";
			$test = sqltab($sqlselect);
			if(is_array($test) || count($test) > 0){
				echo json_encode($test);
			} else {
				echo 'Изделие не найдено';
			}
		}
		if($query == 'FindEquipmentManagementByStoreID'){
			$sql_equipment_management = '
            	SELECT
            	    #Оснастка
            	    equipment_management.id         AS "equipment_management_id",
            	    equipment.title,
            	    equipment.code,
            	    equipment.id                    AS "equipment_id",
            	    equipment_event_types.id        AS "equipment_event_type_id",
            	    equipment_event_types.title     AS "equipment_event_type_title",
            	    users_recipient.name            AS "recipient_name",
            	
            	    #Свойства события
            	    equipment_events.id             AS "equipment_event_id",
            	    equipment_events.note           AS "events_note",
            	    equipment_events.rack           AS "events_rack",
            	    equipment_events.shelf          AS "events_shelf",
            	    equipment_events.cell           AS "events_cell",
            	    equipment_events.place          AS "events_place",
            	    equipment_events.room           AS "events_room",
            	    equipment_events.recipient_id,
            	    equipment_events.timestamp,
            	    count(equipment_management.id) AS count
            	FROM
            	    equipment_management
            	    LEFT JOIN equipment_story_folder ON equipment_story_folder.management_id = equipment_management.id
            	    LEFT JOIN equipment_events ON equipment_events.id = equipment_story_folder.event_id
            	    LEFT JOIN equipment_workers users_recipient ON equipment_events.recipient_id = users_recipient.id
            	    LEFT JOIN equipment_event_types ON equipment_event_types.id = equipment_events.event_type_id
            	    LEFT JOIN equipment ON equipment.id = equipment_management.equipment_id
            	WHERE
            	    equipment_events.storage_id = '. $_POST['storage_id'] .' AND
                    equipment_story_folder.active = 2';
			if($_POST['request_dop'] == 'code'){
				$sql_equipment_management .= ' AND equipment.code LIKE "%' . $_POST['code'] .'%"';
			}
			$sql_equipment_management .= ' GROUP BY 
                                         equipment.id, 
                                         rack,
                                         shelf,
                                         cell,
                                         place,
                                         room,
                                         recipient_id,
                                         equipment_event_type_id,
                                         note
                                         ORDER BY timestamp desc';
			echo json_encode(sqltab($sql_equipment_management));
		}
		if($query == 'FindDataByEquipmentCode') {
			$sql_equipment_management = "SELECT * FROM equipment WHERE code LIKE '%". $_POST['code']."'%;";
			$result = sqltab($sql_equipment_management);
			if(is_array($result) || count($result) > 0){
				echo json_encode($result);
			} else {
				echo 'Оснастка не найдена';
			}
		}
		if($query == 'FindDataByEquipmentCodeSHARP') {
			$sql_equipment_management = "SELECT * FROM equipment WHERE code LIKE '". $_POST['code']."';";
			$result = sqltab($sql_equipment_management)[0];
			echo json_encode($result);
		}
		if($query == 'FindEquipmentProducts') {
			$sql_equipment_management = "
				SELECT 
      				equipment.code as eq_code,
      				equipment_products.equipment_id as eq_id,
      				products.id as id,
      				products.code as code,
      				products.title as title
      			FROM `equipment_products` 
      				LEFT JOIN equipment ON equipment_products.equipment_id = equipment.id 
      				LEFT JOIN products ON 
      				    equipment_products.product_id = products.id 
				WHERE equipment_products.equipment_id = '". $_POST['id']."';";
			$test = sqltab($sql_equipment_management);
			if(is_array($test) || count($test) > 0){
				echo json_encode(sqltab($sql_equipment_management));
			} else {
				echo 'Нет изделий';
			}
		}
		if($query == 'FindEquipmentTTP') {
			$sql_equipment_management = "
				SELECT 
      				equipment.code as eq_code,
      				equipment_ttp.equipment_id as eq_id,
      				documents_ttp.code as code,
      				documents_ttp.id as ttp_id
            	FROM `equipment_ttp` 
      				LEFT JOIN equipment ON equipment_ttp.equipment_id = equipment.id 
      				LEFT JOIN documents_ttp ON equipment_ttp.ttp_id = documents_ttp.id 
				WHERE equipment_ttp.equipment_id = '". $_POST['id']."';";
			$test = sqltab($sql_equipment_management);
			if(is_array($test) || count($test) > 0){
				echo json_encode(sqltab($sql_equipment_management));
			} else {
				echo 'Нет изделий';
			}
		}
		if($query == 'FindProductByCode') {
			$sql_equipment_management = "SELECT * FROM products WHERE code LIKE '%". $_POST['search']. "%' LIMIT 60;";
			$response = [];
			foreach (sqltab($sql_equipment_management) as $value){
				$response[] = array("value"=>$value['id'],"label"=>$value['code']);
			}
			echo json_encode($response);
		}
		if($query == 'FindUsersAndStorages') {
			$sql_users =    "SELECT * FROM equipment_workers WHERE name LIKE '%". $_POST['name']. "%' GROUP BY name;";
			$sql_storages = "
				SELECT 
                	equipment_storages.title as 'storage_name', 
                	equipment_storages.id as 'storage_id',
                	u.name as 'user_name',
                	u.id as 'user_id'
               	FROM equipment_storages 
                	LEFT JOIN equipment_storages_access a on equipment_storages.id = a.storage_id 
                	LEFT JOIN users u on a.user_id = u.id
            	WHERE 
              		user_id IS NOT NULL AND 
                  	equipment_storages.title LIKE '%". $_POST['name'] . "%' 
                GROUP BY user_name;";
			$users =    sqltab($sql_users);
			$storages = sqltab($sql_storages);
			$ret = [
				'users' => $users,
				'storages' => $storages
			];
			echo json_encode($ret);
		}
		if($query == 'FindTTPByCode') {
			$sql_documents_ttp = sqltab("SELECT * FROM documents_ttp WHERE code LIKE '%". $_POST['search']."%' LIMIT 15;");
			$response = [];
			foreach ($sql_documents_ttp as $value){
				$response[] = array("value"=>$value['id'],"label"=>$value['code']);
			}
			echo json_encode($response);
		}
		if($query == 'ShowStorekeeperRoots'){
			$sql_documents_ttp = "SELECT storage_id FROM equipment_storages_access WHERE user_id = '". $_POST['data']."';";
			$test = sqltab($sql_documents_ttp);
			if(is_array($test) || count($test) > 0){
				echo json_encode(sqltab($sql_documents_ttp));
			} else {
				echo 'Неизвестная ошибка';
			}
		}
		if($query == 'EditStorekeeperRoots'){
			if($_POST['data']['isSelected'] === 'true'){
				sqlupd("
			INSERT INTO `equipment_storages_access`
			    (
			    	`user_id`, 
			    	`storage_id`, 
			     	`rootlevel`
			    ) 
			    	VALUES 
				(
					'". $_POST['data']['user_id'] ."',
					'". $_POST['data']['storage_id'] ."',
					'1'
				)
			");
			} else {
				sqlupd("DELETE FROM `equipment_storages_access` WHERE user_id = '". $_POST['data']['user_id'] ."' AND storage_id = '". $_POST['data']['storage_id'] ."';");
			}
		}
		if($query == 'GetEquipmentInProducts' ){
			$result = sqltab("SELECT * FROM products WHERE code = '". $_POST['code'] ."'")[0];
			if( $result === ''){
				$result = null;
			}
			echo json_encode($result);
		}
		if($query == 'InsertStructure'){
			/*
			    аргументы
				$_POST['data']['user_id']
				$_POST['data']['ParentEquipmentCode']
				$_POST['data']['next_function']

				$_POST['data']['Children']['EquipmentCode']
				$_POST['data']['Children']['EquipmentType']
				$_POST['data']['Children']['EquipmentTitle']
				$_POST['data']['Children']['EquipmentQuantity']

				else

				$_POST['data']['Children'][0]['EquipmentCode']
				$_POST['data']['Children'][0]['EquipmentType']
				$_POST['data']['Children'][0]['EquipmentTitle']
				$_POST['data']['Children'][0]['EquipmentQuantity']
			 */

			$department_id = sqltab("SELECT department_id FROM users WHERE id = ". $_POST['data']['user_id'])[0]['department_id'];
			$_POST['data']['ParentEquipmentCode'] = mb_strtoupper($_POST['data']['ParentEquipmentCode']);
			$findParentInDB = sqltab("SELECT * FROM products WHERE code = '". $_POST['data']['ParentEquipmentCode'] ."'")[0]['id'];

			if(
				isset($_POST['data']['Children']['EquipmentCode']) &&
				isset($_POST['data']['Children']['EquipmentType']) &&
				isset($_POST['data']['Children']['EquipmentTitle']) &&
				isset($_POST['data']['Children']['EquipmentQuantity'])
			){
				$_POST['data']['Children'][0]['EquipmentCode']     = $_POST['data']['Children']['EquipmentCode'];
				$_POST['data']['Children'][0]['EquipmentType']     = $_POST['data']['Children']['EquipmentType'];
				$_POST['data']['Children'][0]['EquipmentTitle']    = $_POST['data']['Children']['EquipmentTitle'];
				$_POST['data']['Children'][0]['EquipmentQuantity'] = $_POST['data']['Children']['EquipmentQuantity'];

				unset($_POST['data']['Children']['EquipmentCode']);
				unset($_POST['data']['Children']['EquipmentType']);
				unset($_POST['data']['Children']['EquipmentTitle']);
				unset($_POST['data']['Children']['EquipmentQuantity']);
			}

			if( $findParentInDB != '' && $department_id != ''){
				foreach($_POST['data']['Children'] as $child){
					$child['EquipmentCode'] = mb_strtoupper($child['EquipmentCode']);
					$findChildrenInDB   = '';
					if( $child['EquipmentCode'] != '' ){
						$findChildrenInDB = sqltab("SELECT * FROM products WHERE code = '". $child['EquipmentCode'] ."'")[0]['id'];
					}
					if( $findChildrenInDB == '' ){
						if( $child['EquipmentType'] == '10'){ //ПФ, ДП, Штампы, ГОСТ
							$beginRange = 7790;
							foreach (
								sqltab("
									SELECT * FROM product_type_code_ranges 
										WHERE 
										      product_type_id = ". $child['EquipmentType'] . " 
									ORDER BY needle DESC
									#Важно чтобы поиск начинался с самой сложной подстроки для поиска, в данном случае needle при первом прогоне будет равна 'ШТАМП'
									") as $value ){
								if (
									strpos(
										$child['EquipmentTitle'],
										$value['needle']
									) !== false
								){
									$beginRange = $value['code_range'];
									break;
								}
							}
							$BiggestCode = sqltab("SELECT MAX(ABS(code)) as code FROM `products` WHERE `type_id` = 10 AND code REGEXP '^".$beginRange."'")[0]['code'];
							$child['EquipmentCode'] = $BiggestCode == '' ? ( $beginRange . pad0((int)$BiggestCode, 7, "l") ) : $BiggestCode + 1;
							if( (int)$child['EquipmentCode'] > 77999999999 ){
								echo json_encode(
									array(
										'err' => 'Нехватка допустимого диапазона для деталей неимеющих обоначений' . $child['EquipmentCode']
									)
								);
								exit;
							}
						}
						$findChildrenInDB =
							sqlupd("
								INSERT INTO products
									(`id`, `MPNK`, `active`, `assembly`, `code`, `date`, `efficiency`, `shelfLife`, `title`, `control_id`, `document_id`, `manufacture_id`, `okp_id`, `type_id`) VALUES
									(NULL, '". get_kod($child['EquipmentCode']) ."', 	b'1', b'1', '".$child['EquipmentCode']."', 0, 0, 0, '".$child['EquipmentTitle']."', 1, 1, 1, 1, ".$child['EquipmentType'].");
							");
					}
					sqlupd("
						INSERT INTO structure_product 
						    (`id`, `date`, `part`, `quantity`, `parentProduct_id`, `product_id`, `department_id`) VALUES
							(NULL, UNIX_TIMESTAMP(), 0, ".$child['EquipmentQuantity'] .", ". $findParentInDB .", ". $findChildrenInDB .",". $department_id .")");
				}
				if( isset($_POST['data']['next_function']) ){
					$query = $_POST['data']['next_function'];
					$_POST = array(
						'code' => $_POST['data']['ParentEquipmentCode']
					);
				} else {
					echo json_encode(['response' => 'ok!!']);
				}
			} else {
				echo json_encode(
					array(
						'err' => 'Не найдено родительское изделие "'. $_POST['data']['ParentEquipmentCode'] .'". encoded as '. mb_detect_encoding($_POST['data']['ParentEquipmentCode']) .'')
					);
			}
		}
		if($query == 'RemoveProductFromProductStructure'){
			sqlupd("DELETE FROM structure_product WHERE id = ". $_POST['data']['delete_id']);

			if( isset($_POST['data']['next_function']) ){
				$query = 'GetEquipmentChildrenBootstrap5';
			} else {
				$query = 'GetEquipmentChildren';
			}
			$_POST = array(
				'code' => $_POST['data']['EquipmentCode']
			);
		}
		if($query == 'ProductRename'){
			$product_id = sqltab("SELECT * FROM products WHERE code = '".$_POST['obj']['code']."'")[0]['id'];
			$sql = "UPDATE products SET title = '". $_POST['obj']['title'] ."', type_id = ". $_POST['obj']['type'] ." WHERE id = ". $product_id;
			sqlupd($sql);
			echo json_encode(
				[
					'response' => 'ok!!',
					'sql'=> $sql
				]
			);
		}
		if($query == 'EditEquipmentProduct'){
			$count_rows = sqltab("SELECT * FROM products WHERE code = '".$_POST['obj']['code']."'");
			if( $count_rows[0]['id'] == '' ){
				if( strpos( $_POST['obj']['code'], 'П5.') === 0 || strpos( $_POST['obj']['code'], 'СБП5.') === 0 ){
					sqlupd("
						INSERT INTO products
							(`id`, `MPNK`, `active`, `assembly`, `code`, `date`, `efficiency`, `shelfLife`, `title`, `control_id`, `document_id`, `manufacture_id`, `okp_id`, `type_id`) VALUES
							(NULL, '".get_kod($_POST['obj']['code'])."', b'1', b'1', '".mb_strtoupper($_POST['obj']['code'])."', 0, 0, 0, '".$_POST['obj']['title']."', 1, 1, 1, 1, ".$_POST['obj']['type'].");");
				} else {
					echo json_encode(
						array('err' => 'Неправильный формат кода оснастки, код должен быть записан в формате "П5.xxx.xxx-xx", "СБП5.xxx.xxx-xx", принято: "'. $_POST['obj']['code'] .'"')
					);
					exit;
				}
			} else {
				sqlupd("
					UPDATE products SET 
						title 	= '". $_POST['obj']['title'] ."' 
					WHERE 
						id      = ". $count_rows[0]['id']);
			}
			$query = 'GetEquipmentChildren';
			$_POST = array(
				'code' => $_POST['obj']['code']
			);
		}
		if($query == 'GetEquipmentChildrenData'){
			$product_id = sqltab("SELECT id FROM products WHERE products.code = '". $_POST['code']  ."';")[0]['id'];
			$ret_arr = array();
			$msg = '';
			if( $product_id != '' ){
				foreach ( sqltab("SELECT * FROM structure_product WHERE parentProduct_id = ". $product_id .";") as $value ){
					$product = sqlsingle("SELECT * FROM products WHERE id = ". $value['product_id']);
					$ret_arr[$product['type_id']][] = [
						'product' => [
							'id'        =>  $product['id'],
							'code'      =>  $product['code'],
							'title'     =>  $product['title']
						],
						'structure' => [
							'id' => $value['id'],
							'quantity' => $value['quantity']
						]
					];
				}
			} else {
				$msg = 'Ничего не найдено';
			}
			echo json_encode(['content' => $ret_arr, 'message' => $msg]);
		}
		if($query == 'GetEquipmentChildren'){
			$product_id = sqltab("SELECT id FROM products WHERE products.code = '". $_POST['code']  ."';")[0]['id'];
			$ret_arr = array();
			$msg = '';
			$content = '<div class="sostav" style=""><hr/>';
			if( $product_id != '' ){
				foreach ( sqltab("SELECT * FROM structure_product WHERE parentProduct_id = ". $product_id .";") as $value ){
					$product = sqlsingle("SELECT * FROM products WHERE id = ". $value['product_id']);
					$ret_arr[ $product['type_id'] ] .= '
                        <div class="part" data-id="'. $value['id'] .'">
                        	<input  class="inline form-control code"     value="'. $product['code'] .'"         style="width: 200px; margin: 2px" readonly placeholder="Обозначение"/>
                            <input  class="inline form-control title"    value="'. $product['title'] .'"        style="width: 300px; margin: 2px" readonly placeholder="Наименование"/>
                            <input  class="inline form-control quantity" value="'. $value['quantity'] .'"		style="width: 100px; margin: 2px" readonly placeholder="Кол-во"/>
                			<button class="inline btn btn-danger removeFromProductStructure-js" type="button"	style="width: 40px;  margin: 2px">
                				<span class="glyphicon glyphicon-remove"></span>
                			</button>
                		</div>';
				}
				if( count($ret_arr) > 0 ){
					foreach ($ret_arr as $key => $item) {
						$product_type = sqlsingle("SELECT * FROM product_type WHERE id = ". $key);
						if( isset($product_type['titles'])){
							$content .= '
                                <div class="product_type" data-id="'. $key .'">
        						    <h4>
        						    	'. $product_type['titles'] .'
                                        <span class="glyphicon glyphicon-'. $product_type['glyphicon'] .'"></span>
        						    </h4>
        						    <div class="data">'. $item .'</div>
        						</div>';
						}
					}
				} else {
					$content .= '<span class="help-block">Ничего не найдено</span>';
					$msg = 'Ничего не найдено';
				}
			} else {
				$content .= '<span class="help-block">Ничего не найдено</span>';
				$msg = 'Ничего не найдено';
			}
			$content .= '<hr/></div>';
			echo json_encode(['content' => $content, 'message' => $msg]);
		}
		if($query == 'GetEquipmentChildrenBootstrap5'){
			$product_id = sqltab("SELECT id FROM products WHERE products.code = '". $_POST['code']  ."';")[0]['id'];
			$ret_arr = array();
			$msg = "";
			$content = "<hr/>";
			if( $product_id != '' ){
				foreach ( sqltab("SELECT * FROM structure_product WHERE parentProduct_id = ". $product_id .";") as $value ){
					$product = sqlsingle("SELECT * FROM products WHERE id = ". $value['product_id']);
					$ret_arr[ $product['type_id'] ] .= "
                    	<div 
                    		class='input-group part w-100 mb-3'
                    		data-id='". $value['id'] ."'>
                    	    <input 
                    	        value='". $product['code'] ."'
                    	        readonly
                    	        type='text' 
                    	        class='form-control w-25' 
                    	        placeholder='Децимальный номер' 
                    	        aria-label='Децимальный номер'/>
                    	    <input 
                    	        readonly
                    	        type='text'
                    	        value='". $product['title'] ."' 
                    	        class='form-control w-25'
                    	        placeholder='Наименование'
                    	        aria-label='Наименование'>
                    	    <input 
                    	        readonly
                    	        type='number'
                    	        value='". $value['quantity'] ."'
                    	        min='1'
                    	        class='form-control' 
                    	        placeholder='Кол-во' 
                    	        aria-label='Кол-во'>
                    	    <button class='btn btn-danger removeFromProductStructure-js' type='button'>
                    	        <i class='bi bi-trash'></i>
                    	    </button>
                    	</div>";
				}
				if( count($ret_arr) > 0 ){
					foreach ($ret_arr as $key2 => $item) {
						$product_type = sqlsingle("SELECT * FROM product_type WHERE id = ". $key2);
						if( isset($product_type['titles']) ){
							$content .= "
                                <div class='product_type' data-id='". $key2 ."'>
        						    <h4>
        						    	". $product_type['titles'] ."
                                        <span class='glyphicon glyphicon-". $product_type['glyphicon'] ."'></span>
        						    </h4>
        						    <div class='data'>". $item ."</div>
        						</div>";
						}
					}
				} else {
					$content .= '<span class="help-block">Ничего не найдено</span>';
					$msg = 'Ничего не найдено';
				}
			} else {
				$content .= '<span class="help-block">Ничего не найдено</span>';
				$msg = 'Ничего не найдено';
			}
			$content .= '<hr/>';
			echo json_encode([
				'product' => sqltab("SELECT * FROM products WHERE products.code = '". $_POST['code']  ."';")[0],
				'content' => $content,
				'message' => $msg,
				'response' => 'ok!!'
			]);
		}
		if($query == 'SearchSpecTitle' ){
			$removeEmptys = function(string $text): string{
				if($text === '' || $text === ' '){
					return false;
				}
				return true;
			};
			$readyText =
				array_filter(
					explode(' ', $_POST['title']), $removeEmptys
				);
			$readyForIn = array();
			$prevValue = '';
			foreach ($readyText as $key => $value){
				$value = str_replace('x','х', $value);
				$readyForIn[$key] = " title LIKE '%". $value . "%'"; // $prevValue . $value;
				$prevValue .= $value;
			}
			$sql = "SELECT * FROM `products` WHERE". implode(' AND ', $readyForIn) ." AND type_id = ".$_POST['type_id']." ORDER BY title DESC LIMIT 100";
			if( count($readyForIn) > 0 && $readyForIn[0] != ''){
				$response = sqltab($sql);
			} else {
				$response = null;
			}
			echo json_encode(
				[
					'response'  => $response,
					'sql'       => $sql
				]
			);
		}
		if($query == 'FindProductByCodeAndTypeId') {
			$response = array();
			$ALL      = array();
			if( $_POST['code'] != '' &&  $_POST['code'] != ' ' ){
				$sql_equipment_management =
					"SELECT * FROM products WHERE 
                     code LIKE '%".$_POST['code']."%' AND 
                     type_id = ".$_POST['type_id']." LIMIT 15;";
				$ALL = sqltab($sql_equipment_management);
				foreach ( $ALL as $key2 => $value){
					$response[] = array( "value"=>$key2, "label"=>$value['code']);
				}
			}
			echo json_encode(
				array(
					'labels'    => $response,
					'ALL'       => $ALL
					)
			);
		}
	}
}
?>