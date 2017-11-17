<?php

require_once 'RequestHandler.php';
require_once 'Logger.php';
require_once 'Utils.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

Logger::log("WalletEntry::()~>Received a request processing - ".print_r($input,TRUE)." ######");

switch ($method) {
    case 'GET':
        $response = array(
            "status" => "200",
            "message" => "NOT ALLOWED"
        );
        break;
    case 'PUT':
        $response = array(
            "status" => "200",
            "message" => "NOT ALLOWED"
        );
        break;
    case 'POST':
        $start_time = Utils::getMTime();
        Logger::log("WalletEntry::()~>Push request to the RequestHandler for "
                . "processing");
        $handler = new RequestHandler();
        $response = $handler->process($input);
        $end_time = Utils::getMTime();
        $tat = sprintf("%0.2f",($end_time - $start_time));
        $request_was = $handler->last_request;
        Logger::log("WalletEntry::()~>RequestHandler finished processing ["
                . "$request_was-request] in "
                . "$tat seconds. Send back response "
                .print_r($response,TRUE));
        break;
    case 'DELETE':
        $response = array(
            "status" => "200",
            "message" => "NOT ALLOWED"
        );
        break;
    default :
        $response = array(
            "status" => "200",
            "message" => "NOT ALLOWED"
        );
        break;
}
$final_response = json_encode($response);
Logger::log("WalletEntry::()~>After switch --responding with ->"
        . " $final_response to caller");
echo $final_response;
