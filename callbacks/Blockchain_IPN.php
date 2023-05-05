<?php
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("../app/configs/bootstrap.php");
include("../app/includes/bootstrap.php");
//include(getLanguage($settings['url'],null,2));
$order_id = protect($_GET['order_id']);
$secret = protect($_GET['secret']);
if(!empty($order_id)) {   
    if(orderinfo($order_id,"id")) {
        $blockchain_root = "https://blockchain.info/";
        $blockchain_receive_root = "https://api.blockchain.info/";
        $mysite_root = $settings['url'];
        $xsecret = gatewayinfo(orderinfo($order_id,"gateway_send"),"g_field_3");
        $my_xpub = gatewayinfo(orderinfo($order_id,"gateway_send"),"g_field_1");
        $my_api_key = gatewayinfo(orderinfo($order_id,"gateway_send"),"g_field_2");
        $time = time();
        $invoice_id = protect($_GET['invoice_id']);
        $transaction_hash = protect($_GET['transaction_hash']);
        $value_in_btc = protect($_GET['value']) / 100000000;

        if ($_GET['address'] != orderinfo($order_id,"u_field_10")) {
            echo 'Incorrect Receiving Address';
          return;
        }
        if ($_GET['secret'] != $xsecret) {
          echo 'Invalid Secret';
          return;
        }
        if ($_GET['confirmations'] >= 3) {
          $update = $db->query("UPDATE ce_orders SET status='4',updated='$time' WHERE id='$order_id'");
          echo 'OK';
        } else {
          $update = $db->query("UPDATE ce_orders SET status='3',updated='$time' WHERE id='$order_id'");
           echo "Waiting for confirmations";
        }
    }
}
?>