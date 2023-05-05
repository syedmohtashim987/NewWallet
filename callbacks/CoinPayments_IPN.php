<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");
//include(getLanguage($settings['url'],null,2));
$order_id = protect($_GET['order_id']);
if(!empty($order_id)) {   
    if(orderinfo($order_id,"id")) {
    // Fill these in with the information from your CoinPayments.net account.
    $cp_merchant_id = gatewayinfo(orderinfo($order_id,"gateway_send"),"g_field_3");
    $cp_ipn_secret = gatewayinfo(orderinfo($order_id,"gateway_send"),"g_field_4");
    $cp_debug_email = $settings['supportemail'];

    //These would normally be loaded from your database, the most common way is to pass the Order ID through the 'custom' POST field.
    $order_currency = orderinfo($order_id,"currency_from");
    $order_total = orderinfo($order_id,"amount_send");

    function errorAndDie($error_msg) {
        global $cp_debug_email;
        if (!empty($cp_debug_email)) {
            $report = 'Error: '.$error_msg."\n\n";
            $report .= "POST Data\n\n";
            foreach ($_POST as $k => $v) {
                $report .= "|$k| = |$v|\n";
            }
            mail($cp_debug_email, 'CoinPayments IPN Error', $report);
        }
        die('IPN Error: '.$error_msg);
    }

    if (!isset($_POST['ipn_mode']) || $_POST['ipn_mode'] != 'hmac') {
        errorAndDie('IPN Mode is not HMAC');
    }

    if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
        errorAndDie('No HMAC signature sent.');
    }

    $request = file_get_contents('php://input');
    if ($request === FALSE || empty($request)) {
        errorAndDie('Error reading POST data');
    }

    if (!isset($_POST['merchant']) || $_POST['merchant'] != trim($cp_merchant_id)) {
        errorAndDie('No or incorrect Merchant ID passed');
    }

    $hmac = hash_hmac("sha512", $request, trim($cp_ipn_secret));
    if (!hash_equals($hmac, $_SERVER['HTTP_HMAC'])) {
    //if ($hmac != $_SERVER['HTTP_HMAC']) { <-- Use this if you are running a version of PHP below 5.6.0 without the hash_equals function
        errorAndDie('HMAC signature does not match');
    }
    
    // HMAC Signature verified at this point, load some variables.

    $txn_id = $_POST['txn_id'];
    $item_name = $_POST['item_name'];
    $item_number = $_POST['item_number'];
    $amount1 = floatval($_POST['amount1']);
    $amount2 = floatval($_POST['amount2']);
    $currency1 = $_POST['currency1'];
    $currency2 = $_POST['currency2'];
    $status = intval($_POST['status']);
    $status_text = $_POST['status_text'];

    //depending on the API of your system, you may want to check and see if the transaction ID $txn_id has already been handled before at this point

    // Check the original currency to make sure the buyer didn't change it.
    if ($currency1 != $order_currency) {
        errorAndDie('Original currency mismatch! ORDER ID: '.$order_id);
    }    
    
    // Check amount against order total
    if ($amount1 < $order_total) {
        errorAndDie('Amount is less than order total! ORDER ID: '.$order_id);
    }
  
    if ($status >= 100 || $status == 2) {
        // payment is complete or queued for nightly payout, success
        $update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$txn_id' WHERE id='$order_id'");
    } else if ($status < 0) {
        //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
        $update = $db->query("UPDATE ce_orders SET status='5' WHERE id='$order_id'");
    } else {
        //payment is pending, you can optionally add a note to the order page
        $update = $db->query("UPDATE ce_orders SET status='2',transaction_send='$txn_id' WHERE id='$order_id'");
    }
    die('IPN OK'); 
    }
}
?>