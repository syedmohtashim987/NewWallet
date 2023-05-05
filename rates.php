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

$s = protect($_GET['s']);
if($s == "xml") {
    header('Content-Type: text/xml');
    echo '<rates>';
    $query = $db->query("SELECT * FROM ce_xmlrates ORDER BY id");
    if($query->num_rows>0) {
        while($row = $query->fetch_assoc()) {
            $GetRates = $db->query("SELECT * FROM ce_rates_live WHERE gateway_from='$fid' and gateway_to='$sid'");
            $rate = $GetRates->fetch_assoc();
            $rate_from = $rate['rate_from'];
            $rate_to = $rate['rate_to'];
            echo '<item>
            <from>'.$row[gateway_from_prefix].'</from>
            <to>'.$row[gateway_to_prefix].'</to>
            <in>'.$rate_from.'</in>
            <out>'.$rate_to.'</out>
            <amount>'.gatewayinfo($row[gateway_to],"reserve").'</amount>
            <minamount>'.gatewayinfo($row[gateway_from],"min_amount").'</minamount>
            </item>';
        }
    }
    echo '</rates>';
} else {
    echo 'Unknown export type.';
}
?>