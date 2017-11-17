<?php

require_once 'libs/rabbitmq/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Utils {
    /**
     * Format response in the correct manner.
     * 
     * @param type $status
     * @param type $message
     * @param type $reason
     */
    public static function format_response($status=200, $message=NULL,
            $reason=NULL, $other=NULL) {
        $response = array();        
        $response['status']=$status;
        $response['message']=$message;            
        if($reason != NULL){
            $response['reason'] = $reason;
        }
        if($other != NULL){
            $response['more'] = $other;
        }
        return $response;
    }
    
    /**
     * Generate a PIN
     * @param type $src
     * @return type
     */
    public static function generatePIN($src){
        $item = NULL;
        for($i=0;$i<4;$i++) {
            $n = rand(0,strlen($src));
            $item .= substr($src,$n,1);
        }
        return $item;
    }
    
    /**
     * Generate message for one time pin.
     * @param type $pin
     * @param type $firstname
     * @return string
     */
    public static function generateOneTimePinMessage($pin, $firstname=NULL){
        $message = "Dear customer, ";
        if($firstname != NULL){
            $message = "Dear $firstname, ";
        }        
        $message .= "your ewallet pin is $pin. Dial *699*12# to access "
                . "your account";
        return $message;
    }
    
    /**
     * Withdrawal message.
     * 
     * @param type $account
     * @param type $amount
     * @param type $firstname
     * @return string
     */
    public static function generateWithdrawalMessage($account, $amount, 
            $firstname=NULL){
        $message = "Dear customer, ";
        if($firstname != NULL){
            $message = "Dear $firstname, ";
        }    
        $message .= "Acc.ending ".substr($account,strlen($account), -5)
                ." has been DEBITED with KES $amount on ".date("Y/m/d")
                ." at ".date("H:i:s a");
        return $message;
    }
    
    /**
     * Send payload to RabbitMQ queue.
     * @param type $payload
     */
    public static function queue_request($payload, $queue_name,
            $mq_server = 'localhost', $mq_port = 5672, $mq_user = 'guest', 
            $mq_password = 'guest') {
        try{
            $connection = new AMQPStreamConnection($mq_server, $mq_port, 
                    $mq_user, $mq_password);
            $channel = $connection->channel();
            $channel->queue_declare($queue_name, false, true, false, false);
            $msg = new AMQPMessage($payload,
                        array('delivery_mode' => 2) # make message persistent
                      );
            $channel->basic_publish($msg, '', $queue_name);
            $channel->close();
            $connection->close();
            return TRUE;
        } catch (Exception $ex) {
            return FALSE;
        }
    }
    
    /**
     * Calculate TAT.
     * @return type
     */
    public static function getMTime(){
        $mtime = microtime(); 
        $ftime = explode(' ', $mtime); 
        $stime = $ftime[1] + $ftime[0]; 
        return $stime; 
    }
    
    /**
     * 
     * @param type $payload
     * @param type $uri
     * @return type
     */
    public static function post_data($payload, $uri){
        $data_string = json_encode($payload,JSON_PRETTY_PRINT);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                                'Content-Length: ' . strlen($data_string))
                                    );
        $result = curl_exec($ch);
        return $result;
    }
}
