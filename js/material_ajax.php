<?php
const _INC = 1;
global $db;
global $auth_status;
include ("../cmsconf.php");
include( $_SERVER['DOCUMENT_ROOT'] . "/inc/material_delivery_functions.php");
sql_connect();
$status_array = sqltab_id("SELECT * FROM material_delivery_story_status");

if( $_POST['request'] == 'DemandToOpenOrders' ){
	echo json_encode(
		[
			'type'  => 'demandToOpenOrders',
			'data'  =>
				DemandToOpenOrdersTable(
					getDisposalsFromCacheMaterialsByMaterialCode($_POST['data']['material_code']),
					$_POST['data']['material_code']
				)
		]
	);
}

if( $_POST['request'] == 'InsertGroupStatus' ){
    //Необходимые параметры
    //material_delivery_group_id
    //date
    //status_id
    //user_id
	$checkResult = CheckMaterialDeliveryGroupBeforeInsertStatus( $_POST['data']['status_id'], $_POST['data']['material_delivery_group_id'] );
    if(
        $checkResult['decision'] === 'deny'
    ){
        echo json_encode(['err' => $checkResult['reason']]);
        exit;
    }

    sqlupd("
        INSERT INTO `material_delivery_story` 
            (`id`, `user_id`, `material_delivery_group_id`, `date`, `insert_date`, `material_delivery_story_status_id`) VALUES 
            (   
                NULL, 
                '".$_POST['data']['user_id']."', 
                '".$_POST['data']['material_delivery_group_id']."', 
                '".$_POST['data']['date']."', 
                CURRENT_TIMESTAMP, 
                '".$_POST['data']['status_id']."'
            )"
    );
	$DisData =
		sqltab("
			SELECT disposal_id, material_code FROM material_delivery
				RIGHT JOIN material_delivery_group mdg on material_delivery.id = mdg.material_delivery_id
				WHERE mdg.id = '".$_POST['data']['material_delivery_group_id']."'
			")[0];
    echo json_encode( [
    	    'type'      => 'updateTr',
            'data'      =>
	            [
		            'tr' =>
			            ReturnGroupArray(
				            $DisData['disposal_id'],
				            $DisData['material_code'],
				            $status_array,
				            true
			            ),
		            'badges' => GetCountMaterialsStatus( $_POST['data']['badgeMaterials'], $DisData['disposal_id'] )
	            ]
        ]
    );
}

if( $_POST['request'] == 'ApplyNewStatusForAllGroups' ) {
	$material_delivery_id =
		getMaterialDeliveryIdByDisposalIdMaterialCode(
			$_POST['data']['disposal_id'],
			$_POST['data']['material_code']
		);
	InsertStatusToExistingGroupStatusesALL(
		$material_delivery_id,
		[
			'user_id'   => $_POST['data']['user_id'],
			'date'      => $_POST['data']['date'],
			'status_id' => $_POST['data']['status_id']
		]
	);
	$result = [
		'type'    => 'updateTr',
		'data'      =>
			[
				'tr' =>
					ReturnGroupArray(
						$_POST['data']['disposal_id'],
						$_POST['data']['material_code'],
						$status_array,
						true
					),
				'badges' => GetCountMaterialsStatus( $_POST['data']['material_code'], $_POST['data']['disposal_id'] )
			]
	];
	echo json_encode($result);
}

if( $_POST['request'] == 'GroupOperation' ){
    $material_delivery_id =
        getMaterialDeliveryIdByDisposalIdMaterialCode(
            $_POST['data']['disposal_id'],
            $_POST['data']['material_code']
        );
    $norm =
        (float)getMaterialData(
            $_POST['data']['disposal_id'],
            $_POST['data']['material_code']
        )['norm'];
	$norm = $norm === 0.0 ? 0.001 : $norm;
    $quantityGroup = CalcQuantityMaterialDeliveryId( $material_delivery_id );
	if( $_POST['data']['SearchMaterialInAnotherDisposals'] == 'false' ){
		if( $_POST['data']['quantity'] == 'full' ){
			$quantity_insert = $norm - $quantityGroup;
		} else {
			$quantity_insert = (float)$_POST['data']['quantity'];
		}
	    $material_delivery_groupId =
	        sqlupd("
			        INSERT INTO `material_delivery_group` 
			        (`id`, `material_delivery_id`,  `quantity`) VALUES 
			        (NULL, '".$material_delivery_id."', '".$quantity_insert."')");
	    sqlupd("
					INSERT INTO `material_delivery_story` 
					(`id`, `user_id`,                       `material_delivery_group_id`,     `insert_date`,     `date`,                       `material_delivery_story_status_id`) VALUES 
					(NULL, '".$_POST['data']['user_id']."', '".$material_delivery_groupId."', CURRENT_TIMESTAMP, '".$_POST['data']['date']."', '". $_POST['data']['status_id'] ."')
	    ");
	    $result = [
	        'type'  => 'updateTr',
	        'data'  =>
		        [
			        'tr' =>
				        ReturnGroupArray(
					        $_POST['data']['disposal_id'],
					        $_POST['data']['material_code'],
					        $status_array,
					        true
				        ),
			        'badges' => GetCountMaterialsStatus( $_POST['data']['badgeMaterials'], $_POST['data']['disposal_id'] )
		        ]
	    ];
	} else {
	    $result = [
		    'type'      => 'modal',
		    'action'    => 'update1cCounter',
	        'data'      =>
	            ModalDisposalsMaterial(
	                $_POST['data']['material_code'],
	                [
	                    'quantity_insert'   => $_POST['data']['quantity'] ,
	                    'disposal_id'       => $_POST['data']['disposal_id'] ,
	                    'user_id'           => $_POST['data']['user_id'],
	                    'date'              => $_POST['data']['date'],
	                    'status_id'         => $_POST['data']['status_id']
	                ]
	            )
	    ];
	}
    echo json_encode($result);
}

if( $_POST['request'] == 'InsertSeveralGroupStatuses' ){
    $result = [];
    foreach ( $_POST['data']['row'] as $value ){
        $material_delivery_id =
	        getMaterialDeliveryIdByDisposalIdMaterialCode(
	        	$value['disposal_id'],
		        $_POST['data']['data']['material_code']
	        );
        $material_delivery_groupId =
            sqlupd("
                INSERT INTO `material_delivery_group` 
                (`id`, `material_delivery_id`,  `quantity`) VALUES 
                (NULL, '".$material_delivery_id."', '".$value["quantity"]."')");
        sqlupd("
            INSERT INTO `material_delivery_story` 
            (`id`, `user_id`,                               `material_delivery_group_id`,     `insert_date`,     	`date`,                       		  `material_delivery_story_status_id`	      ) VALUES 
            (NULL, '".$_POST['data']['data']['user_id']."', '".$material_delivery_groupId."', CURRENT_TIMESTAMP,  '".$_POST['data']['data']['date']."', '". $_POST['data']['data']['status_id'] ."' )
        ");
        if( $value["current"] == 'true' ){
            $result =
	            [
		            'tr' =>
			            ReturnGroupArray(
				            $value['disposal_id'],
				            $_POST['data']['data']['material_code'],
				            $status_array,
				            true
			            ),
		            'badges' => GetCountMaterialsStatus( $_POST['data']['data']['badgeMaterials'], $_POST['data']['data']['disposal_id'] )
	            ];
        }
    }
    echo json_encode(
    	[
            'type'          => 'updateTr',
            'data'          => $result
        ]
    );
}

if( $_POST['request'] == 'DeleteGroupStatus' ){
	$user_id_inDB   = sqltab("SELECT user_id FROM material_delivery_story WHERE material_delivery_story.id = ". $_POST['data']['mds_id'])[0]['user_id'];
	$user_role      = sqltab("SELECT role_id FROM material_delivery_user_roles mdur WHERE mdur.user_id = ". $_POST['data']['user_id'])[0]['role_id'];
	if(
		$user_id_inDB == $_POST['data']['user_id'] ||
		$user_role == 2
	){
		$DisData = sqltab("
			SELECT disposal_id, material_code, mdg.id as 'mdg_id' FROM material_delivery
				RIGHT JOIN material_delivery_group mdg on material_delivery.id = mdg.material_delivery_id
				RIGHT JOIN material_delivery_story mds on mdg.id = mds.material_delivery_group_id
				WHERE mds.id = '".$_POST['data']['mds_id']."'
			")[0];
		sqlupd("DELETE FROM material_delivery_story WHERE material_delivery_story.id = ". $_POST['data']['mds_id']);
		if( sqltab("SELECT COUNT(*) as count FROM material_delivery_story WHERE material_delivery_story.material_delivery_group_id = ". $DisData['mdg_id'])[0]['count'] == 0 ){
			sqlupd("DELETE FROM material_delivery_group WHERE material_delivery_group.id = ". $DisData['mdg_id'] );
		}
		echo json_encode([
			'type'  => 'updateTr',
			'data'  =>
				[
					'tr' =>
						ReturnGroupArray(
							$DisData['disposal_id'],
							$DisData['material_code'],
							$status_array,
							true
						),
					'badges' => GetCountMaterialsStatus( $_POST['data']['badgeMaterials'], $DisData['disposal_id'] )
				]
		]);
	} else {
		echo json_encode([
			'err'   => 'Удаление отменено: Статус, который вы пытаетесь удалить был установлен другим пользователем'
		]);
	}
}

if( $_POST['request'] == 'UpdateGroupStatusNote' ){
	sqlupd("UPDATE material_delivery_group SET note ='".$_POST['data']['note']."' WHERE id = ". $_POST['data']['group_id'] .";");
	echo json_encode([
		'status'   => 'ok'
	]);
}

if( $_POST['request'] == 'UploadFile' ){
	$basename = basename($_FILES["file"]["name"]);
	$target_dir = $_SERVER['DOCUMENT_ROOT'] . "/documents/material_delivery_files/mdg_id". $_POST['mdg_id']."/";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
	$sql_dir_file = "documents/material_delivery_files/mdg_id". $_POST['mdg_id']."/". $basename;
	$target_file = $target_dir . $basename;
	if ( move_uploaded_file($_FILES["file"]["tmp_name"], $target_file) ) {
		$msg = "Файл " . $basename . " был загружен.";
	}
	sqlupd("
		INSERT INTO material_delivery_files 
		    (`material_delivery_group_id`, `link`, `name`) VALUES 
			( '".$_POST['mdg_id']."', '". $sql_dir_file ."', '". $basename ."' );"
	);
	$DisData =
		sqltab("
			SELECT disposal_id, material_code FROM material_delivery
				RIGHT JOIN material_delivery_group mdg on material_delivery.id = mdg.material_delivery_id
				WHERE mdg.id = '".$_POST['mdg_id']."'
			")[0];
	echo json_encode([
		'msg'   => $msg,
		'type'  => 'updateTr',
		'data'  =>
			[
				'tr' =>
					ReturnGroupArray(
						$DisData['disposal_id'],
						$DisData['material_code'],
						$status_array,
						true
					)
			]
	]);
}

if( $_POST['request'] == 'DeleteFile'){
	$fileRoot = sqltab("SELECT link FROM `material_delivery_files` WHERE `material_delivery_files`.`id` = ". $_POST['data']['file_id'] .";")[0]['link'];
	unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $fileRoot );
	sqlupd("DELETE FROM `material_delivery_files` WHERE `material_delivery_files`.`id` = ". $_POST['data']['file_id'] .";");
	$DisData = sqltab("
			SELECT disposal_id, material_code, mdg.id as 'mdg_id' FROM material_delivery
				RIGHT JOIN material_delivery_group mdg on material_delivery.id = mdg.material_delivery_id
				RIGHT JOIN material_delivery_story mds on mdg.id = mds.material_delivery_group_id
				WHERE mds.id = '".$_POST['data']['mds_id']."'
			")[0];
	echo json_encode([
		'type'  => 'updateTr',
		'data'  =>
			[
				'tr' =>
					ReturnGroupArray(
						$DisData['disposal_id'],
						$DisData['material_code'],
						$status_array,
						true
					)
			]
	]);
}

if( $_POST['request'] == 'GetReservedQuantityByMaterialCode'){
	$return = [
		'type' => 'modal'
	];
	$material_storage_quantity   = sqltab("SELECT * FROM material_storage WHERE material_code = '".$_POST['data']."'")[0]['quantity'];
	if( $material_storage_quantity != '' ){
		$table = '
			<table class="table">
				<thead>
					<tr>
						<th>Заказ</th>
						<th>Распоряжение</th>
						<th>Количество</th>
						<th>Статус</th>
					</tr>
				</thead>
			<tbody>
			<tr class="table-info">
				<td>Всего материала</td>
				<td>-</td>
				<td>'.$material_storage_quantity.'</td>
				<td>-</td>
			</tr>
			';
		$material_delivery_groups =
			getMaterialStorageQuantity_minusInStorageByMaterialCode( $_POST['data'] );
		foreach ($material_delivery_groups['material_delivery_group'] as $mdg_id => $material_delivery_group){
			$disCode =
				sqltab("
					SELECT
						d.code as 'dCode',
						o.code as 'oCode'
					FROM disposal d
						LEFT JOIN orders o ON d.order_id = o.id
					WHERE d.id = ". $material_delivery_group['disposal_id'])[0];
			$table .= '
				<tr class="table-'.$material_delivery_group['class'].'">
					<td>'. $disCode['oCode'] .'</td>
					<td>'. $disCode['dCode'] .'</td>
					<td>'. $material_delivery_group['quantity'] .'</td>
					<td>'. $material_delivery_group['status'] .'</td>
				</tr>
				';
			}

		$table .= '
		</tbody>
	</table>';
		$return['data'] = '
			<div class="modal">
			    <div class="modal-dialog">
			        <div class="modal-content">
			            <div class="modal-header">
			                <h2 class="modal-title">Наличие материала в других распоряжениях</h2>
			            	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			            </div>
			            <div class="modal-body">'.$table.'
			            </div>
			            <div class="modal-footer">
			                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Закрыть</button>
			            </div>
			        </div>
			    </div>
			</div>';
	} else {
		$return['err'] = 'Количество материала '. $_POST['data']  .' на складе не определено';
	}
	echo json_encode(
		$return
	);
}

if( $_POST['request'] == 'MaterialDeliveryGroupCut' ){
	$group_to_copy =
		sqltab("SELECT * FROM material_delivery_group WHERE id = ". $_POST['data']['mdg_id'] )[0];
	$DisData = sqltab("SELECT * FROM material_delivery md INNER JOIN material_delivery_group mdg ON md.id = mdg.material_delivery_id WHERE mdg.id = ". $_POST['data']['mdg_id'])[0];
	if( floatval( $_POST['data']['quantity'] ) >= floatval($group_to_copy['quantity']) ){
		echo json_encode([
			'err'  => 'Невозможно произвести операцию. Превышение лимита.'
		]);
		exit;
	}
	$update_quantity = floatval($group_to_copy['quantity']) - floatval( $_POST['data']['quantity'] ) ;
	sqlupd("UPDATE material_delivery_group mdg SET quantity = '". $update_quantity ."' WHERE id = ". $_POST['data']['mdg_id']);
	$mdg_id = sqlupd("
		INSERT INTO `material_delivery_group`(`material_delivery_id`, `quantity`) VALUES 
		('". $group_to_copy['material_delivery_id'] ."', " . $_POST['data']['quantity'] . ");"
	);
	$material_delivery_story =
		sqltab("SELECT * FROM material_delivery_story WHERE material_delivery_group_id = ". $_POST['data']['mdg_id']);
	foreach( $material_delivery_story as $value ){
		sqlupd("
			INSERT INTO `material_delivery_story`
			    (
			    	`user_id`, 
			    	`material_delivery_group_id`, 
			    	`date`, 
			    	`insert_date`, 
			    	`material_delivery_story_status_id`
			    ) VALUES (
			    	'". $value['user_id'] ."',
			    	'". $mdg_id ."',
			    	'". $value['date'] ."',
			    	'". $value['insert_date'] ."',
			    	'". $value['material_delivery_story_status_id'] ."'
			    )"
		);
	}
	echo json_encode([
		'type'  => 'updateTr',
		'data'  =>
			[
				'tr' =>
					ReturnGroupArray(
						$DisData['disposal_id'],
						$DisData['material_code'],
						$status_array,
						true
					)
			]
	]);
}
?>