<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$transaction_id = protect($_POST['transaction_id']);
$merchant_id = protect($_POST['pay_to_email']);
$item_number = protect($_POST['detail1_text']);
$item_name = protect($_POST['detail1_description']);
$mb_amount = protect($_POST['mb_amount']);
$mb_currency = protect($_POST['mb_currency']);
$date = date("d/m/Y H:i:s");
$query = $db->query("SELECT * FROM ce_orders WHERE id='$item_number'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
	$amount = $row['amount_send'];
	$currency = gatewayinfo($row[gateway_send],"currency");
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$a_field_1 = gatewayinfo($row['gateway_send'],"g_field_1");
	$a_field_2 = gatewayinfo($row['gateway_send'],"g_field_2");
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$transaction_id'");
	$concatFields = $_POST['merchant_id']
		.$_POST['transaction_id']
		.strtoupper(md5($a_field_2))
		.$_POST['mb_amount']
		.$_POST['mb_currency']
		.$_POST['status'];

	$MBEmail = $a_field_1;

	// Ensure the signature is valid, the status code == 2,
	// and that the money is going to you
	if (strtoupper(md5($concatFields)) == $_POST['md5sig']
		&& $_POST['status'] == 2
		&& $_POST['pay_to_email'] == $MBEmail)
	{
		// payment successfully...
		if($mb_amount == $amount && $mb_currency == $currency) {
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$transaction_id',updated='$time' WHERE id='$row[id]'");
				$redirect = $settings['url']."payment/success";
				header("Location: $redirect");
			}
		}
	}
}			
header("Location: $settings[url]");			
?>