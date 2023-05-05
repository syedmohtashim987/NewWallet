<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

if (isset($_POST['m_operation_id']) && isset($_POST['m_sign'])) {
	$m_operation_id = protect($_POST['m_operation_id')];
	$m_operation_date = protect($_POST['m_operation_date']);
	$m_orderid = protect($_POST['m_orderid']);
	$m_amount = protect($_POST['m_amount']);
	$m_currency = protect($_POST['m_curr']);
	$query = $db->query("SELECT * FROM ce_orders WHERE id='$m_orderid'");
		if($query->num_rows>0) {
			$row = $query->fetch_assoc();
			$amount = $row['amount_send'];
			$currency = gatewayinfo($row[gateway_send],"currency");
			if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
			$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$m_operation_id'");
			$m_key = gatewayinfo($row['gateway_send'],"g_field_2");
			$arHash = array($_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key);
			$sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
			if ($_POST['m_sign'] == $sign_hash && $_POST['m_status'] == 'success') {
				if($m_amount == $amount or $m_currency == $currency) {
					if($check_trans->num_rows==0) {
						$time = time();
						$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$m_operation_id',updated='$time' WHERE id='$row[id]'");
                    }
				}
			}
	}
}
?>