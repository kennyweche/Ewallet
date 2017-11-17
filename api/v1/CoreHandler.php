<?php

require_once 'Logger.php';
require_once 'DatabaseHandler.php';
require_once 'Utils.php';
require_once 'libs/rabbitmq/vendor/autoload.php';
require_once 'AuthHandler.php';

class Core {

    var $_coredb = NULL;
    var $_auth = NULL;
    var $configs = NULL;

    /**
     * Constructor.
     */
    function __construct() {
        $this->_coredb = new DB();
        $this->_auth = new Auth();
        $this->configs = new Configs();  
    }
    
    /**
     * Ensure pin is secure alowed access.
     * @param type $pin
     * @return type
     */
    private function ensure_safety($pin){
        $auth_query = "SELECT user_name,api_key from users where uid="
                .$this->_coredb->_clean_input($pin);
        $auth_query_details = $this->_coredb->get_record($auth_query);
        if(count($auth_query_details) < 1){
            return FALSE;
        }
        $user_name = $auth_query_details[0]['user_name'];
        $super_pin = $user_name.$pin;
        $secure_pin = $auth_query_details[0]['api_key'];
        #confirm pin matches  
        #Logger::log("Core::ensure_safety()->match $super_pin to $secure_pin");
        $auth_status = 
                $this->_auth->isAuthenticated($super_pin, $secure_pin);        
        Logger::log("Core::ensure_safety()->Authentication XXXXX");
        if( !$auth_status) {
            Logger::log("Core::ensure_safety()->Authentication"
                    . "Authentication Failed");
            return FALSE;
        }           
        Logger::log("Core::ensure_safety()->AuthStatus -> Approved");
        return TRUE;
    }

    /**
     * Retransform the account number back to a mobile number.
     * @param type $clean_dest_acc
     * @return type
     */
    function restore_mobile_no_from_acc($clean_dest_acc){
        //Clean destination as this is a B2C
        $prob_wallet_id = substr($clean_dest_acc, 0,4);
        if($prob_wallet_id == Configs::ACCOUNT_ID){
            $clean_dest_acc = substr($clean_dest_acc,
                    strlen(Configs::ACCOUNT_ID), strlen($clean_dest_acc));  
        }
        return $clean_dest_acc;       
    }
    
    /**
     * Retransform the account number back to a mobile number.
     * @param type $ref_no
     * @return type
     */
    function restore_trxid_from_refno($ref_no){
        //Clean destination as this is a B2C
        $prob_wallet_id = substr($ref_no, 0,8);
        if($prob_wallet_id == Configs::WALLET_ID){
            $ref_no = 
                    str_replace($prob_wallet_id,"",$ref_no);
        }
        return $ref_no;       
    }
    
    /**
     * Check if account exists.
     * @param type $account
     * @return boolean
     */
    function account_exists($account){
        $get_account_detail_query = "select a.accountsID, a.accountBalance"
                . ", a.status from accounts a inner "
                . "join customers c on a.customerID=c.customerid inner join "
                . "customerDetails cd on c.customerid = cd.customerid where "
                . "cd.account_number='$account'";
        Logger::log("Core::account_exists()->"
                . "Check account -> $account , "
                . "query -> $get_account_detail_query");
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);
        if (count($account_details) > 0) {
            Logger::log("Core::account_exists()~TRUE");
            return $account_details;
        }
        Logger::log("Core::account_exists()~FALSE");
        return FALSE;
    }
    
    /**
     * Extended version of account_exists ?
     * @param type $account
     * @param type $org_code
     * @return boolean
     */
    function account_exists_ex($account, $org_code){
        $get_account_detail_query = "select a.accountsID, a.accountBalance"
                . ", a.loanAccountBalance, a.status from accounts a inner "
                . "join customers c on a.customerID=c.customerid inner join "
                . "customerDetails cd on c.customerid = cd.customerid inner "
                . "join organizations o on cd.org_id=o.org_id where "
                . "cd.account_number='$account' and o.org_code='$org_code'";
        Logger::log("Core::account_exists()->"
                . "Check account -> $account , "
                . "query -> $get_account_detail_query");
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);
        if (count($account_details) > 0) {
            Logger::log("Core::account_exists()~TRUE");
            return $account_details;
        }
        Logger::log("Core::account_exists()~FALSE");
        return FALSE;
    }
    
    /**
     * Get organizations details.
     * @param type $org_code
     * @return boolean
     */
    function getOrgAccount($org_code){
        $clean_org_code = $this->_coredb->_clean_input($org_code);
        $org_acc_exists_query = "SELECT o.org_id,o.customerID,a.accountsID,"
                . "(oef.current_float_balance)accountBalance FROM "
                . "organizations o INNER JOIN accounts a on o.customerID = "
                . "a.customerID inner join organizations_external_float_accounts"
                . " oef on oef.org_id = o.org_id  WHERE org_code='$clean_org_code'";
        Logger::log("Core::getOrgAccount()->Check with query "
                . "$org_acc_exists_query #####");
        $org_acc_details = $this->_coredb->get_record($org_acc_exists_query);
        if(count($org_acc_details)> 0) {
            Logger::log("Core::getOrgAccount()->Found Parent eWallet Account "
                    . "for -> $org_code");
            return $org_acc_details;
        }
        Logger::log("Core::getOrgAccount()->Unable to find Parent eWallet "
                . "Account for -> $org_code");
        return FALSE;
    }
    
    /**
     * Create an organization based on the organization code.
     * @param type $org_code
     */
    function createOrgAccount($org_code, $initial_bal=0){
        Logger::log("Core::createOrgAccount()-> $org_code working #####");
        $clean_org_code = $this->_coredb->_clean_input($org_code);        
        #create customer
        $add_customer_query = "INSERT INTO customers (status,date_created,"
                . "date_modified) VALUES ('active',now(),now())";            
        Logger::log("Core::createOrgAccount()->Add customer query-> "
                . "$add_customer_query running ###");            
        $add_cus_res = $this->_coredb->add_record($add_customer_query);
        if (!$add_cus_res) {                
            Logger::log("Core::createOrgAccount()->add org $org_code "
                    . "as customer failed. ~WOW~ PANIC~~~");
            return FALSE;
        }            
        $customer_id = $this->_coredb->get_last_insertid();
        Logger::log("Core::createOrgAccount()->Add Organization OK with id=> "
                . "$customer_id ..next >>>");        
        Logger::log("Core::createOrgAccount()->Create parent account->"
                . "$clean_org_code #####");        
        $org_acc_create_query = "INSERT INTO organizations (org_code,customerID,"
                . "date_created,date_modified) VALUES ('$clean_org_code',"
                . "$customer_id,now(),now())";
        Logger::log("Core::createOrgAccount()->Add organization query ->"
                . "$org_acc_create_query");
        $add_org_res = $this->_coredb->add_record($org_acc_create_query);
        if (!$add_org_res) {                
            Logger::log("Core::createOrgAccount()->add organization failed."
                    . " Reject request");
            return FALSE;
        }            
        Logger::log("Core::createOrgAccount()->Now add an account for the "
                . "parent organization >>>");
        $add_acc_query = "INSERT INTO accounts (customerID,accountBalance,"
                    . "status,date_created,date_modified) VALUES ($customer_id,"
                    . "$initial_bal,'active',now(),now())";
        Logger::log("Core::createOrgAccount()->Add actual account->"
                . "$add_acc_query");            
        $add_acc_res = $this->_coredb->add_record($add_acc_query);
        if( !$add_acc_res) {
            Logger::log("Core::createOrgAccount()->add account failed."
                    . " Reject request");
            return FALSE;
        }
        Logger::log("Core::createOrgAccount()-> $org_code Complete #####");
        return TRUE;
    }
    
    /**
     * Create a new virtual account.
     * @param type $mobile_number
     */
    function createCustAccount($mobile_number, $org_id, $unique_id, 
            $initial_bal=0, $source=NULL, $firstname=NULL, $lastname=NULL,
            $account_no=NULL){
        $response = array();
        Logger::log("Core::createAccount()-> working #####");
        $clean_msisdn = $this->_coredb->_clean_input($mobile_number);
        $orig_clean_msisdn = $clean_msisdn;        
        $walletid = substr($clean_msisdn, 0,4);
        if(!strcasecmp($walletid, Configs::ACCOUNT_ID)==0){
            $clean_msisdn = Configs::ACCOUNT_ID.$clean_msisdn; 
        }        
        $get_account_detail_query = "select a.accountsID, a.accountBalance"
                . ", a.status from accounts a inner "
                . "join customers c on a.customerID=c.customerid inner join "
                . "customerDetails cd on c.customerid = cd.customerid inner"
                . " join organizations o on o.org_id=cd.org_id where "
                . "cd.account_number='$clean_msisdn' "
                . "and cd.mobile_number='$orig_clean_msisdn'and"
                . " o.org_id=$org_id";        
        Logger::log("Core::createAccount()->validate there is no account "
                . "registered with account number $clean_msisdn ,query -> "
                . "$get_account_detail_query");        
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);        
        if(count($account_details) <= 0) {
            Logger::log("Core::createAccount()->Account does not exist. "
                    . "Proceed with request ###");            
            #create customer
            $add_customer_query = "INSERT INTO customers (status,date_created,"
                    . "date_modified) VALUES ('active',now(),now())";            
            Logger::log("Core::createAccount()->Add customer query-> "
                    . "$add_customer_query running ###");            
            $add_cus_res = $this->_coredb->add_record($add_customer_query);
            if (!$add_cus_res) {                
                Logger::log("Core::createAccount()->add customer failed."
                        . " Reject request");
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,"Add customer failed");
                return $response;
            }            
            $customer_id = $this->_coredb->get_last_insertid();
            Logger::log("Core::createAccount()->Add customer OK with id=> "
                    . "$customer_id ..next >>>");
            $plain_text_pin = Utils::generatePIN($clean_msisdn);            
            Logger::log("Core::createAccount()->$customer_id PIN is "
                    . "$plain_text_pin>>");            
            $account_pin = $this->_coredb->_clean_input(
                    $this->_auth->generate_secure_pin($plain_text_pin));
            $orig_mobile_number = substr(
                    $clean_msisdn, 4, strlen($clean_msisdn));
            $add_customer_detail_query = "INSERT INTO customerDetails ("
                    . "customerid,mobile_number,account_number,"
                    . "account_pin,org_id,first_name,last_name,date_created,"
                    . "date_modified) VALUES ($customer_id, "
                    . "'$orig_mobile_number','$clean_msisdn',"
                    . "'$account_pin',$org_id,'$firstname',"
                    . "'$lastname',NOW(),NOW())";            
            Logger::log("Core::createAccount()->Add customer query->"
                    . "$add_customer_detail_query");
            $add_cus_det_res = 
                    $this->_coredb->add_record($add_customer_detail_query);            
            if( !$add_cus_det_res) {
                Logger::log("Core::createAccount()->add customer details failed."
                        . " Reject request");
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Add customer details failed");
                return $response;
            }
            Logger::log("Core::createAccount()->Add customerDetail OK with id=> "
                    . "$customer_id ..next accounts >>>");

            Logger::log("Core::createAccount()->send to profiles ~>"
                    . "$orig_mobile_number");
            
            $payload = array(
                "names"=>$firstname." ".$lastname,
                "platform" =>"ewallet",
                "channel"=>"Mobile", //or MPESA
                "external_id"=>"$customer_id",
                "msisdn"=>"$orig_mobile_number",
                "request"=>"create"
            );            
            
            $add_acc_query = "INSERT INTO accounts (customerID,accountBalance,"
                    . "status,date_created,date_modified) VALUES ($customer_id,"
                    . "0,'active',now(),now())";
            Logger::log("Core::createAccount()->Add customer account->"
                    . "$add_acc_query");            
            $add_acc_res = $this->_coredb->add_record($add_acc_query);
            if( !$add_acc_res) {
                Logger::log("Core::createAccount()->add account failed."
                        . " Reject request");
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Add account failed");
                return $response;
            }            
            //Logger::log("Core::createAccount()->Profiles payload ~>"
            //        . print_r($payload,TRUE));            
            //$presp = Utils::post_data($payload, Configs::PROFILES_URL);            
            //Logger::log("Core::createAccount()->Profiles response ~>"
            //        .$presp .".Proceed to create accounts ...");
        } else {
             Logger::log("Core::createAccount()-> Fail account exists #####");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC, "Account Exists");
            return $response;
        }  
        Logger::log("Core::createAccount()-> finished #####");        
        return Utils::format_response(StatusCodes::STAT_CODE_OK, 
                        StatusCodes::STAT_CODE_OK_DESC);
    }
    
        /**
     * Deposit virtual money into a virtual account.
     * 
     * @param type $source
     * @param type $destination
     * @param type $amount
     * @return string
     */
    function processCredit($pin, $source, $destination, $amount, $unique_id, 
            $firstname=NULL, $lastname=NULL) {
        $response = array();        
        if($this->ensure_safety($pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC);
            return $response;
        }        
        $wallet_queues = $this->configs->get_wallet_queues();
        $failed_queries_queue = $wallet_queues[3];
        Logger::log("Core::processCredit()-> working #####");
        $clean_source = $this->_coredb->_clean_input($source);
        $orig_clean_dest = $this->_coredb->_clean_input($destination);
        $clean_destination = 
                Configs::ACCOUNT_ID.$orig_clean_dest;
        $clean_trx_id = $this->_coredb->_clean_input($unique_id);
        if($firstname != NULL){
            $firstname = $this->_coredb->_clean_input($firstname);
        }        
        if($lastname != NULL){
            $lastname = $this->_coredb->_clean_input($lastname);
        }
        Logger::log("Core::processCredit()->Ensure Parent ~eWallet~ Account "
                . "$clean_source exists >>>");
        $org_acc_details = $this->getOrgAccount($clean_source);
        if($org_acc_details == FALSE){
            $this->createOrgAccount($clean_source);
            $org_acc_details = $this->getOrgAccount($clean_source);
        }
        $parent_customer_id = $org_acc_details[0]['customerID'];
        $parent_account_id = $org_acc_details[0]['accountsID'];
        $org_acc_balance = (float)$org_acc_details[0]['accountBalance'];  
        Logger::log("Core::processCredit()->Parent eWallet customerID is =>"
                . " $parent_customer_id >>>");        
        $get_account_detail_query = "select a.accountsID, a.accountBalance, "
                . "a.status from accounts a inner join customers c on "
                . "a.customerID=c.customerid inner join customerDetails cd"
                . " on c.customerid = cd.customerid inner join organizations o "
                . "on cd.org_id=o.org_id where "
                . "cd.account_number='$clean_destination' "
                . "and o.org_code='$clean_source';";
        Logger::log("Core::processCredit()->"
                . "Check destination -> $clean_destination , "
                . "query -> $get_account_detail_query");
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);
        if (count($account_details) > 0) {
            Logger::log("Core::processCredit()->Account "
                    . "$clean_destination found ####");            
            $account_id = $account_details[0]['accountsID'];
            $acc_status = $account_details[0]['status'];
            $previous_bal = (float)$account_details[0]['accountBalance'];            
            Logger::log("Core::processCredit()->account -> $account_id , "
                    . "status ->$acc_status");            
            #record in transactions
            $orig_acc_no = 
                    $this->restore_mobile_no_from_acc($clean_destination);
            $ins_transactions = "INSERT INTO transactions (accountid,"
                    . "uniqueTrxID,serviceid,destination,amount,"
                    . "transaction_type,status,"
                    . "date_created,date_modified) VALUES ($account_id,"
                    . "'$clean_trx_id',(SELECT serviceid FROM services WHERE "
                    . "servicename='".Configs::CREDIT_KEY
                    ."'),'$orig_acc_no',$amount,"
                    . "'".Configs::CREDIT_KEY."','"
                    .StatusCodes::STAT_CODE_COMPLETE."',now(),now())";
            Logger::log("Core::processCredit()->log transaction for revenue "
                    . "assesment ##### query -> $ins_transactions");            
            if( !$this->_coredb->add_record($ins_transactions)) {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Insert into transactions failed");
                return $response;
            }            
            $dp_trx_id = $this->_coredb->get_last_insertid();
            Logger::log("Core::processCredit()->log transaction OK ####");            
            $ins_acc_history = "INSERT INTO accounts_history (accountsID,amount,"
                    . "previous_balance,transaction_type,date_created,"
                    . "date_modified) VALUES ($account_id,$amount,$previous_bal,"
                    . "'".Configs::DR_KEY."',now(),now()),"
                    . "($parent_account_id,-($amount),$org_acc_balance,'".
                    Configs::CR_KEY."',now(),now())";            
            Logger::log("Core::processCredit()->query->"
                    . "$ins_acc_history, ####");            
            if( $this->_coredb->add_record($ins_acc_history)) {            
                Logger::log("Core::processCredit()->Success $dp_trx_id now "
                        . "updating account balance ####");
                $update_org_acc_query = "UPDATE accounts SET "
                        . "accountBalance=(accountBalance-$amount) WHERE"
                        . " accountsID=$parent_account_id";
                Logger::log("Core::processCredit()->DR process ~> "
                        . "$update_org_acc_query running >>>");
                if(!$this->_coredb->update_record($update_org_acc_query)){
                    Logger::log("Core::processCredit()->DR the $clean_source "
                            . "parent account..#~IGNORE~");
                    $payload = json_encode(array("query"=>$update_org_acc_query));
                    Utils::queue_request($payload, $failed_queries_queue,
                            Configs::rabbit_mq_server, Configs:: rabbit_mq_port,
                            Configs::rabbit_mq_user, Configs::rabbit_mq_pass);
                }
                Logger::log("Core::processCredit()->DR ~ok");
                $update_acc_query = "UPDATE accounts SET "
                        . "accountBalance=(accountBalance+$amount) WHERE"
                        . " accountsID=$account_id";                
                Logger::log("Core::processCredit()->CR process ~>"
                        . " ->$update_acc_query running >>>");
                if($this->_coredb->update_record($update_acc_query)) {                
                    Logger::log("Core::processCredit()->CR ~>OK "
                            . "Balance updated");     
                    
                    Logger::log("Core::processCredit()->"
                            . "Return final response");
                    #####################################
                    $extra = array("balance"=>(float)$previous_bal+$amount);
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK, 
                            StatusCodes::STAT_CODE_OK_DESC,NULL, $extra);
                } else {
                    Logger::log("Core::processCredit()->Balance not updated");                    
                    Logger::log("Core::processCredit()-> WOW serious +++ "
                            . "insert into accounts_history Success but "
                            . "update balance failed. Push to MQ for retry ###"
                            );                    
                    $payload = json_encode(array("query"=>$update_acc_query));
                    Utils::queue_request($payload, $failed_queries_queue,
                            Configs::rabbit_mq_server, Configs:: rabbit_mq_port,
                            Configs::rabbit_mq_user, Configs::rabbit_mq_pass);
                    $extra = array("balance"=>(float)$previous_bal+$amount);
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK, 
                            StatusCodes::STAT_CODE_OK_DESC,NULL, $extra);
                    return $response;
                }
            } else {
                Logger::log("Core::processCredit()-> WOW failed to insert into"
                        . " accounts_history");
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC);
                return $response;
            }
        } else {
            Logger::log("Core::processCredit()->Account $clean_destination "
                    . "not found, fail as account should exist in wallet "
                    . "account");
            $errors = array(
                "Invalid Account specified for credit"
            );
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC, $errors);
                return $response;            
        }
        Logger::log("Core::processCredit()-> finished. Respond back to "
                . "handler OK #####");
        return $response;
    }
    
    /**
     * Deposit virtual money into a virtual account.
     * 
     * @param type $source
     * @param type $destination
     * @param type $amount
     * @return string
     */
    function processDeposit($pin, $source, $destination, $amount, $unique_id, 
            $firstname=NULL, $lastname=NULL, $account_no=NULL) {
        $response = array();        
        if($this->ensure_safety($pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC);
            return $response;
        }        
        $wallet_queues = $this->configs->get_wallet_queues();
        $failed_queries_queue = $wallet_queues[3];
        Logger::log("Core::processDeposit()-> working #####");
        $clean_source = $this->_coredb->_clean_input($source);
        $orig_clean_dest = $this->_coredb->_clean_input($destination);
        $clean_destination = 
                Configs::ACCOUNT_ID.$orig_clean_dest;
        $clean_trx_id = $this->_coredb->_clean_input($unique_id);
        if($firstname != NULL){
            $firstname = $this->_coredb->_clean_input($firstname);
        }        
        if($lastname != NULL){
            $lastname = $this->_coredb->_clean_input($lastname);
        }
        Logger::log("Core::processDeposit()->Ensure Parent ~eWallet~ Account "
                . "$clean_source exists >>>");
        $org_acc_details = $this->getOrgAccount($clean_source);
        if($org_acc_details == FALSE){
            $this->createOrgAccount($clean_source);
            $org_acc_details = $this->getOrgAccount($clean_source);
        }
        $parent_customer_id = $org_acc_details[0]['customerID'];
        $parent_account_id = $org_acc_details[0]['accountsID'];
        $org_account_id = $org_acc_details[0]['org_id'];
        $org_acc_balance = (float)$org_acc_details[0]['accountBalance'];  
        Logger::log("Core::processDeposit()->Parent eWallet customerID is =>"
                . " $parent_customer_id >>>");        
        $get_account_detail_query = "select a.accountsID, a.accountBalance, "
                . "a.status from accounts a inner join customers c on "
                . "a.customerID=c.customerid inner join customerDetails cd"
                . " on c.customerid = cd.customerid inner join organizations o "
                . "on cd.org_id=o.org_id where "
                . "cd.account_number='$clean_destination' "
                . "and o.org_code='$clean_source';";
        Logger::log("Core::processDeposit()->"
                . "Check destination -> $clean_destination , "
                . "query -> $get_account_detail_query");
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);
        if (count($account_details) > 0) {
            Logger::log("Core::processDeposit()->Account "
                    . "$clean_destination found ####");            
            $account_id = $account_details[0]['accountsID'];
            $acc_status = $account_details[0]['status'];
            $previous_bal = (float)$account_details[0]['accountBalance'];            
            Logger::log("Core::processDeposit()->account -> $account_id , "
                    . "status ->$acc_status");            
            #record in transactions
            $orig_acc_no = 
                    $this->restore_mobile_no_from_acc($clean_destination);
            $ins_transactions = "INSERT INTO transactions (accountid,"
                    . "uniqueTrxID,serviceid,destination,amount,"
                    . "transaction_type,status,"
                    . "date_created,date_modified) VALUES ($account_id,"
                    . "'$clean_trx_id',(SELECT serviceid FROM services WHERE "
                    . "servicename='".Configs::DEPOSIT_KEY
                    ."'),'$orig_acc_no',$amount,"
                    . "'".Configs::DEPOSIT_KEY."','"
                    .StatusCodes::STAT_CODE_COMPLETE."',now(),now())";
            Logger::log("Core::processDeposit()->log transaction for revenue "
                    . "assesment ##### query -> $ins_transactions");            
            if( !$this->_coredb->add_record($ins_transactions)) {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Insert into transactions failed");
                return $response;
            }            
            $dp_trx_id = $this->_coredb->get_last_insertid();
            Logger::log("Core::processDeposit()->log transaction OK ####");            
            $ins_acc_history = "INSERT INTO accounts_history (accountsID,amount,"
                    . "previous_balance,transaction_type,date_created,"
                    . "date_modified) VALUES ($account_id,$amount,$previous_bal,"
                    . "'".Configs::CR_KEY."',now(),now())";            
            Logger::log("Core::processDeposit()->query->"
                    . "$ins_acc_history, ####");            
            if( $this->_coredb->add_record($ins_acc_history)) {            
                Logger::log("Core::processDeposit()->updating account balance"
                        . " ####");
                $update_acc_query = "UPDATE accounts SET "
                        . "accountBalance=(accountBalance+$amount) WHERE"
                        . " accountsID=$account_id";                
                Logger::log("Core::processDeposit()->CR process ~>"
                        . " ->$update_acc_query running >>>");
                if($this->_coredb->update_record($update_acc_query)) {                
                    Logger::log("Core::processDeposit()->CR ~>OK "
                            . "Balance updated");     
                    #Check if notification is required
                    Logger::log("Core::processDeposit()->Check if source "
                            . "$clean_source has notification enabled ...");
                    $notify_check_query = "SELECT  p.notify,n.notify_url from "
                            . "wallet_partners p inner join "
                            . "wallet_partners_notification_rules n on"
                            . " p.partner_id = n.partner_id WHERE"
                            . " p.partner_code='$clean_source' and "
                            . "n.notify_level='".Configs::DEPOSIT_KEY."'";
                    Logger::log("Core::processDeposit()->Query->"
                            . "$notify_check_query");
                    $res = $this->_coredb->get_record($notify_check_query);
                    if(count($res) > 0) {
                        $can_notify = $res[0]['notify'];     
                        $notify_url = $res[0]['notify_url'];
                        Logger::log("Core::processDeposit()->NOTIFY status "
                                . "$can_notify");
                        if(strcasecmp($can_notify, "yes") == 0 ){
                            Logger::log("Core::processDeposit()->"
                                    . "Send deposit notification ~> URL"
                                    . " #### ");     
                            $super_clean_dest = 
                                    $this->restore_mobile_no_from_acc(
                                            $clean_destination);
                            //queue this message
                            $payload = array(
                                "names"=>"$firstname $lastname",
                                "account_no"=> $this->_coredb->_clean_input(
                                        $account_no),
                                "balance"=>(float)($previous_bal+$amount),
                                "reference"=>$clean_trx_id,
                                "amount"=>"$amount",
                                "msisdn"=>  $super_clean_dest,
                                "date"=>date("Y-m-d H:i:s"),
                                "transaction_id"=>$dp_trx_id,
                                "url"=> $notify_url,
                                "business_number"=>"$clean_source"
                            );
                            $json_p = json_encode($payload);
                            $wallet_queues = 
                                    $this->configs->get_wallet_queues();
                            $actual_queue = $wallet_queues[1];
                            $queue_status = Utils::queue_request(
                                    $json_p, $actual_queue, 
                                    Configs::rabbit_mq_server, 
                                    Configs::rabbit_mq_port, 
                                    Configs::rabbit_mq_user,
                                    Configs::rabbit_mq_pass);
                            if($queue_status){
                                Logger::log("Core::processDeposit()->"
                                        . "queued message for "
                                        . "delivery { $json_p }");
                            } else {
                                Logger::log("Core::processDeposit()->"
                                        . "Unable to queue message for "
                                        . "delivery"
                                        . " { $json_p }");
                            }
                            
                        }
                    } else {
                        Logger::log("Core::processDeposit()->push "
                                . "notification is not enabled##proceed");
                    }
                    Logger::log("Core::processDeposit()->"
                            . "Return final response");
                    #####################################
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK, 
                            StatusCodes::STAT_CODE_OK_DESC);
                } else {
                    Logger::log("Core::processDeposit()->Balance not updated");                    
                    Logger::log("Core::processDeposit()-> WOW serious +++ "
                            . "insert into accounts_history Success but "
                            . "update balance failed. Push to MQ for retry ###"
                            );                    
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK, 
                            StatusCodes::STAT_CODE_OK_DESC);
                    return $response;
                }
            } else {
                Logger::log("Core::processDeposit()-> WOW failed to insert into"
                        . " accounts_history");
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC);
                return $response;
            }
        } else {
            Logger::log("Core::processDeposit()->Account $clean_destination "
                    . "not found, try registering the account as a new wallet "
                    . "account");
            ////////////////////////////////////////////////////////////////////
            //Try creating the account first and if we fail move the money to
            //an unallocated account
            $response = 
                    $this->createCustAccount($destination,$org_account_id, 
                            $unique_id, $amount, $clean_source, $firstname, 
                            $lastname,$account_no);
            Logger::log("Core::processDeposit()->createCustAccount()~>response is "
                    . json_encode($response));
            if ( $response['status'] !=  StatusCodes::STAT_CODE_OK) {
                Logger::log("Core::processDeposit()->Account $clean_destination "
                    . "not found , move to unallocated accounts for refund !");
                $duplicate_check = "SELECT alloc_id FROM "
                        . "unallocatedAccountsTransactions WHERE "
                        . "uniqueTransactionID='$clean_trx_id'";
                Logger::log("Core::processDeposit()->Check for duplicates in "
                        . "unallocated accounts, query->$duplicate_check");
                $dup_res = $this->_coredb->get_record($duplicate_check);
                if (count($dup_res) > 0) {
                    Logger::log("Core::processDeposit()->"
                            . "Duplicate record detected");
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC,
                            "Duplicate");
                    return $response;
                }
                Logger::log("Core::processDeposit()->No duplicates, insert into"
                        . "unallocated accounts ####");
                $unallocated_acc_ins_query = "INSERT INTO "
                        . "unallocatedAccountsTransactions (source, destination, "
                        . "amount,uniqueTransactionID, status, date_created,"
                        . " date_modified) VALUES ("
                        . "'$clean_source', '$clean_destination', $amount, "
                        . "'$clean_trx_id',1,now()"
                        . ",now())";
                Logger::log("Core::processDeposit()-> query -> "
                        . "$unallocated_acc_ins_query");
                if ($this->_coredb->add_record($unallocated_acc_ins_query)) {                   
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK, 
                            StatusCodes::STAT_CODE_OK_DESC);
                } else {
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC);
                }
            } else {
                return $this->processDeposit($pin, $source, $destination,
                        $amount, $unique_id, $firstname, $lastname, $account_no);
            }
        }
        Logger::log("Core::processDeposit()-> finished. Respond back to "
                . "handler OK #####");
        return $response;
    }
    
    /**
     * 
     * @param type $account
     */
    function getWithdrawalOperationCharge($account, $amount, 
            $charge_type= Configs::WITHDRAW_KEY){
        Logger::log("Core::getWithdrawalOperationCharges()~>Get withdrawal"
                . " charges ...");
        $operator_code = 'AIRTEL';
        $saf_regex = $this->configs->get_safaricom_regex(); 
        foreach($saf_regex as $regexp){
            if(preg_match("/$regexp/i", $account)){
                $operator_code = 'SAFARICOM';
                break;
            }                            
        }
        $charges_query = "select soc.charge_amount from "
                . "services_operator_charges soc inner join "
                . "service_operator_mapping som on soc.mapping_id=som.mapping_id"
                . " inner join service_operators so on "
                . "som.operator_id=so.operator_id inner join services s on "
                . "s.serviceid=som.service_id where "
                . "so.operator_code='$operator_code'and s.servicename='"
                . "$charge_type' and $amount between soc.min_range and"
                . " soc.max_range";
        Logger::log("Core::getWithdrawalOperationCharges()~>$charges_query");
        $charges_query_details = $this->_coredb->get_record($charges_query);
        if(count($charges_query_details)<1){
            return 0;
        }
        $charge = $charges_query_details[0]['charge_amount'];
        if($charge==NULL){$charge=0;}
        Logger::log("Core::getWithdrawalOperationCharges()~>Charge Amount -> "
                . "$charge");
        return $charge;
    }
    
    /**
     * Move money from one account to another account.
     * 
     * @param type $account
     * @param type $amount
     * @param type $channel
     */
    function processWithdrawal($source_account, $dest_account, $amount, $pin, 
            $withdraw_type='b2c') {
        $response = array();
        $clean_src_acc = $this->_coredb->_clean_input($source_account);        
        $clean_dest_acc = $this->_coredb->_clean_input($dest_account);
        $walletid2 = substr($clean_dest_acc, 0, 4);
        if(strcasecmp($walletid2, Configs::ACCOUNT_ID) != 0) {
            $clean_dest_acc = Configs::ACCOUNT_ID.$clean_dest_acc;
        } 
        if($this->ensure_safety($pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC);
            return $response;
        }
        Logger::log("Core::processWithdrawal()->Type of withdrawal is "
                . "$withdraw_type...#STARTING#####");
        Logger::log("Core::processWithdrawal()-> Confirm initiating account "
                . "$source_account exists #####");
        $get_account_detail_query = "select a.accountsID,(select accountsID".
                " from accounts where customerID=o.customerID)orgAccID,"
                ."(select accountBalance from accounts where"
                ." customerID=o.customerID)orgAccBalance,cd.mobile_number,"
                ."cd.account_pin,a.accountBalance,a.interest,o.customerID"
                ." from accounts a inner join customers c on "
                ."a.customerID=c.customerid inner join customerDetails cd on"
                ." c.customerid = cd.customerid inner join organizations o on c"
                ."d.org_id = o.org_id where cd.account_number='$clean_dest_acc' and"
                . " o.org_code='$clean_src_acc'";
        Logger::log("Core::processWithdrawal()->"
                . "Check destination -> $clean_dest_acc, "
                . "query -> $get_account_detail_query");
        $account_details = $this->_coredb->get_record(
                $get_account_detail_query);
        if (count($account_details) > 0) {
            Logger::log("Core::processWithdrawal()->Account "
                    . "$clean_dest_acc found, confirm charges ####");
            
            $crgtyp = 'NONE';
            if ($withdraw_type=='b2c'){
                $crgtyp = Configs::WITHDRAW_KEY;
            }
            
            $charge_amount = $this->getWithdrawalOperationCharge(
                    $this->restore_mobile_no_from_acc($clean_dest_acc), 
                    $amount, $crgtyp);
            
            Logger::log("Core::processWithdrawal()~Withdraw amount is $amount"
                    . " Charges for withdrawal is $charge_amount ####");
            
            $orig_amount = $amount;           
            $balance =(double)$account_details[0]['accountBalance'];
            $parent_org_acc_id = $account_details[0]['orgAccID'];
            $parent_org_previous_bal = 
                    (float)$account_details[0]['orgAccBalance'];  
            $amount += $charge_amount;
            
            Logger::log("Core::processWithdrawal()~>New amount after charges"
                    . " is $amount");
            
            if ($amount > $balance) {
                Logger::log("Core::processWithdrawal()->"
                        . "Cannot withdraw from account. "
                        . "Insufficient Balance");
                $extra = array("$clean_src_acc"=>
                        array(
                            "accountBalance"=>(float)$balance,
                            "accountCharge"=>(float)$charge_amount,
                            "referenceNumber"=>NULL,
                        )
                );
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, 
                        "Insufficient Funds",$extra);                
                return $response;
            } else {
                Logger::log("Core::processWithdrawal()-> Moving money from "
                        . "$clean_dest_acc TO $clean_src_acc via $withdraw_type >>>>");
                //Clean destination as this is a B2C
                $clean_dest_acc = 
                        $this->restore_mobile_no_from_acc($clean_dest_acc);
                Logger::log("Core::processWithdrawal()->Clean destination "
                        . "to be mobile number $clean_dest_acc , working "
                        . ">>>");                    
                $src_acc_id = $account_details[0]['accountsID'];               
                $ins_acc_history = "INSERT INTO accounts_history"
                         . " (accountsID,amount,previous_balance,"
                         . "transaction_type,date_created,"
                         . "date_modified) VALUES (";
                if(strcasecmp($withdraw_type ,'debit')==0){
                    $dr_parent_query = "UPDATE accounts SET "
                            . "accountBalance = (accountBalance+$amount) "
                            . "WHERE accountsID=$parent_org_acc_id";                        
                    Logger::log("Core::processWithdrawal()->"
                            . "DR parent eWallet account ->"
                            . "$dr_parent_query running");                        
                    if( !$this->_coredb->update_record($dr_parent_query)) {
                        Logger::log("Core::processWithdrawal()~> WOW DR "
                                . "parent account failed. IGNORE & "
                                . "reconcile later");
                        $wallet_queues = $this->configs->get_wallet_queues();
                        $failed_queries_queue = $wallet_queues[3];
                        $payload = json_encode(array("query"=>$dr_parent_query));
                        Utils::queue_request($payload, 
                                $failed_queries_queue,
                                Configs::rabbit_mq_server, 
                                Configs:: rabbit_mq_port,
                                Configs::rabbit_mq_user, 
                                Configs::rabbit_mq_pass);
                    }
                    $ins_acc_history.="$src_acc_id,$amount,$balance,'".
                            Configs::DR_KEY."',now(),now()),"
                            . "($parent_org_acc_id,$amount,"
                            . "$parent_org_previous_bal,'".
                            Configs::CR_KEY."',now(),now())";
                } else {
                    $ins_acc_history.="$src_acc_id,$amount,$balance,'".
                            Configs::DR_KEY."',now(),now())";
                }
                Logger::log("Core::processWithdrawal()-> "
                        . "Proceed to DR customer account ~>>>");
                //debit source
                $debit_src_query = "UPDATE accounts SET "
                        . "accountBalance = (accountBalance-$amount) "
                        . "WHERE accountsID=$src_acc_id";                        
                Logger::log("Core::processWithdrawal()->"
                        . "DR account $clean_src_acc ->"
                        . "$debit_src_query");                        
                if( !$this->_coredb->update_record($debit_src_query)) {
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC,
                            "DR $clean_src_acc FAILED !");
                    return $response;
                }                        
                Logger::log("Core::processWithdrawal()-> Make records"
                        . " of transactions ~>>>");
                //Accounts history
                Logger::log("Core::processWithdrawal()->Log "
                        . "accounts_history -query-> $ins_acc_history");                        
                if( !$this->_coredb->add_record($ins_acc_history)) {
                    //shouldnt be a show stopper
                    //re-schedule with MQ
                    Logger::log("Core::processWithdrawal()->insert into"
                            . " accounts_history failed, proceed anyway."
                            . " Push to MQ for reconcilliation");
                    $wallet_queues = $this->configs->get_wallet_queues();
                    $failed_queries_queue = $wallet_queues[3];
                    $payload = json_encode(array("query"=>$ins_acc_history));
                    Utils::queue_request($payload, 
                            $failed_queries_queue,
                            Configs::rabbit_mq_server, 
                            Configs:: rabbit_mq_port,
                            Configs::rabbit_mq_user, 
                            Configs::rabbit_mq_pass);
                }                        
                Logger::log("Core::processWithdrawal()->CR OK, logging "
                        . "tracker >>>");                        
                
                $keytype = Configs::WITHDRAW_KEY;
                if(strcasecmp($withdraw_type,'debit')==0){
                    $keytype = Configs::DEBIT_KEY;
                }
                $service_query = "SELECT serviceid FROM services where "
                        . "servicename='$keytype'";    
                Logger::log("Core::processWithdrawal()->Get service~>"
                        . "$service_query");
                $service_query_detail = 
                        $this->_coredb->get_record($service_query);
                if(count($service_query_detail) <= 0) {
                    $service_id = 3;                       
                }
                $service_id = $service_query_detail[0]['serviceid'];
                //log transaction
                $uniqid = uniqid(Configs::WALLET_ID);
                $ins_wallet_trx = "insert into transactions (accountid, "
                        . "uniqueTrxID,serviceid, destination, amount, "
                        . "charge_amount,transaction_type, date_created, "
                        . "date_modified) values ($src_acc_id,"
                        . "'$uniqid',$service_id,'$clean_dest_acc',$orig_amount,"
                        . "$charge_amount,'$keytype',now(),now())";
                //log transaction history
                Logger::log("Core::processWithdrawal()->CR Log ->"
                        . "$ins_wallet_trx working >>>");                        
                if($this->_coredb->add_record($ins_wallet_trx)) {
                    Logger::log("Core::processWithdrawal()->CR Log ->"
                        . "Success >>>");
                    $transaction_id = $this->_coredb->get_last_insertid();                    
                    Logger::log("Core::processWithdrawal()->CR Log transaction "
                            . "id is $transaction_id");
                    if(strcasecmp($withdraw_type,'b2c')==0){
                        Logger::log("Core::processWithdrawal()->CR ticket "
                                . "$uniqid . Push to gateway >>>");
                        $gateway_channels =
                                $this->configs->get_gateway_channels();
                        $gateway_channel_ids = 
                                $this->configs->get_gateway_channel_ids();
                        $wallet_queues = $this->configs->get_wallet_queues();
                        $saf_regex = $this->configs->get_safaricom_regex();                            
                        Logger::log("Core::processWithdrawal()->Loaded "
                                . "channels "
                                .  print_r($gateway_channels, TRUE) 
                                . " and regexp ". print_r($saf_regex,TRUE));
                        //default to airtel
                        $gateway_destination = $gateway_channels[1];
                        $gateway_channel_id = $gateway_channel_ids[1];
                        $queue = $wallet_queues[1];
                        foreach($saf_regex as $regexp){
                            if(preg_match("/$regexp/i", $clean_dest_acc)){
                                $gateway_destination = $gateway_channels[0];
                                $gateway_channel_id =
                                        $gateway_channel_ids[0];
                                $queue = $wallet_queues[0];
                                Logger::log("Core::processWithdrawal()->"
                                        . "$clean_dest_acc MATCHES "
                                        . "SAFARICOM $regexp");
                                break;
                            }                            
                        }
                        $super_clean_src = $this->restore_mobile_no_from_acc(
                                $source_account);
                        $credentials = array(
                            "username"=>  Configs::GATEWAY_USER_ID,
                            "password"=>  Configs::GATEWAY_SECRET_ID);
                        $packet = array(
                            "source_account"=>"$super_clean_src",
                            "destination"=>"$gateway_destination",
                            "destination_account"=>"$clean_dest_acc",
                            "payment_date"=>date("Y-m-d H:i:s"),
                            "amount"=>$orig_amount,
                            "channel_id"=>$gateway_channel_id,
                            "reference_number"=>"$uniqid",
                            "narration"=>"Withdrawal from wallet account "
                            . "$clean_dest_acc to mobile number $clean_dest_acc"
                            . " of amount $amount",
                            "extra"=>array("shortcode"=>$clean_src_acc),
                        );
                        $gateway_payload = array(
                            "credentials"=>$credentials, 
                            "packet"=>$packet);                            
                        $json = json_encode($gateway_payload);                            
                        Logger::log("Core::processWithdrawal()->"
                                . "Payload formulated -> $json SENT to "
                                . "Wallet queue => $queue xxxxxxxxxxxx");
                        $queue_status = Utils::queue_request($json,
                                $queue, Configs::rabbit_mq_server, 
                                Configs::rabbit_mq_port,
                                Configs::rabbit_mq_user, 
                                Configs::rabbit_mq_pass);
                        if($queue_status) {
                            Logger::log("Core::processWithdrawal()->"
                                    . "Transaction QUEUED updating status on"
                                    . "DB ###");
                            $upd_status_query = "UPDATE transactions SET "
                                    . "status='INPROGRESS' WHERE "
                                    . "transactionsid=$uniqid";
                            if($this->_coredb->update_record(
                                    $upd_status_query)){
                                Logger::log("Core::processWithdrawal()->"
                                        . " Status updated ###");
                            } else {
                                 Logger::log("Core::processWithdrawal()->"
                                        . " Ignore this DLR processor will "
                                         . "update FINAL status ###");
                            }
                        } else {
                            ################################################
                            #Failed to go to queue
                            Logger::log("Core::processWithdrawal()->"
                                    . " FAILED to push payout request to"
                                    . " queue # WOW !!!");
                            ##Write to file cron will pick it up
                        }
                    } else {
                        $update_trx_as_complete_query="UPDATE transactions SET"
                                . " status='".StatusCodes::STAT_CODE_COMPLETE
                                ."' WHERE transactionsid=$transaction_id";
                        Logger::log("Core::processWithdrawal()-> Withdrawal "
                                . "type was 'debit' no need to push to "
                                . "gateway#####");
                        Logger::log("Core::processWithdrawal()-> Close "
                                . "transaction with query ..."
                                . "$update_trx_as_complete_query");
                        if(!$this->_coredb->update_record(
                                $update_trx_as_complete_query)){
                            Logger::log("Core::processWithdrawal()-> Update "
                                    . "failed, push query to MQ for reprocessing"
                                    . "###");
                            $wallet_queues = $this->configs->get_wallet_queues();
                            $failed_queries_queue = $wallet_queues[3];
                            $payload = json_encode(
                                    array(
                                        "query"=>$update_trx_as_complete_query)
                                    );
                            Utils::queue_request($payload, 
                                    $failed_queries_queue,
                                    Configs::rabbit_mq_server, 
                                    Configs:: rabbit_mq_port,
                                    Configs::rabbit_mq_user, 
                                    Configs::rabbit_mq_pass);
                            
                        }
                        Logger::log("Core::processWithdrawal()-> Success !");
                    }
                    $extra = array("$clean_src_acc"=>
                        array(
                            "accountBalance"=>(float)$balance-$amount,
                            "accountCharge"=>(float)$charge_amount,
                            "referenceNumber"=>$uniqid)
                        );                    
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_OK,
                            StatusCodes::STAT_CODE_OK_DESC,NULL, $extra);                            
                    return $response;
                } else {
                    Logger::log("Core::processWithdrawal()->CR Log ->"
                        . "Failed. Push back to MQ first >>>");
                    Logger::log("Core::processWithdrawal()->CR Log ->"
                        . "Failed. Push back response >>>");
                    $response = Utils::format_response(
                            StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC);
                    return $response;
                }
            }
        } else {
            Logger::log("Core::processWithdrawal()->Account $clean_src_acc "
                    . "or $clean_dest_acc does not exist !");            
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Either account(s) $clean_dest_acc or "
                    . "$clean_src_acc does not exist");
            return $response;
        }        
        Logger::log("Core::processWithdrawal()-> Finished. Send response back");        
        return $response;
    }
    
    /**
     * Get customer balance.
     * @param type $account
     */
    function processBalance($account, $pin) {
        $response = array();        
        Logger::log("Core::processBalance()-> Get balance for $account #####");        
        $clean_account = $this->_coredb->_clean_input($account);        
        $walletid = substr($account, 0, 4);
        if(strcasecmp($walletid, Configs::ACCOUNT_ID) != 0) {
            $clean_account = Configs::ACCOUNT_ID.$clean_account;
        }
        if($this->ensure_safety($pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC);
            return $response;
        }
        
        $get_balance_query = "select o.org_code,a.accountsID,cd.mobile_number,"
                . "cd.account_pin,a.accountBalance,a.loanAccountBalance, "
                . "a.interest from accounts a"
                . " inner join customers c on a.customerID=c.customerid inner"
                . " join customerDetails cd on c.customerid = cd.customerid"
                . " inner join organizations o on o.org_id=cd.org_id where"
                . " cd.account_number='$clean_account'";        
        Logger::log("Core::processBalance()->QUERY->$get_balance_query");        
        $account_details = $this->_coredb->get_record($get_balance_query);        
        if (count($account_details) <= 0) {
            Logger::log("Core::processBalance()->Account does not exist !");
            $response = Utils::format_response(
                    StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC, 
                    "Account not found");
            return $response;
        }
        
        $other = array();
        foreach ($account_details as $caccount){
            $balance = (float)$caccount['accountBalance'];        
            $interest = (float)$caccount['loanAccountBalance'];
            $other[$caccount['org_code']]= array(            
                "accountBalance"=>$balance,
                "loanBalance"=>$interest,
            );        
            Logger::log("Core::processBalance()->Got account balance - $balance"); 
        }
        $response = Utils::format_response(StatusCodes::STAT_CODE_OK,
                StatusCodes::STAT_CODE_OK_DESC, NULL, $other);        
        Logger::log("Core::processBalance()-> finished. Respond back to "
                . "handler OK #####");        
        return $response;
    }
    
    /**
     * Get loan Balance.
     * @param type $pin
     * @param type $account
     * @return type
     */
    function processLoanBalance($pin, $account){
        return $this->processBalance($account, $pin);
    }
    
    /**
     * Update interest value/amount in virtual account.
     * 
     * @param type $pin
     * @param type $destination
     * @param type $amount
     */
    function processInterest($pin, $destination, $amount) {
        $clean_destination = 
                Configs::ACCOUNT_ID.$this->_coredb->_clean_input($destination);        
        $get_account_detail_query = "select a.accountsID, a.interest"
                . ",a.accountBalance, a.status from accounts a inner "
                . "join customers c on a.customerID=c.customerid inner join "
                . "customerDetails cd on c.customerid = cd.customerid where "
                . "cd.account_number='$clean_destination'";        
        Logger::log("Core::processInterest()->validate there is an account "
                . "registered with number $clean_destination ,query -> "
                . "$get_account_detail_query");        
        $account_details = 
                $this->_coredb->get_record($get_account_detail_query);        
        if(count($account_details) <= 0) {
            Logger::log("Core::processInterest()->Account does not exist ###");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Account does not exist");
            return $response;
        }
        Logger::log("Core::processInterest()->Account found, updating interest");
        $account_id = $account_details[0]['accountsID'];
        $acc_bal = $account_details[0]['accountBalance'];        
        $acc_history = "INSERT INTO accounts_history (accountsID,amount, "
                . "previous_balance,interest_amt,transaction_type,date_created,"
                . "date_modified) VALUES ($account_id,$amount,$acc_bal,$amount,"
                . "'".Configs::CR_KEY."',now(),now())";        
        Logger::log("Core::processInterest()->query->$acc_history");        
        if( !$this->_coredb->add_record($acc_history)) {
            Logger::log("Core::processInterest()->Fail to create history ###");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Fail to create history");
            return $response;
        }
        Logger::log("Core::processInterest()->Create history OK, update interest"
                . " balance #### ");        
        $upd_acc_interest_query = "UPDATE accounts SET "
                . "interest=(interest+$amount) WHERE accountsID=$account_id";        
        Logger::log("Core::processInterest()->query->$upd_acc_interest_query");        
        if(!$this->_coredb->update_record($upd_acc_interest_query)) {
            Logger::log("Core::processInterest()->Fail to update");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Fail to update interest value");
        }        
        Logger::log("Core::processInterest()->Success");        
        $response = Utils::format_response(StatusCodes::STAT_CODE_OK,
                    StatusCodes::STAT_CODE_OK_DESC);
        return $response;
    }
    
    /**
     * DLR processor.
     * @param type $transaction_id
     * @param type $final_status
     */
    function processDLR($transaction_id, $final_status,
            $external_receipt_no) {
        $needs_reversal = FALSE;
        if(strcasecmp($final_status, "success") == 0) {
            $final_status = 'COMPLETE';
        }        
        if(strcasecmp($final_status, "fail") == 0) {
            $final_status = 'REVERSED';
            $needs_reversal = TRUE;
        }        
        $clean_transaction_id = $this->_coredb->_clean_input($transaction_id);
        $clean_receipt_number = $this->_coredb->_clean_input(
                $external_receipt_no);
        $dlr_query = "UPDATE transactions SET "
                . "status='$final_status',external_receipt_number="
                . "'$clean_receipt_number' WHERE "
                . "uniqueTrxID='$clean_transaction_id'";        
        Logger::log("Core::processDLR()->query->$dlr_query");        
        if(!$this->_coredb->update_record($dlr_query)) {
            Logger::log("Core::processDLR()->Fail to update");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Fail to update db");
            return $response;
        }
        if($needs_reversal == TRUE) {
            Logger::log("Core::processDLR()->"
                    . "$clean_transaction_id requires REVERSAL"
                    . "...starting#");   
            $this->processReversal($clean_transaction_id);
            Logger::log("Core::processDLR()->Success");    
        } 
        $org_details_query = " select a.accountsID,(select partner_id from "
                . "wallet_partners where partner_code=(select org_code from "
                . "organizations where org_id=cd.org_id))partner_id,"
                . "a.accountBalance,t.destination,t.amount,cd.first_name,"
                . "cd.last_name,t.uniqueTrxID,t.transactionsid from "
                . "customerDetails cd inner join accounts a on "
                . "cd.customerid=a.customerID inner join transactions "
                . "t on a.accountsID=t.accountid where "
                . "t.uniqueTrxID='$clean_transaction_id';";
        Logger::log("Core::processDLR()->Check notification ,query~>"
                . "$org_details_query");
        $org_details = $this->_coredb->get_record($org_details_query);
        if(count($org_details)>0){
            $partner_id = $org_details[0]['partner_id'];
            $transactionsid = $org_details[0]['transactionsid'];
            $balance = $org_details[0]['accountBalance'];
            $amount = $org_details[0]['amount'];
            $account = $org_details[0]['destination'];
            $firstname = $org_details[0]['first_name'];
            $lastname = $org_details[0]['last_name'];
            $uniqueTrxID = $org_details[0]['uniqueTrxID'];
            $status = $org_details[0]['status'];
            $clean_account = $this->restore_mobile_no_from_acc(
                                        $account);               
            
            Logger::log("Core::processDLR()->working almost there ~~~>");
            Logger::log("Core::processDLR()->Check if notification is "
                    . "enabled ...");
            $notify_check_query = "SELECT  p.notify,n.notify_url from "
                    . "wallet_partners p inner join "
                    . "wallet_partners_notification_rules n on"
                    . " p.partner_id = n.partner_id WHERE"
                    . " p.partner_id=$partner_id AND "
                    . "n.notify_level='".Configs::WITHDRAW_KEY."'";
            Logger::log("Core::processDLR()->Query->"
                    . "$notify_check_query");
            $res = $this->_coredb->get_record($notify_check_query);
            if(count($res) > 0) {
                $can_notify = $res[0]['notify'];     
                $notify_url = $res[0]['notify_url'];
                Logger::log("Core::processDLR()->NOTIFY status "
                        . "$can_notify");
                if(strcasecmp($can_notify, "yes") == 0 ){
                    Logger::log("Core::processDLR()->"
                            . "Send deposit notification ~> URL"
                            . " #### ");                            
                    //queue this message
                    $payload = array(
                        "reference"=>$uniqueTrxID,
                        "names"=> "$firstname $lastname",
                        "balance"=>$balance,
                        "amount"=>"$amount",
                        "account"=> $clean_account,
                        "status"=>$status,
                        "transaction_id"=>$transactionsid,
                        "url"=> $notify_url,
                        "external_receipt_number"=>$clean_receipt_number
                    );
                    $json_p = json_encode($payload);
                    Logger::log("Core::processDLR()->Payload to send~>"
                            . "$json_p");
                    $wallet_queues = 
                            $this->configs->get_wallet_queues();
                    $actual_queue = $wallet_queues[2];
                    $queue_status = Utils::queue_request(
                            $json_p, $actual_queue, 
                            Configs::rabbit_mq_server, 
                            Configs::rabbit_mq_port, 
                            Configs::rabbit_mq_user,
                            Configs::rabbit_mq_pass);
                    if($queue_status){
                        Logger::log("Core::processDLR()->"
                                . "queued message for "
                                . "delivery { $json_p }");
                    } else {
                        Logger::log("Core::processDLR()->"
                                . "Unable to queue message for "
                                . "delivery"
                                . " { $json_p }");
                    }
                }
            } else {
                Logger::log("Core::processDLR() -> No notification setup, "
                        . "#Proceed as usual");
            }                
        }
        Logger::log("Core::processDLR()->Success");        
        $response = Utils::format_response(StatusCodes::STAT_CODE_OK,
                    StatusCodes::STAT_CODE_OK_DESC);
        return $response;
    }
    
    /**
     * Reverse funds in case a withdrawal failed.
     * @param type $transaction_id
     */
    function processReversal($transaction_id) {
        Logger::log("Core::processReversal()->Processing ...");  
        $clean_trx_id = $this->_coredb->_clean_input($transaction_id);
        $get_acc_details_query = "select a.accountsID,(select accountsID "
                . "from accounts where customerID=(select customerID from"
                . " organizations "
                . "where org_id=cd.org_id))orgAccID,a.accountBalance,"
                . "t.destination,(t.amount+t.charge_amount)tamount "
                . "from customerDetails cd inner"
                . " join accounts a on cd.customerid=a.customerID inner"
                . " join transactions t on a.accountsID=t.accountid"
                . " where t.uniqueTrxID='$clean_trx_id';";
        Logger::log("Core::processReversal()~>Get details ~> "
                . "$get_acc_details_query");
        $_acc_details = $this->_coredb->get_record($get_acc_details_query);
        if(count($_acc_details) < 0 ){
            Logger::log("Core::processReversal()~> $transaction_id does not "
                    . "have associated account probably **fake**");
            return TRUE;
        }
        $dest_account = $_acc_details[0]['accountsID'];
        $amt_moved = $_acc_details[0]['tamount'];
        Logger::log("Core::processReversal()~>Reverse funds of amount"
                . " $amt_moved moved from WALLET SRC -> $dest_account TO MOBILE "
                . "DST -> $dest_account , moving ****");
        $cr_dstacc_query = "UPDATE accounts SET accountBalance"
                . "=(accountBalance+$amt_moved) WHERE accountsID=$dest_account";
        if(!$this->_coredb->update_record($cr_dstacc_query)){
            Logger::log("Core::processReversal()->Update failed"
                            . " Push to MQ , query $cr_dstacc_query");
            $wallet_queues = $this->configs->get_wallet_queues();
            $failed_queries_queue = $wallet_queues[3];
            $payload = json_encode(array("query"=>$cr_dstacc_query));
            Utils::queue_request($payload, 
                    $failed_queries_queue,
                    Configs::rabbit_mq_server, 
                    Configs:: rabbit_mq_port,
                    Configs::rabbit_mq_user, 
                    Configs::rabbit_mq_pass);
        }
        return TRUE;
    }

    /**
     * Managing B2C float accounts.
     * @param type $code
     * @param type $balance
     * @return type
     */
    function processOrgBalance($code, $balance){
        Logger::log("Core::processOrgBalance()->Starting ...");  
        $clean_org_code = $this->_coredb->_clean_input($code);
        $check_org_query = "select o.org_id from organizations o inner"
                . " join customerDetails cd on cd.org_id = o.org_id inner "
                . "join accounts a on a.customerID=cd.customerID inner join "
                . "transactions t on a.accountsID=t.accountid where "
                . "t.uniqueTrxID='$clean_org_code'";
        Logger::log("Core::processOrgBalance()->Check query ~>$check_org_query"
                . " ...");  
        $_org_details = $this->_coredb->get_record($check_org_query);
        if(count($_org_details) < 0 ){
            Logger::log("Core::processOrgBalance()~> $code does not "
                    . "have associated account probably **fake**");
            return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC);
        }
        #Get /create the float account
        $org_id = $_org_details[0]['org_id'];
        $check_float_account_query = "select float_acc_id,current_float_balance"
                . " from organizations_external_float_accounts where "
                . "org_id=$org_id";
        Logger::log("Core::processOrgBalance()->Check query ~>"
                . "$check_float_account_query ...");  
        $_float_acc_detail = $this->_coredb->get_record(
                $check_float_account_query);
        $ins_hist_query = NULL;
        $float_acc_id = NULL;
        if(count($_float_acc_detail) <= 0 ){
            $_create_float_acc_query = "insert into "
                    . "organizations_external_float_accounts "
                    . "(current_float_balance,org_id,date_created,date_modified)"
                    . " values ($balance,$org_id,now(),now())";
            Logger::log("Core::processOrgBalance()->create float query "
                    . "$_create_float_acc_query~>");
            if(!$this->_coredb->add_record($_create_float_acc_query)){
                Logger::log("Core::processOrgBalance()->WOW PANIC");
            }
            $float_acc_id = $this->_coredb->get_last_insertid();
            $ins_hist_query = "insert into "
                    . "organizations_external_float_accounts_history ("
                    . "previous_balance,balance,float_acc_id,date_created,"
                    . "date_modified) values (0,$balance,$float_acc_id,now(),"
                    . "now());";
        } else {
            #update the balance
            $float_acc_id = $_float_acc_detail[0]['float_acc_id'];
            $previous_balance = $_float_acc_detail[0]['current_float_balance'];
            $_upd_float_query = "update organizations_external_float_accounts"
                    . " set current_float_balance=$balance where "
                    . "org_id=$org_id";
            Logger::log("Core::processOrgBalance()->Upd float ~>"
                    . "$_upd_float_query"); 
            if(!$this->_coredb->update_record($_upd_float_query)){
                Logger::log("Core::processOrgBalance()->WOW PANIC");
            }
            $ins_hist_query = "insert into "
                    . "organizations_external_float_accounts_history ("
                    . "previous_balance,balance,float_acc_id,date_created,"
                    . "date_modified) values ($previous_balance,$balance,"
                    . "$float_acc_id,now(),"
                    . "now());";
        }
        Logger::log("Core::processOrgBalance()->Updating history float acc"
                . "~$ins_hist_query");
        if(!$this->_coredb->add_record($ins_hist_query)){
            Logger::log("Core::processOrgBalance()->WOW PANIC");
        }
        Logger::log("Core::processOrgBalance()->Finished");  
        $response = Utils::format_response(StatusCodes::STAT_CODE_OK,
                    StatusCodes::STAT_CODE_OK_DESC);
        return $response;
    }
    
    /**
     * Added on 06/09/2017
     * Microlending functionality -> Process loans
     * @return type
     */
    public function processLoan($pin, $account_source,$initiator_account,
            $receiver_account,$loan_amount, $type,$external_id=NULL){
        $response = array();
        $has_errors = array();
        Logger::log("Core::".__FUNCTION__."->Started , check loan source ####"); 
        /** Clean inputs **/
        $clean_pin = $this->_coredb->_clean_input($pin);
        if($this->ensure_safety($clean_pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC,"unauthorized access");
            return $response;
        }        
        $clean_loan_acct = $this->_coredb->_clean_input($account_source);//this is the org_float_account
        $clean_init_acct = $this->_coredb->_clean_input($initiator_account);//the recv acct
        $clean_parent_acct = $this->_coredb->_clean_input($account_source);//where recv is registered
        $clean_recv_acc = $this->_coredb->_clean_input($receiver_account);
        $clean_amount = $this->_coredb->_clean_input($loan_amount);
        
        $loan_src = $this->getOrgAccount($clean_loan_acct);
        if(count($loan_src) > 0){
            $org_id = $loan_src[0]['org_id'];
            $org_customerID = $loan_src[0]['customerID'];
            $org_accountsID = $loan_src[0]['accountsID'];
            $accountBalance = $loan_src[0]['accountBalance'];
            Logger::log("Core::".__FUNCTION__
                    ." found loan source AccNo#$org_accountsID ,"
                    . " check balance ...");
            
            if($accountBalance < Configs::GLOBAL_MAX_LOAN_AMOUNT) {
                Logger::log("Core::".__FUNCTION__
                    ." $loan_src running low on funds. current amount is"
                        . " $accountBalance ...");
                return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, "No funds for loan");
            } else {
                if((float)$clean_amount > (float)$accountBalance){
                    return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, "Loan Amount is "
                            . "greater than what is available");
                }                
                Logger::log("Core::".__FUNCTION__." account balance is OK,"
                        . " #proceed processing loan, check customer #"
                        . "$clean_recv_acc");
                $customer_dt = $this->account_exists_ex(Configs::ACCOUNT_ID
                        .$clean_init_acct, $clean_parent_acct);  
                Logger::log("Core::".__FUNCTION__.' customer '
                        .print_r($customer_dt,TRUE));
                
                if($customer_dt == FALSE){
                    Logger::log("Core::".__FUNCTION__
                            ." customer not found creating ");
                    $resp = $this->createCustAccount($clean_init_acct, 
                            $org_id, date('YmdHis'));
                    if($resp['status']!= StatusCodes::STAT_CODE_OK){
                        return Utils::format_response(
                                StatusCodes::STAT_CODE_NOK,
                                StatusCodes::STAT_CODE_NOK_DESC,
                                "Unable to create customer");
                    }
                    Logger::log("Core::".__FUNCTION__
                            ." customer created ");
                    $customer_dt = $this->account_exists_ex(Configs::ACCOUNT_ID
                        .$clean_init_acct, $clean_parent_acct);
                }
                Logger::log("Core::".__FUNCTION__.' customer '
                        .print_r($customer_dt,TRUE));
                
                if(count($customer_dt) > 0 || $customer_dt != FALSE){
                    Logger::log("Core::".__FUNCTION__." found customer "
                                .  "checking loan balance ###");
                    $cust_acc_id = $customer_dt[0]['accountsID'];
                    $loan_acc_bal = $customer_dt[0]['loanAccountBalance'];
                    Logger::log("Core::".__FUNCTION__."Bal at $loan_acc_bal");
                    if($loan_acc_bal > 0){
                        Logger::log("Core::".__FUNCTION__." customer "
                                .  "has unpaid loan.");
                        return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC,
                                "Repay your previous loan");
                    } else {
                        Logger::log("Core::".__FUNCTION__." customer "
                                .  "has no unpaid loan.");                        
                        $service_query = "SELECT serviceid FROM services where "
                        . "servicename='".Configs::LOAN_KEY_EX."'";    
                        Logger::log("Core::".__FUNCTION__
                                ." Get loan service id~>"
                                . "$service_query");
                        $service_query_detail = 
                                $this->_coredb->get_record($service_query);
                        if(count($service_query_detail) <= 0) {
                            return Utils::format_response(
                                    StatusCodes::STAT_CODE_NOK,
                                    StatusCodes::STAT_CODE_NOK_DESC,
                                    "Processing error - [LOAN] service id not "
                                    . "found in database setup");                 
                        }
                        $service_id = $service_query_detail[0]['serviceid'];
                        Logger::log("Core::".__FUNCTION__." ##Create a "
                                . "transaction to recon, service_id is "
                                . "$service_id >>>>");
                        //////////////////////////////////////
                        //Create a transaction.
                        //////////////////////////////////////
                        $uniqid = isset($external_id) ? $external_id : 
                                uniqid(Configs::LOAN_KEY);
                        $ins_wallet_trx = "insert into transactions (accountid, "
                        . "uniqueTrxID,serviceid, destination, amount, "
                        . "charge_amount,transaction_type, date_created, "
                        . "date_modified) values ($cust_acc_id,"
                        . "'$uniqid',$service_id,'$clean_recv_acc',$clean_amount,"
                        . "0,'".Configs::LOAN_KEY_EX."',now(),now())";
                        
                        Logger::log("Core::".__FUNCTION__
                                ." Trx# $ins_wallet_trx");
                        $transaction_obj = 
                                $this->_coredb->add_record($ins_wallet_trx);
                        if($transaction_obj){
                            $trx_id = $this->_coredb->get_last_insertid();
                            Logger::log("Core::".__FUNCTION__
                                    ."Trx created id $trx_id");
                            //DR parent , CR customer
                            Logger::log("Core::".__FUNCTION__." Now DR parent"
                                    . " account $clean_parent_acct CR customer "
                                    . " $clean_recv_acc");
                            $dr_parent_acc = "UPDATE accounts SET "
                                    . "accountBalance="
                                    . "(accountBalance-$clean_amount) where"
                                    . " accountsID=$org_accountsID";
                            
                            Logger::log("Core::".__FUNCTION__
                                    ." query ~~> $dr_parent_acc");
                            
                            if(!$this->_coredb->update_record($dr_parent_acc)){
                                Logger::log("Core::".__FUNCTION__."WOW Panic ");
                                $err =  array(
                                    'message'=>'PANIC-insert failed',
                                    'SQL'=>$dr_parent_acc);
                                array_push($has_errors, $err);
                            }                            
                            Logger::log("Core::".__FUNCTION__." CR customer "
                                    . "account $clean_init_acct");
                            
                            $cr_cust_acc = "UPDATE accounts SET "
                                    . "loanAccountBalance="
                                    . "(loanAccountBalance+$clean_amount) "
                                    . "where accountsID=$cust_acc_id";
                            
                            Logger::log("Core::".__FUNCTION__
                                    ." query ~~~> $cr_cust_acc");
                            if(!$this->_coredb->update_record($cr_cust_acc)){
                                Logger::log("Core::".__FUNCTION__."WOW Panic ");
                                $err =  array(
                                    'message'=>'PANIC-insert failed',
                                    'SQL'=>$cr_cust_acc);
                                array_push($has_errors, $err);
                            }
                            
                            Logger::log("Core::".__FUNCTION__
                                    ." Push to GATEWAY for delivery XXXXX "
                                    .  print_r($has_errors,TRUE));
                            
                            if(count($has_errors) <= 0){
                                //Queue request to be pushed to the Gateway    
                                Logger::log("Core::".__FUNCTION__
                                    ." preparing payload for delivery XXXXX ");
                                $gateway_channels =
                                $this->configs->get_gateway_channels();
                                $gateway_channel_ids = 
                                        $this->configs->get_gateway_channel_ids();
                                $wallet_queues 
                                        = $this->configs->get_wallet_queues();
                                
                                $gateway_destination = $gateway_channels[1];
                                $gateway_channel_id 
                                        = $gateway_channel_ids[1];
                                $queue = $wallet_queues[1];
                                if ($type=='b2c'){
                                    $saf_regex = 
                                            $this->configs->get_safaricom_regex();
                                    $gateway_channel_id =
                                                    $gateway_channel_ids[1];
                                    foreach($saf_regex as $regexp){
                                        if(preg_match("/$regexp/i", 
                                                $clean_recv_acc)){
                                            $gateway_destination 
                                                    = $gateway_channels[2];
                                            $gateway_channel_id =
                                                    $gateway_channel_ids[0];
                                            $queue = $wallet_queues[0];
                                            Logger::log("Core::processLoan()->"
                                                    . "$clean_recv_acc MATCHES "
                                                    . "SAFARICOM $regexp");
                                            break;
                                        }                            
                                    }                                    
                                } else {
                                    $gateway_destination = $gateway_channels[3];
                                    $gateway_channel_id = $gateway_channel_ids[2];
                                    $queue = $wallet_queues[0];
                                }
                                //PUSH TO GATEWAY
                                $credentials = array(
                                    "username"=>  Configs::GATEWAY_USER_ID,
                                    "password"=>  Configs::GATEWAY_SECRET_ID);
                                $packet = array(
                                    "source_account"=>"$clean_loan_acct",
                                    "destination"=>"$gateway_destination",
                                    "destination_account"=>"$clean_recv_acc",
                                    "payment_date"=>date("Y-m-d H:i:s"),
                                    "amount"=>$clean_amount,
                                    "channel_id"=>$gateway_channel_id,
                                    "reference_number"=>"$uniqid",
                                    "narration"=>"Loan request from "
                                        . "wallet account $clean_loan_acct to"
                                        . " mobile number $clean_recv_acc"
                                        . " of amount $clean_amount",
                                    "extra"=>array(
                                        "type"=>"$type",
                                        "shortcode"=>$clean_loan_acct,
                                        "receiveraccount"=>$clean_recv_acc,
                                        "other"=>NULL,
                                    ),
                                );
                                $gateway_payload = array(
                                    "credentials"=>$credentials, 
                                    "packet"=>$packet);                            
                                $json = json_encode($gateway_payload);                            
                                Logger::log("Core::".__FUNCTION__."()->"
                                        . "Payload formulated -> $json SENT to "
                                        . "Wallet queue => $queue xxxxxxxxxxxx");
                                $queue_status = Utils::queue_request($json,
                                        $queue, Configs::rabbit_mq_server, 
                                        Configs::rabbit_mq_port,
                                        Configs::rabbit_mq_user, 
                                        Configs::rabbit_mq_pass);
                                Logger::log("Core::".__FUNCTION__
                                        ." queue status $queue_status");
                            }
                            $response = Utils::format_response(
                                    StatusCodes::STAT_CODE_OK,
                                    StatusCodes::STAT_CODE_OK_DESC);
                        } else {
                            return Utils::format_response(
                                    StatusCodes::STAT_CODE_NOK,
                                    StatusCodes::STAT_CODE_NOK_DESC,
                                    "Error during processing ###");
                        }
                    }
                } else {
                    Logger::log("Core::".__FUNCTION__." Something weird is "
                            . "happening");
                }
            }
            Logger::log("Core::".__FUNCTION__."->Finished");
            return $response;            
        } else {
            return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                    StatusCodes::STAT_CODE_NOK_DESC,"loan source not found");
        }
    }
    
    /**
     * Process loan repayment.
     * @param type $pin
     * @param type $source
     * @param type $destination
     * @param type $amount
     */
    public function processLoanRepayment($pin, $source,$destination, $amount, 
            $transaction_receipt_number){
        Logger::log("Core::".__FUNCTION__."->Started");
        $has_errors = array();
        Logger::log("Core::".__FUNCTION__."->Started , check loan source ####"); 
        /** Clean inputs **/
        $clean_pin = $this->_coredb->_clean_input($pin);
        if($this->ensure_safety($clean_pin)==FALSE){
            $response = Utils::format_response( StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC,"unauthorized access");
            return $response;
        }        
        $clean_loan_acct = $this->_coredb->_clean_input($destination);//this is the org_float_account
        $clean_src_acc = $this->_coredb->_clean_input($source);
        $clean_amount = $this->_coredb->_clean_input($amount);
        
        $loan_src = $this->getOrgAccount($clean_loan_acct);
        if(count($loan_src) > 0){
            $org_id = $loan_src[0]['org_id'];
            $org_customerID = $loan_src[0]['customerID'];
            $org_accountsID = $loan_src[0]['accountsID'];
            $accountBalance = $loan_src[0]['accountBalance'];
            Logger::log("Core::".__FUNCTION__
                    ." found loan source AccNo#$org_accountsID ,"
                    . " ... check customer ~~> $clean_src_acc");
            $customer_dt = $this->account_exists_ex(Configs::ACCOUNT_ID
                    .$clean_src_acc, $clean_loan_acct);  
            Logger::log("Core::".__FUNCTION__.' customer '
                    .print_r($customer_dt,TRUE));

            if($customer_dt == FALSE){
                return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Customer not found");
            } 
            
            $service_query = "SELECT serviceid FROM services where "
            . "servicename='".Configs::LOAN_KEY_EX."'";    
            Logger::log("Core::".__FUNCTION__
                    ." Get loan service id~>"
                    . "$service_query");
            $service_query_detail = 
                    $this->_coredb->get_record($service_query);
            if(count($service_query_detail) <= 0) {
                return Utils::format_response(
                        StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Processing error - [LOAN] service id not "
                        . "found in database setup");                 
            }
            $service_id = $service_query_detail[0]['serviceid'];
            Logger::log("Core::".__FUNCTION__." ##Create a "
                    . "transaction to recon, service_id is "
                    . "$service_id >>>>");
            
            $cust_acc_id = $customer_dt[0]['accountsID'];
            $bal = $customer_dt[0]['loanAccountBalance'];
            
            if($bal <= 0) {
                //refund this guy by crediting his wallet account
                $this->processCredit($pin, $clean_loan_acct, $clean_src_acc,
                        $clean_amount, uniqid("REFUND_"));
                
                //Send message
                $payload = array("msisdn"=>$clean_src_acc,
                    "message"=>"Dear customer, you have no loans. Money has been"
                    . " refunded in your eWallet account. To withdraw sms "
                    . "withdraw#amount on code 29494. Thank you.");
                
                Utils::queue_request(json_encode($payload), "PG_MESSENGER");
                //where he can withdraw the amount.
                return Utils::format_response(
                        StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "No loan to repay, money moved to customers "
                        . "eWallet account");
            }
            
            if((float)$clean_amount > (float)$bal){
                //amount that was paid is higher than the loan.
                $refund_amount = $clean_amount - $bal;
                //refund this guy by crediting his wallet account with this 
                //amount
                $this->processCredit($pin, $clean_loan_acct, $clean_src_acc,
                        $refund_amount, uniqid("REFUND_"));
                
                //Send message
                $payload = array("msisdn"=>$clean_src_acc,
                    "message"=>"Dear customer, you have no loans. Money has been"
                    . " refunded in your eWallet account. To withdraw sms "
                    . "withdraw#amount on code 29494. Thank you.");
                
                Utils::queue_request(json_encode($payload), "PG_MESSENGER");
                
            }
            //else deduct the loan
            
            //////////////////////////////////////
            //Create a transaction.
            //////////////////////////////////////
            $uniqid = uniqid(Configs::LOAN_KEY);
            $ins_wallet_trx = "insert into transactions (accountid, "
            . "uniqueTrxID,serviceid, destination, amount, "
            . "charge_amount,transaction_type, date_created, "
            . "date_modified) values ($org_customerID,"
            . "'$uniqid',$service_id,'$clean_loan_acct',$clean_amount,"
            . "0,'".Configs::CR_KEY."',now(),now())";
            
            Logger::log("Core::".__FUNCTION__." $ins_wallet_trx");
            Logger::log("Core::".__FUNCTION__
                                ." Trx# $ins_wallet_trx");
            $transaction_obj = 
                    $this->_coredb->add_record($ins_wallet_trx);
            if($transaction_obj){
                $trx_id = $this->_coredb->get_last_insertid();
                Logger::log("Core::".__FUNCTION__
                        ."Trx created id $trx_id");
                //DR parent , CR customer
                Logger::log("Core::".__FUNCTION__." Now DR customer"
                        . " account $clean_loan_acct CR parent "
                        . " $clean_src_acc");
                $dr_cust_acc = "UPDATE accounts SET "
                        . "loanAccountBalance="
                        . "(loanAccountBalance-$clean_amount) where"
                        . " accountsID=$cust_acc_id";

                Logger::log("Core::".__FUNCTION__
                        ." query ~~> $dr_cust_acc");

                if(!$this->_coredb->update_record($dr_cust_acc)){
                    Logger::log("Core::".__FUNCTION__."WOW Panic ");
                    $err =  array(
                        'message'=>'PANIC-debit failed',
                        'SQL'=>$dr_cust_acc);
                    array_push($has_errors, $err);
                }                            
                Logger::log("Core::".__FUNCTION__." CR parent "
                        . "account $clean_loan_acct");

                $cr_parent_acc = "UPDATE accounts SET "
                        . "accountBalance="
                        . "(accountBalance+$clean_amount) "
                        . "where accountsID=$org_accountsID";

                Logger::log("Core::".__FUNCTION__
                        ." query ~~~> $cr_parent_acc");
                if(!$this->_coredb->update_record($cr_parent_acc)){
                    Logger::log("Core::".__FUNCTION__."WOW Panic ");
                    $err =  array(
                        'message'=>'PANIC-credit failed',
                        'SQL'=>$cr_parent_acc);
                    array_push($has_errors, $err);
                }
                
                //Send message
                $payload = array("msisdn"=>$clean_src_acc,
                    "message"=>"Dear customer, your loan of amount KES "
                    . "$clean_amount has been settled. Thank you.");
                
                Utils::queue_request(json_encode($payload), "PG_MESSENGER");
                
                //Push back to Microlending for processing.
                //Utils::queue_request(json_encode($payload), "MICROLENDING_CALLBACK_QUEUE");
                
                $response = Utils::format_response(
                        StatusCodes::STAT_CODE_OK,
                        StatusCodes::STAT_CODE_OK_DESC,$has_errors);
                return $response;
            } else {
                return Utils::format_response(
                        StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "Error during processing ###");
            }            
        } else {        
            Logger::log("Core::".__FUNCTION__."->Finished");
            return Utils::format_response(StatusCodes::STAT_CODE_NOK,
                            StatusCodes::STAT_CODE_NOK_DESC,"Loan source"
                    . " account not found");
        }
    }
    
    /**
     * Close SQL
     */
    function flush() {
        $this->_coredb->terminate();
    }
}
