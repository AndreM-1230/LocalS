<?php
define("_INC", 1);
global $db;
include ("../cmsconf.php");
sql_connect();
$post = file_get_contents('php://input');
if( $post !== '' ){
	$_POST = json_decode($post, true);
}

if( $_POST['request'] === 'GetDbTime'){
	echo json_encode(sqltab("SELECT now() as 'time'")[0]);
}

if( $_POST['request'] === 'GetTableData' ){
	$where_array = [];
	$where_array[] = 'mt.measuring_tool_status_id = 1';
	if( $_POST['time_from_last_call'] !== '' ){
		$where_array[] = 'last_updated > "'.  $_POST['time_from_last_call'].'"';
	}
	if( $_POST['title'] !== '' ){
		$where_array[] = 'mt.title LIKE "%'.  $_POST['title'].'%"';
	}
	if( $_POST['radioSI'] !== 'All' ){
		if( $_POST['radioSI'] === 'SdatochniePribori' ){
			$where_array[] = 'mt.disposal_id IS NULL AND mt.product_id IS NULL';
		}
		if( $_POST['radioSI'] === 'DeistvuyushieZakazi' ){
			$where_array[] = 'mt.disposal_id IS NOT NULL AND mt.product_id IS NOT NULL';
		}
	}
	$where_implode = '';
	if( count($where_array) > 0 ){
		$where_implode = 'WHERE ' . implode(' AND ', $where_array);
	}

	$count = ( (int)$_POST['page'] - 1 ) * 100;
	$limit = 'LIMIT '.  $count  . ', 100';

	$sql = "
		SELECT 
			mt.id 									 	as 'id', 
			mt.product_id 								as 'product_id', 
			mt.disposal_id 								as 'disposal_id', 
			mt.quantity								 	as 'quantity', 
			mt.title 								 	as 'title', 
		    mt.type 								 	as 'type', 
		    mt.factory_number 						 	as 'factory_number', 
		    mt.storage_title						 	as 'storage_title',
		    mat.title 								 	as 'attestation', 
		    mt.inventory_number						 	as 'inventory_number', 
		    mt.metrological_number					 	as 'metrological_number',
		    msc.date_control 						 	as 'date_control_sort',
		    DATE_FORMAT(msc.date_control, '%d.%m.%Y') 	as 'date_control', 
		    msw.recipient							 	as 'recipient', 
			mp.title    							 	as 'place_title',
		    mts.visible								 	as 'visible'
		FROM measuring_tool mt 
			LEFT JOIN measuring_place mp ON mt.measuring_place_id = mp.id
			LEFT JOIN measuring_story_control msc ON 
				msc.measuring_tool_id = mt.id AND
				(SELECT MAX(date_control) FROM measuring_story_control msc_alias 
				WHERE msc_alias.measuring_tool_id = mt.id) = msc.date_control
			LEFT JOIN measuring_attestation_types mat ON msc.measuring_attestation_type_id = mat.id
			LEFT JOIN measuring_story_worker msw ON 
				msw.measuring_tool_id = mt.id AND
				(SELECT MAX(date) FROM measuring_story_worker msw_alias 
				WHERE msw_alias.measuring_tool_id = mt.id) = msw.date
			LEFT JOIN measuring_tool_status mts on mt.measuring_tool_status_id = mts.id
		".$where_implode." 
		GROUP BY mt.id
		ORDER BY 
			last_updated DESC 
		".$limit." 
		;";

	$tab = sqltab($sql);
	$rows = [];
	foreach ($tab as $key => $row) {
		$tr_class = "class='tr";
		$delta_time = '';
		$days_deadline = '';
		if($row['date_control']){
			$sym='+';
			$date1 = date_create_from_format('d.m.Y', date('d.m.Y'));
			$date2 = date_create_from_format('d.m.Y', $row['date_control']);
			$delta_time = (array) date_diff($date1, $date2);
			if($delta_time['m'] == 0 && $delta_time['y'] == 0 && $delta_time['d'] <= 10){
				$tr_class .= ' table-warning';
			}
			if( $delta_time['invert'] == 1 ){
				$sym='-';
				$tr_class .= ' table-danger';
			}
			$delta_time_html = '<span class="input-group-text" style="padding: 1px">';
			if($delta_time['y'] > 0){
				$delta_time_html .= $sym.$delta_time['y'].'г. ';
				$sym = '';
			}
			if($delta_time['m'] > 0){
				$delta_time_html .= $sym.$delta_time['m'].'мес. ';
				$sym = '';
			}
			if($delta_time['d'] > 0){
				$delta_time_html .= $sym.$delta_time['d'].'д. ';
				$sym = '';
			}
			$delta_time_html .= '</span>';
			$days_deadline = $delta_time_html;
		}
		$btn_edit_tool          = "<a target='_blank' href='measuring-tools-management/tool/".$row['id']               ."' class='btn btn-sm btn-outline-warning'><i class='bi bi-pen'></i></a>";
		$btn_add_story_control  = "<a target='_blank' href='measuring-tools-management/story-control/".$row['id']      ."' class='btn btn-sm btn-outline-primary'><i class='bi bi-pencil'></i></a>";
		$btn_add_story_worker   = "<a target='_blank' href='measuring-tools-management/story-worker/".$row['id']       ."' class='btn btn-sm btn-outline-primary'><i class='bi bi-pencil'></i></a>";
		$storage                = "<input class='form-control storage_title' value='".$row['storage_title']."'/>";
		$date_control           = "<div class='input-group'><input class='form-control' disabled value='".$row['date_control'] ."'/>".$days_deadline.$btn_add_story_control."</div>";
		$recipient              = "<div class='input-group'><input class='form-control' disabled value='".$row['recipient']    ."'/>".$btn_add_story_worker."</div>";
		$title                  = "<div class='input-group'><input class='form-control' disabled value='".$row['title']        ."'/><span class='input-group-text'>".$row['quantity']."</span>".$btn_edit_tool."</div>";

		if( $row['product_id'] != '' && $row['disposal_id'] != '' ){
			$control_tab = '<i class="bi bi-dash-circle" title="Используется в заказе или изделии"></i>';
			$tr_class .= " table-info";
		} else {
			$control_tab            = '  
  			<div class="btn-group" role="group">
    			<button id="btnGroupDrop'.$row['id'].'" type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> 
    				<i class="bi bi-gear"></i>
    			</button>
    			<ul class="dropdown-menu" aria-labelledby="btnGroupDrop'.$row['id'].'">
    				<!--<li><a class="dropdown-item" href="#">ИД - '.$row['id'].'</a></li>-->
    				<li><a class="dropdown-item" target="_blank" href="measuring-tools-management/using-in-products/'.$row['id'].'">Использование в изделиях</a></li>
    				<li><hr class="dropdown-divider"></li>
    				<li><button type="button" data-status_id="3" class="mt_row_action dropdown-item">Списать</button></li>
    				<li><button type="button" data-status_id="2" class="mt_row_action dropdown-item">Удалить из базы данных</button></li>
    			</ul>
  			</div>';
		}

		$tr_class .="'";
		$btnradioP = '';
		$btnradioK = '';
		if($row['attestation'] == 'Калибровка'){
			$btnradioK = 'checked';
		}
		if($row['attestation'] == 'Поверка'){
			$btnradioP = 'checked';
		}
		$attestation            = '
			<div class="btn-group" role="group">
				<input disabled type="radio" class="btn-check attestation" name="btnradio'.$row['id'].'" id="btnradioP_'.$row['id'].'" '.$btnradioP.' value="Поверка">
				<label class="btn btn-outline-primary" for="btnradioP_'.$row['id'].'">П</label>
			
				<input disabled type="radio" class="btn-check attestation" name="btnradio'.$row['id'].'" id="btnradioK_'.$row['id'].'" '.$btnradioK.' value="Калибровка">
				<label class="btn btn-outline-primary" for="btnradioK_'.$row['id'].'">К</label>
			</div>';
		$type =                "<input data-cell='type' class='mt_text_cell type form-control' value='".$row['type']."'>";
		$factory_number =      "<input data-cell='factory_number' class='mt_text_cell factory_number form-control' value='".$row['factory_number']."'>";
		$inventory_number =    "<input data-cell='inventory_number' class='mt_text_cell inventory_number form-control' value='".$row['inventory_number']."'>";
		$metrological_number = "<input data-cell='metrological_number' class='mt_text_cell metrological_number form-control' value='".$row['metrological_number']."'>";
		$rows[] = [
			'id'        => $row['id'],
			'visible'   => (bool)$row['visible'],
			'data'  => "<tr ".$tr_class." data-id='".$row['id'] ."' data-title='".mb_strtolower($row['title']) ."'>
<td>".$control_tab                              ."</td>
<td>".$title                                    ."</td>
<td>".$attestation                              ."</td>
<td>".$type                                     ."</td>
<td>".$factory_number                           ."</td>
<td>".$inventory_number                         ."</td>
<td>".$metrological_number                      ."</td>
<td>".$date_control                             ."</td>
<td>".$recipient                                ."</td>
<td>".$storage                                  ."</td></tr>"
			];
	}
	echo json_encode(['rows'=>$rows, 'page' => $_POST['page'], 'sql' => $sql]);
}
if( $_POST['request'] === 'GetMeasuringTools' ){
	echo
		json_encode(
			sqltab("
				SELECT 
		       		measuring_tool.id as 'id', 
				    measuring_tool.title as 'title' 
				FROM measuring_tool 
				WHERE title LIKE '%".$_POST['title']."%' LIMIT 15;"
			)
		);
}
if( $_POST['request'] === 'GetMeasuringToolByTitle' ){
	$result =
		sqltab("
			SELECT 
	       		mt.*, 
			    mp.title as 'mp_title'
			    FROM measuring_tool mt
				LEFT JOIN measuring_place mp on mt.measuring_place_id = mp.id
			WHERE mt.title='".$_POST['title']."';")[0];
	$result['files'] = sqltab("SELECT * FROM measuring_tool_document WHERE measuring_tool_id=". $result['id']);
	echo json_encode($result);
}
if( $_POST['request'] === 'UpdateStorageByMeasuringToolId' ){
	echo json_encode(
		sqlupd_no_echo("UPDATE measuring_tool SET storage_title='".$_POST['title']."', last_updated=now() WHERE id=".$_POST['id'].";")
	);
}
if( $_POST['request'] === 'UpdateStatusByMeasuringToolId' ){
	echo json_encode(
		sqlupd_no_echo("UPDATE measuring_tool SET measuring_tool_status_id='".$_POST['status_id']."', last_updated=now() WHERE id=".$_POST['id'].";")
	);
}
if( $_POST['request'] === 'UpdateTextCellByMeasuringToolId' ){
	echo json_encode(
		sqlupd_no_echo("UPDATE measuring_tool SET ".$_POST['cell']."='".$_POST['value']."', last_updated=now() WHERE id=".$_POST['id'].";")
	);
}
?>