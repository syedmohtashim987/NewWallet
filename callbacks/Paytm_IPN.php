<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");

// following files need to be included
require_once("../app/includes/payment_src/encdec_paytm.php");

$paytmChecksum = "";
$paramList = array();
$isValidChecksum = "FALSE";

$paramList = $_POST;
$ORDER_ID = protect($paramList['ORDERID']);
$AMOUNT = protect($paramList['TXNAMOUNT']);
$query = $db->query("SELECT * FROM ce_orders WHERE id='$ORDER_ID'");
if($query->num_rows==0) {
    die("Wrong ORDER id!");
}
$row = $query->fetch_assoc();
$gateway = $row['gateway_send'];
$paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg
define('PAYTM_ENVIRONMENT', 'PROD'); // PROD
define('PAYTM_MERCHANT_KEY',  gatewayinfo($gateway,"g_field_1")); //Change this constant's value with Merchant key received from Paytm.
define('PAYTM_MERCHANT_MID',  gatewayinfo($gateway,"g_field_2")); //Change this constant's value with MID (Merchant ID) received from Paytm.
define('PAYTM_MERCHANT_WEBSITE',  gatewayinfo($gateway,"g_field_3")); //Change this constant's value with Website name received from Paytm.
$PAYTM_STATUS_QUERY_NEW_URL='https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
$PAYTM_TXN_URL='https://securegw-stage.paytm.in/theia/processTransaction';
if (PAYTM_ENVIRONMENT == 'PROD') {
    	$PAYTM_STATUS_QUERY_NEW_URL='https://securegw.paytm.in/merchant-status/getTxnStatus';
    	$PAYTM_TXN_URL='https://securegw.paytm.in/theia/processTransaction';
}
define('PAYTM_REFUND_URL', '');
define('PAYTM_STATUS_QUERY_URL', $PAYTM_STATUS_QUERY_NEW_URL);
define('PAYTM_STATUS_QUERY_NEW_URL', $PAYTM_STATUS_QUERY_NEW_URL);
define('PAYTM_TXN_URL', $PAYTM_TXN_URL);
//Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application�s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
$isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.

if($isValidChecksum == "TRUE") {
	echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
	if ($_POST["STATUS"] == "TXN_SUCCESS") {
		echo "<b>Transaction status is success</b>" . "<br/>";
		//Process your transaction here as success transaction.
		//Verify amount & order id received from Payment gateway with your application's order id and amount.
					if($AMOUNT == $row['amount']) {
						$time = time();
						$update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$txn_id',updated='$time' WHERE id='$row[id]'");
						$redirect = $settings['url']."payment/success";
						header("Location: $redirect");
					}
	}
	else {
        echo "<b>Transaction status is failure</b>" . "<br/>";
        $time = time();
        $update = $db->query("UPDATE ce_orders SET status='5',transaction_send='$txn_id',updated='$time' WHERE id='$row[id]'");
		$redirect = $settings['url']."payment/fail";
						header("Location: $redirect");
	}

	if (isset($_POST) && count($_POST)>0 )
	{ 
		foreach($_POST as $paramName => $paramValue) {
				echo "<br/>" . $paramName . " = " . $paramValue;
		}
	}
	

}
else {
	echo "<b>Checksum mismatched.</b>";
	//Process transaction as suspicious.
}
?>