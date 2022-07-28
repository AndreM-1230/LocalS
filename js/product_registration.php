<?php
define("_INC", 1);
global $db;
include ("../cmsconf.php");
sql_connect();

if( $_POST['request'] === 'InsertProductHeaderIntoSession' ){
	$result = [];
	foreach($_POST['data']['queries'] as $product_code){
		$result[] = sqlupd_no_echo("
			INSERT INTO product_registration_session_heading_products 
			    (`product_registration_session_id`, `product_code`) VALUES 
			    (".$_POST['data']['session_id'].", '".$product_code."')
		");
	}
	echo json_encode($result);
}

if( $_POST['request'] === 'InsertProductsInbox' ){
	$result = [];
	foreach($_POST['data']['heading_product_id_array'] as $heading_product_id){
		foreach($_POST['data']['products'] as $product){
			$result[] = sqlupd_no_echo("
				INSERT INTO product_registration_session_products 
				    (`heading_product_id`, `product_code`, `qty`, `action`) VALUES 
				    (".$heading_product_id.", '".$product['product_code']."', '".$product['quantity']."', '". $product['action'] ."')
			");
		}
	}
	echo json_encode($result);
}

if( $_POST['request'] === 'DeleteSessionProductHeader' ){
	echo json_encode( sqlupd_no_echo("DELETE FROM product_registration_session_heading_products WHERE id = ". $_POST['data']['prshp_id'] . ";") );
}

if( $_POST['request'] === 'FindSessionHeaderProducts' ){
	echo json_encode(
		sqltab("
			SELECT 
	       		prshp.product_code 	as 'productCode', 
		       	p.id 				as 'product_id', 
		       	prshp.id 			as 'prshp_id',
			    COUNT(prsp.heading_product_id)		as 'childrenCount'
			FROM product_registration_session prs
			    RIGHT JOIN product_registration_session_heading_products prshp on prs.id = prshp.product_registration_session_id
				LEFT JOIN product_registration_session_products prsp ON prshp.id = prsp.heading_product_id
			    LEFT JOIN products p ON prshp.product_code = p.code 
			WHERE prs.id = ". $_POST['data']['session_id'] . " 
				GROUP BY 
					prshp.date_insert,
					prshp.id
				ORDER BY  
					/*date_insert DESC,*/
					prshp_id ASC"
		)
	);
}

if( $_POST['request'] === 'DeleteProductInbox' ) {
	echo json_encode(
		sqlupd_no_echo("
			DELETE FROM product_registration_session_products 
		    WHERE id = ". $_POST['data']['prsp_id']
		)
	);
}

if( $_POST['request'] === 'FindInboxProducts' ){
	echo json_encode(
		sqltab("
			SELECT 
	       		prsp.product_code 	as 'productCode', 
	       		prsp.id 			as 'prsp_id', 
	       		prsp.action 		as 'action', 
	       		prsp.qty 			as 'quantity', 
		       	p.id 				as 'product_id'
			FROM product_registration_session_products prsp
			    LEFT JOIN products p on p.code = prsp.product_code
		    WHERE prsp.heading_product_id = ". $_POST['data']['prshp_id'] . " 
				ORDER BY prsp_id DESC"
		)
	);
}
?>