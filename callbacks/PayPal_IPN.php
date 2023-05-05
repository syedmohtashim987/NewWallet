<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);

// assign posted variables to local variables
$item_name = protect($_POST['item_name']);
$item_number = protect($_POST['item_number']);
$payment_status = protect($_POST['payment_status']);
$payment_amount = protect($_POST['mc_gross']);
$payment_currency = protect($_POST['mc_currency']);
$txn_id = protect($_POST['txn_id']);
$receiver_email = protect($_POST['receiver_email']);
$payer_email = protect($_POST['payer_email']);
$query = $db->query("SELECT * FROM ce_orders WHERE id='$item_number'");
if($query->num_rows>0) {
	$row = $query->fetch_assoc();
	$date = date("d/m/Y H:i:s");
    $amount = $row['amount_send'];
	$currency = gatewayinfo($row[gateway_send],"currency");
	if(checkSession()) { $uid = $_SESSION['eex_uid']; } else { $uid = 0; }
	$check_trans = $db->query("SELECT * FROM ce_orders WHERE transaction_send='$txn_id'");
	if (!$fp) {
		echo error("Cant connect to PayPal server.");
	} else {
		fputs ($fp, $header . $req);
		while (!feof($fp)) {
		$res = fgets ($fp, 1024);
		if (strcmp ($res, "VERIFIED") == 1) {
			if ($payment_status == 'Completed') {
				if ($receiver_email==gatewayinfo($row['gateway_send'],"g_field_1")) {
					if($payment_amount == $amount && $payment_currency == $currency) {
						if($check_trans->num_rows==0) {
					        $time = time();
							$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$txn_id',updated='$time' WHERE id='$row[id]'");
						}
					}
													
				} 

			}

	}

	else if (strcmp ($res, "INVALID") == 0) {
		echo 'Invalid payment.';
	}
	}
	fclose ($fp);
	}  
}
?>