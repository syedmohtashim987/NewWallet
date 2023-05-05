<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$ac_src_wallet = protect($_GET['ac_src_wallet']);
$ac_dest_wallet = protect($_GET['ac_dest_wallet']);
$ac_amount = protect($_GET['ac_amount']);
$ac_merchant_currency = protect($_GET['ac_merchant_currency']);
$ac_transfer = protect($_GET['ac_transfer']);
$ac_start_date = protect($_GET['ac_start_date']);
$ac_order_id = protect($_GET['ac_order_id']);
$query = $db->query("SELECT * FROM ce_orders WHERE id='$ac_order_id'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
    $amount = $row['amount_send'];
	$currency = gatewayinfo($row[gateway_send],"currency");
	$fee_text = '0';
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$ac_transfer'");
	if($ac_dest_wallet == gatewayinfo($row['gateway_send'],"g_field_1")) {
		if($ac_amount == $amount or $ac_merchant_currency == $currency) {
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$ac_transfer',updated='$time' WHERE id='$row[id]'");
			}
		}
	}
}