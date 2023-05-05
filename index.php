<?php
if(file_exists("./install.php")) {
	header("Location: ./install.php");
} 
define('CryptExchanger_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
include("app/configs/bootstrap.php");
include("app/includes/bootstrap.php");
include(getLanguage($settings['url'],null,null));
$a = protect($_GET['a']);
if(isset($_GET['refid'])) {
	$refid = protect($_GET['refid']);
	if(idinfo($refid,"email")) {
		$_SESSION['ce_refid'] = $refid;
		header("Location: $settings[url]");
	} else {
		header("Location: $settings[url]");
	}
}
CE_CheckExpiredOrders();
$cur_from = protect($_GET['cur_from']);
$cur_to = protect($_GET['cur_to']);
if(isset($cur_from) && isset($cur_to)) {
	$CheckDirection = $db->query("SELECT * FROM ce_xmlrates WHERE gateway_from_prefix='$cur_from' and gateway_to_prefix='$cur_to' ORDER BY id LIMIT 1");
	if($CheckDirection->num_rows>0) {
		$cdd = $CheckDirection->fetch_assoc();
		$gtname_send = str_ireplace(" ","-",gatewayinfo($cdd['gateway_send'],"name"));
        $gtname_send = $gtname_send.'_'.gatewayinfo($cdd['gateway_send'],"currency");
        $gtname_receive = str_ireplace(" ","-",gatewayinfo($cdd['gateway_receive'],"name"));
        $gtname_receive = $gtname_receive.'_'.gatewayinfo($cdd['gateway_receive'],"currency");
		$redirect = $settings['url']."exchange/".$cdd['gateway_send']."_".$cdd['gateway_receive']."/".$gtname_send."-to-".$gtname_receive;
		header("Location: $redirect");
	}
}
if($a == "account") {
	include("app/sources/account.php");
}  else {
	include("app/sources/header.php");
	switch($a) {
		case "login": include("app/sources/login.php"); break;
		case "password": include("app/sources/password.php"); break;
		case "register": include("app/sources/register.php"); break;
		case "email": include("app/sources/email.php"); break;
		case "reserve_request": include("app/sources/reserve_request.php"); break;
		case "order": include("app/sources/order.php"); break;
		case "pay": include("app/sources/pay.php"); break;
		case "rates": include("app/sources/rates.php"); break;
		case "contacts": include("app/sources/contacts.php"); break;
		case "exchange": include("app/sources/exchange.php"); break;
		case "reviews": include("app/sources/reviews.php"); break;
		case "page": include("app/sources/page.php"); break;
		case "news": include("app/sources/news.php"); break;
		case "sitemap": include("app/sources/sitemap.php"); break;
		case "payment": include("app/sources/payment.php"); break;
		default: include("app/sources/homepage.php");
	}
	include("app/sources/footer.php");
}
mysqli_close($db);
?> 