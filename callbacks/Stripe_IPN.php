<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");

define('STRIPE_API_KEY', gatewayinfo(orderinfo($_SESSION['ce_stripe_productNumber'],"gateway_send"),"g_field_2")); 
define('STRIPE_PUBLISHABLE_KEY', gatewayinfo(orderinfo($_SESSION['ce_stripe_productNumber'],"gateway_send"),"g_field_1")); 
  
$response = array();

// Check whether stripe token is not empty
if(!empty($_POST['stripeToken'])){
    
    $productName = $_SESSION['ce_stripe_productName']; 
    $productNumber = $_SESSION['ce_stripe_productNumber']; 
    $productPrice = $_SESSION['ce_stripe_productPrice']; 
    $currency = $_SESSION['ce_stripe_currency']; 

    // Convert product price to cent
    $stripeAmount = round($productPrice*100, 2);
    // Get token and buyer info
    $token  = $_POST['stripeToken'];
    $email  = $_POST['stripeEmail'];
    
    // Include Stripe PHP library 
    require_once '../app/includes/payment_src/stripe-php/init.php'; 
    
    // Set API key
    \Stripe\Stripe::setApiKey(STRIPE_API_KEY);
	
	// Add customer to stripe 
    $customer = \Stripe\Customer::create(array( 
        'email' => $email, 
        'source'  => $token 
    )); 
    
    // Charge a credit or a debit card
    $charge = \Stripe\Charge::create(array(
        'customer' => $customer->id,
        'amount'   => $stripeAmount,
        'currency' => $currency,
        'description' => $productName,
    ));
    
    // Retrieve charge details
    $chargeJson = $charge->jsonSerialize();

    // Check whether the charge is successful
    if($chargeJson['amount_refunded'] == 0 && empty($chargeJson['failure_code']) && $chargeJson['paid'] == 1 && $chargeJson['captured'] == 1){
        
        // Order details
		$txnID = $chargeJson['balance_transaction']; 
        $paidAmount = ($chargeJson['amount']/100);
        $paidCurrency = $chargeJson['currency']; 
        $status = $chargeJson['status'];
        $orderID = $chargeJson['id'];
        $payerName = $chargeJson['source']['name'];
		
		// Include database connection file  
        $time = time();
        $update = $db->query("UPDATE ce_orders SET status='3',transaction_send='$txnID',updated='$time' WHERE id='$productNumber'");
        $last_insert_id = $productNumber;
        // If order inserted successfully
		if($last_insert_id && $status == 'succeeded'){
            $response = array(
                'status' => 1,
                'msg' => 'Your Payment has been Successful!',
                'txnData' => $chargeJson
            );
        }else{
            $response = array(
                'status' => 0,
                'msg' => 'Transaction has been failed.'
            );
        }
    }else{
        $response = array(
            'status' => 0,
            'msg' => 'Transaction has been failed.'
        );
    }
}else{
    $response = array(
        'status' => 0,
        'msg' => 'Form submission error...'
    );
}

// Return response
echo json_encode($response);