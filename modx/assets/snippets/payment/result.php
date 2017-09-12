<?php

	include_once $_SERVER['DOCUMENT_ROOT'].'/manager/includes/config.inc.php';
	include_once MODX_MANAGER_PATH.'includes/document.parser.class.inc.php';
	require_once MODX_BASE_PATH."assets/snippets/payment/config/platron.php";
	require_once MODX_BASE_PATH."assets/snippets/payment/include/PG_Signature.php";

	$modx = new DocumentParser;
	$mod_table = $modx->db->config['table_prefix']."manager_shopkeeper";

	$arrStatuses = array(
		'Pending'	=> 2,
		'Fail'	=> 5,
		'Success'	=> 6,
	);
	
	if(!empty($_POST))
		$arrRequest = $_POST;
	else
		$arrRequest = $_GET;

	
	$thisScriptName = PG_Signature::getOurScriptName();
	if (empty($arrRequest['pg_sig']) || !PG_Signature::check($arrRequest['pg_sig'], $thisScriptName, $arrRequest, PL_SECRET_KEY))
		die("Wrong signature");

	$order_id = $_REQUEST['pg_order_id'];
	$dbOrder = $modx->db->getRow($modx->db->select("id, short_txt, content, allowed, addit, price, currency, DATE_FORMAT(date,'%d.%m.%Y %k:%i') AS date, status, email, phone, payment, tracking_num,  userid", $mod_table, "id = $order_id", "", ""));
	
	if(!isset($arrRequest['pg_result'])){
		$bCheckResult = 0;
		if(empty($dbOrder) || $dbOrder['status'] != $arrStatuses['Pending'])
			$error_desc = "Товар не доступен. Либо заказа нет, либо его статус " . array_search($dbOrder['status'], $arrStatuses);	
		elseif(sprintf('%0.2f',$arrRequest['pg_amount']) != sprintf('%0.2f', $dbOrder['price']))
			$error_desc = "Неверная сумма";
		else
			$bCheckResult = 1;

		$arrResponse['pg_salt']              = $arrRequest['pg_salt']; // в ответе необходимо указывать тот же pg_salt, что и в запросе
		$arrResponse['pg_status']            = $bCheckResult ? 'ok' : 'error';
		$arrResponse['pg_error_description'] = $bCheckResult ?  ""  : $error_desc;
		$arrResponse['pg_sig']				 = PG_Signature::make($thisScriptName, $arrResponse, PL_SECRET_KEY);

		$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
		$objResponse->addChild('pg_salt', $arrResponse['pg_salt']);
		$objResponse->addChild('pg_status', $arrResponse['pg_status']);
		$objResponse->addChild('pg_error_description', $arrResponse['pg_error_description']);
		$objResponse->addChild('pg_sig', $arrResponse['pg_sig']);

	}
	else{
		$bResult = 0;
		if(empty($dbOrder) || 
				(($dbOrder['status'] != $arrStatuses['Pending']) &&
				!($dbOrder['status'] != $arrStatuses['Success'] && $arrRequest['pg_result'] == 1) && 
				!($dbOrder['status'] != $arrStatuses['Fail'] && $arrRequest['pg_result'] == 0)))

			$strResponseDescription = "Товар не доступен. Либо заказа нет, либо его статус " . array_search($dbOrder['status'], $arrStatuses);		
		elseif(sprintf('%0.2f',$arrRequest['pg_amount']) != sprintf('%0.2f',$dbOrder['price']))
			$strResponseDescription = "Неверная сумма";
		else {
			$bResult = 1;
			$strResponseStatus = 'ok';
			$strResponseDescription = "Оплата принята";
			if ($arrRequest['pg_result'] == 1) {
				// Удачная оплата
				$update_arr = array(
                    'status' => $arrStatuses['Success']
                );
                $modx->db->update($update_arr, $mod_table, "id = $order_id");
                $modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$order_id,'status'=>$arrStatuses['Success']));
			}
			else{
				// Не удачная оплата
				$update_arr = array(
                    'status' => $arrStatuses['Fail']
                );
                $modx->db->update($update_arr, $mod_table, "id = $order_id");
                $modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$order_id,'status'=>$arrStatuses['Fail']));
			}
		}
		if(!$bResult)
			if($arrRequest['pg_can_reject'] == 1)
				$strResponseStatus = 'rejected';
			else
				$strResponseStatus = 'error';

		$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
		$objResponse->addChild('pg_salt', $arrRequest['pg_salt']); // в ответе необходимо указывать тот же pg_salt, что и в запросе
		$objResponse->addChild('pg_status', $strResponseStatus);
		$objResponse->addChild('pg_description', $strResponseDescription);
		$objResponse->addChild('pg_sig', PG_Signature::makeXML($thisScriptName, $objResponse, PL_SECRET_KEY));
	}

	header("Content-type: text/xml");
	echo $objResponse->asXML();
	die();
?>
