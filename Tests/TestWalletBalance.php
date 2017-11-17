<?php
require_once "Utils.php";

#TestCase 1: Max Deposit per Transaction 
echo "\n\n";

$payload = array(
	"account"=>"",
	"pin"=>"",
	"request"=>"balance",
);
$data_string = json_encode($payload);
$url = 'http://localhost/ewallet/api/v1/index.php';
$resp = send_post_request($url, $data_string,'POST');
print_r($resp);

exit();
$resp = json_encode($resp,JSON_PRETTY_PRINT);
print_r($resp);
$arr = json_decode($resp,TRUE);
$balance = $arr['more']['699699']['accountBalance'];
print_r($balance);
echo "\n\n\n";
