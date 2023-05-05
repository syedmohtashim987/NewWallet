<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");
//include(getLanguage($settings['url'],null,2));
$deposit_id = protect($_GET['deposit_id']);
if(!empty($deposit_id)) {   
    $CheckDeposit = $db->query("SELECT * FROM ce_invest_deposits WHERE id='$deposit_id'");
    if($CheckDeposit->num_rows>0) {
    $de = $CheckDeposit->fetch_assoc();
    $PlanInfo = $db->query("SELECT * FROM ce_invest_plans WHERE id='$de[package_id]'");
    $plan = $PlanInfo->fetch_assoc();
    // Fill these in with the information from your CoinPayments.net account.
    $cp_merchant_id = $plan['cp_merchant'];
    $cp_ipn_secret = $plan['cp_secret'];
    $cp_debug_email = $settings['supportemail'];

    //These would normally be loaded from your database, the most common way is to pass the Order ID through the 'custom' POST field.
    $order_currency = $de['currency']);
    $order_total = $de['amount'];

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
        errorAndDie('Original currency mismatch! DEPOSIT ID: '.$deposit_id);
    }    
  
    if ($status >= 100 || $status == 2) {
        // payment is complete or queued for nightly payout, success
        $update = $db->query("UPDATE ce_invest_deposits SET status='3',tx_id='$txn_id',updated='$time' WHERE id='$deposit_id'");
        $time = time();
        $GetUserBalance = $db->query("SELECT * FROM ce_invest_balances WHERE uid='$de[uid]' and currency='$de[currency]'");
        if($GetUserBalance->num_rows>0) {
            $ubalance = $GetUserBalance->fetch_assoc();
            $newubalance = $ubalance['amount'] + $amount1;
            $update = $db->query("UPDATE ce_invest_balances SET amount='$amount',updated='$time' WHERE uid='$de[uid]' and currency='$de[currency]'");
        } else {
            $insert = $db->query("INSERT ce_invest_balances (uid,amount,currency,created,updated,status) VALUES ('$de[uid]','$amount1','$de[currency]','$time','0','1')");
        }
    } else if ($status < 0) {
        //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
        $update = $db->query("UPDATE ce_invest_deposits SET status='4',tx_id='$txn_id' WHERE id='$deposit_id'");
    } else {
        //payment is pending, you can optionally add a note to the order page
        $update = $db->query("UPDATE ce_invest_deposits SET status='2',tx_id='$txn_id',confirmations=confirmations+1 WHERE id='$deposit_id'");
    }
    die('IPN OK'); 
    }
}
?>