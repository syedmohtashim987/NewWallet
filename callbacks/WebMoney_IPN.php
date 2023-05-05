<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

require_once '../app/includes/payment_src/webmoney.inc.php';
$wm_prerequest = new WM_Prerequest();
if ($wm_prerequest->GetForm() == WM_RES_OK) {
	$orderid = $wm_prerequest->payment_no;
	$merchant = $wm_prerequest->payee_purse;
	$send_amount = $wm_prerequest->payment_amount;
	$trans_id = $wm_prerequest->sys_trans_no;
	$date = $wm_prerequest->sys_trans_date;
	$payer = $wm_prerequest->payer_wm;
	$query = $db->query("SELECT * FROM ce_orders WHERE id='$orderid'");
	if($query->num_rows>0) {
		$row = $query->fetch_assoc();
		$amount = $row['amount_send'];
		$currency = gatewayinfo($row[gateway_send],"currency");
		if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
		$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$trans_id'");
		if (
			$wm_prerequest->payment_no == $row['id'] &&
			$wm_prerequest->payee_purse == gatewayinfo($row['gateway_send'],"g_field_1") &&
			$wm_prerequest->payment_amount == $amount
		)
		{
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$trans_id',updated='$time' WHERE id='$row[id]'");
			}
		}
	}
}
header("Location: $settings[url]");			
?>