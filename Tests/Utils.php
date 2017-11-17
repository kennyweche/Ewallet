<?php

/**
 Send a GET request
*/
function send_get_request($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
    ));
    $result = json_decode(curl_exec($ch),TRUE);
    return $result;
}

/**
 Send a POST/PUT request
*/
function send_post_request($url, $data_string, $type='POST'){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $result =json_decode(curl_exec($ch),TRUE);
    return $result;
}

