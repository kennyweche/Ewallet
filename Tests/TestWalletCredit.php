<?php
require_once "Utils.php";

#TestCase 1: Max Deposit per Transaction 
echo "\n\n";

$payload = array(
	"pin"=>"",
	"uniqueID"=>"",
	"amount"=>"",	
	"source"=>"",
	"destination"=>"",
	"request"=>"CREDIT",
);
$data_string = json_encode($payload);
$url = 'http://localhost/ewallet/api/v1/index.php';
$resp = send_post_request($url, $data_string,'POST');
$resp = json_encode($resp,JSON_PRETTY_PRINT);
print_r($resp);

echo "\n\n\n";
