<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$tr_id = protect($_POST['tr_id']);
$eamount = protect($_POST['amount']);
$ecurrency = protect($_POST['currency']);
$orderid = protect($_POST['user1']);
$merchant = protect($_POST['merchantAccount']);
$query = $db->query("SELECT * FROM ce_orders WHERE id='$orderid'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
	$amount = $row['amount_send'];
	$currency = gatewayinfo($row[gateway_send],"currency");
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$tr_id'");
	$sci_pwd = gatewayinfo($row[gateway_send],"g_field_2");
	$sci_pwd = md5($sci_pwd.'s+E_a*');  //encryption for db
	$hash_received = MD5($_POST['tr_id'].":".MD5($sci_pwd).":".$_POST['amount'].":".$_POST['merchantAccount'].":".$_POST['payerAccount']);

	if ($hash_received == $_POST['hash']) {
		if($merchant == gatewayinfo($row['gateway_send'],"g_field_1")) {
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$tr_id',updated='$time' WHERE id='$row[id]'");
				$redirect = $settings['url']."payment/success";
				header("Location: $redirect");
			}
		}
	}
}
header("Location: $settings[url]");			
?>