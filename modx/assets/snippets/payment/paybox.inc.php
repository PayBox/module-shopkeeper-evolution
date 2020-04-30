<?php

require_once MODX_BASE_PATH."assets/snippets/payment/config/paybox.php";
require_once MODX_BASE_PATH."assets/snippets/payment/include/PG_Signature.php";

if ($_SESSION['shk_payment_method'] != 'paybox'){}
else{
	$dbprefix = $modx->db->config['table_prefix'];
	$mod_table = $dbprefix."manager_shopkeeper";
	$order_id = $_SESSION['shk_order_id'];
	$amount = $modx->db->getValue($modx->db->select("price", $mod_table, "id = $order_id", "", ""));
	$serialize_content = $modx->db->getValue($modx->db->select("content", $mod_table, "id = $order_id", "", ""));
	$serialize_short_txt = $modx->db->getValue($modx->db->select("short_txt", $mod_table, "id = $order_id", "", ""));
	$order_info = unserialize($serialize_short_txt);

	// В этих полях лежат по умолчанию значения. Возможно это придется поменять
	$strDescription = '';
	foreach(unserialize($serialize_content) as $arrItem){
		$strDescription .= $arrItem[3];
		if($arrItem[1] > 1)
			$strDescription .= "*".$arrItem[1];
		$strDescription .= "; ";
	}

	$strLang = 'en';
	$bRussian = strpos('russian',$modx->config['manager_language']);
	if(isset($bRussian))
		$strLang = 'ru';

	$arrFields = array(
		'pg_merchant_id'		=> PL_MERCHANT_ID,
		'pg_order_id'			=> $_SESSION['shk_order_id'],
		'pg_currency'			=> PL_CURRENCY_CODE,
		'pg_amount'				=> number_format($amount, 2, '.', ''),
		'pg_lifetime'			=> (int)PL_LIFETIME*60,
		'pg_testing_mode'		=> (PL_TEST_MODE)? 1 : 0 ,
		'pg_description'		=> $strDescription,
		'pg_user_ip'			=> $_SERVER['REMOTE_ADDR'],
		'pg_language'			=> $strLang,
		'pg_check_url'			=> 'http://'.PL_DOMAIN_URL.'/assets/snippets/payment/result.php',
		'pg_result_url'			=> 'http://'.PL_DOMAIN_URL.'/assets/snippets/payment/result.php',
		'pg_success_url'		=> PL_SUCCESS_URL,
		'pg_failure_url'		=> PL_FAIL_URL,
		'pg_request_method'		=> 'GET',
		'pg_salt'				=> rand(21,43433), // Параметры безопасности сообщения. Необходима генерация pg_salt и подписи сообщения.
	);

	if(!empty($order_info['phone'])){
		preg_match_all("/\d/", $order_info['phone'], $array);
		$strPhone = implode('',@$array[0]);
		$arrFields['pg_user_phone'] = $strPhone;
	}

	if(!empty($order_info['email'])){
		$arrFields['pg_user_email'] = $order_info['email'];
		$arrFields['pg_user_contact_email'] = $order_info['email'];
	}

	$arrFields['pg_sig'] = PG_Signature::make('payment.php', $arrFields, PL_SECRET_KEY);

	$change_status = $modx->db->update(array('status' => 2), $mod_table, "id = $order_id");
	$modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$order_id,'status'=>2));

	$output  = "<form action='https://api.paybox.money/payment.php' method='POST'>";

	foreach($arrFields as $strName => $strKey){
		$output .= "<input type='hidden' name='".$strName."' value='".$strKey."'>";
	}
	$output .= "<input type='submit' value='Оплатить сейчас'></form>";

	return $output;
}
?>
