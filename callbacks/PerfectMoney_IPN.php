<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$orderid = protect($_POST['PAYMENT_ID']);
$eamount = protect($_POST['PAYMENT_AMOUNT']);
$ecurrency = protect($_POST['PAYMENT_UNITS']);
$buyer = protect($_POST['PAYEE_ACCOUNT']);
$trans_id = protect($_POST['PAYMENT_BATCH_NUM']);
$date = date("d/m/Y H:i:s");
$query = $db->query("SELECT * FROM ce_orders WHERE id='$orderid'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
	$amount = $row['amount_send'];
	$currency = gatewayinfo($row[gateway_send],"currency");
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$trans_id'");
	$passpharce = gatewayinfo($row['gateway_send'],"g_field_2");
	$alternate = strtoupper(md5($passpharce));
	$string=
		$_POST['PAYMENT_ID'].':'.$_POST['PAYEE_ACCOUNT'].':'.
		$_POST['PAYMENT_AMOUNT'].':'.$_POST['PAYMENT_UNITS'].':'.
		$_POST['PAYMENT_BATCH_NUM'].':'.
		$_POST['PAYER_ACCOUNT'].':'.$alternate.':'.
		$_POST['TIMESTAMPGMT'];
	$hash=strtoupper(md5($string));
	if($hash==$hash){ // proccessing payment if only hash is valid

		/* In section below you must implement comparing of data you recieved
		with data you sent. This means to check if $_POST['PAYMENT_AMOUNT'] is
		particular amount you billed to client and so on. */

		if($_POST['PAYMENT_AMOUNT']==$amount && $_POST['PAYEE_ACCOUNT']==gatewayinfo($row['gateway_send'],"g_field_1") && $_POST['PAYMENT_UNITS']==$currency){
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$trans_id',updated='$time' WHERE id='$row[id]'");
			}
		}							 
	}
}
?>