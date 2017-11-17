<?php

class Configs {

    const db_host = 'localhost';
    const db_user = 'root';
    const db_pass = 'r00t';
    const db_name = 'eWallet';

    /**
     * RabbitMQ
     *
    const rabbit_mq_server = '172.19.1.3';
    const rabbit_mq_port = 5672;
    const rabbit_mq_user = 'bobby';
    const rabbit_mq_pass = 'toor123!';*/
    
    const rabbit_mq_server = 'localhost';
    const rabbit_mq_port = 5672;
    const rabbit_mq_user = 'guest';
    const rabbit_mq_pass = 'guest';

    /*
     * Setup Redis for Cache
     */
    const redis_server = 'web2';
    const redis_port = 6379;
    
    /**
     * Wallet
     */
    const ACCOUNT_ID="0101";
    const WALLET_ID="EWALLET_";
    var $INTERNAL_FUNDS_TRANSFER = array("INTERNAL_FT");
    var $EXTERNAL_FUNDS_TRANSFER = array("MPESA","AIRTELMONEY");

    const DEPOSIT_KEY = 'DEPOSIT';
    const CREDIT_KEY = 'CREDIT';
    const DEBIT_KEY = 'DEBIT';
    const WITHDRAW_KEY = 'WITHDRAWAL';
    const CR_KEY = 'CR';
    const DR_KEY = 'DR';
    const DEFAULT_WALLET_ALPHANUMERIC = "ROAMTECH";
    
    #DLR
    const WALLET_MSGS_DLR = "http://localhost/ewallet/api/v1/index.php";
    const PROFILES_URL="http://127.0.0.1/eprofiles/api/v1/index.php";
    
    /**
     *Microlending services 
     */
    const LOAN_TYPE_INTERNAL = "INTERNAL";
    const LOAN_TYPE_EXTERNAL = "EXTERNAL";
    const GLOBAL_MAX_LOAN_AMOUNT = 100;
    const LOAN_KEY = 'LOAN_';
    const LOAN_KEY_EX = 'LOAN';


    /**
     * Payment Gateway
     */
    
    const GATEWAY_USER_ID = "ewallet_user";
    const GATEWAY_SECRET_ID = "ewallet_user";
    
    var $GATEWAY_CHANNEL_IDS=array(
        1,//MPESA
        3,//AIRTELMONEY
        9,//EWALLET
    );
    
    var $GATEWAY_CHANNELS = array(
        "EWALLET_SAF_WITHDRAWAL",
        "EWALLET_AIRTEL_WITHDRAWAL",
        "EWALLET_LOAN_SAF_WITHDRAWAL",
        "WALLET_SIDIAN_LOANS");
    
    var $SAFARICOM_MATCHES = array(
        '(^25471)[0-9]{7}','(^25470)[0-9]{7}','(^25472)[0-9]{7}'
    );
    
    var $AIRTEL_MATCHES = array(
        '(^25473)[0-9]{7}',
    );
    
    var $WALLET_QUEUES = array(
        "EWALLET_GATEWAY_QUEUE",
        "EWALLET_PUSH_NOTIFY_QUEUE",
        "EWALLET_FAILED_QUERIES_QUEUE",
    );
    
    /**
     * Get channels.
     * @return type
     */
    public function get_gateway_channels(){
        return $this->GATEWAY_CHANNELS;
    }
    
    public function get_safaricom_regex(){
        return $this->SAFARICOM_MATCHES;
    }
    
    public function get_gateway_channel_ids(){
        return $this->GATEWAY_CHANNEL_IDS;
    }
    
    public function get_wallet_queues(){
        return $this->WALLET_QUEUES;
    }
}
