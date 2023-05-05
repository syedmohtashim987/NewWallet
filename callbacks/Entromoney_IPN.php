<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$accountQuery = $db->query("SELECT * FROM ce_gateways WHERE name='Entromoney'");
$acc = $accountQuery->fetch_assoc();
$config = array();
$config['sci_user'] = $acc['g_field_1'];
$config['sci_id'] 	= $acc['g_field_2'];
$config['sci_pass'] = $acc['g_field_3'];
$config['receiver'] = $acc['g_field_4'];
try {
	$sci = new Paygate_Sci($config);
}
catch (Paygate_Exception $e) {
	exit($e->getMessage());
}

$input = array();
$input['hash'] = $_POST['hash'];

// Decode hash
$error = '';
$tran = $sci->query($input, $error);
foreach($tran as $v => $k) {
	$trans[$v] = $k;
}
$date = date("d/m/Y H:i:s");
$status = $trans['status'];
$payment_id = $trans['payment_id'];
$receiver = $trans['account_purse'];
$sender = $trans['purse'];
$eamount = $trans['amount'];
$batch = $trans['batch'];
$query = $db->query("SELECT * FROM ce_orders WHERE id='$trans[payment_id]'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
    $amount = $row['amount_send'];
    $currency = gatewayinfo($row[gateway_send],"currency");
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$batch'");
	if($error) {
		echo $error;
	} else {
		if($status == "completed") {
			if($check_trans->num_rows==0) {
				$time = time();
				$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$batch',updated='$time' WHERE id='$row[id]'");
			}
		}
	}
}