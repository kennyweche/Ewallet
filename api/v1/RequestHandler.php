<?php

require_once 'StatusCodes.php';
require_once 'CoreHandler.php';
require_once 'Logger.php';

class RequestHandler {
    public $last_request;
    
    /**
     * Process the request that has just come in.
     * @param type $input
     * @return string
     */
    function process($input) {
        $response = array();        
        Logger::log("RequestHandler::process()->working #####");        
        try {
            $json_in = json_decode($input, TRUE);
            Logger::log("RequestHandler::process()->decoded request OK #####");
        } catch (Exception $ex) {
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Missing 'requestType' parameter");
        }
        //we passed
        Logger::log("RequestHandler::process()->filter request type #####");
        $request_is = strtolower($json_in['request']);
        $this->last_request = $request_is;
        Logger::log("RequestHandler::process()-> Request-> $request_is");
        switch ($request_is) {
            case 'loan':
                $response = $this->validate_requestloan_params($input);                
                break;
            case 'repayloan':
                $response = $this->validate_repayloan_params($input);                
                break;
            case 'deposit':
                $response = $this->validate_deposit_params($input);                
                break;
            case 'withdrawal':                
                $response = $this->validate_withdrawal_params($input);
                break;
            case 'balance':
                $response = $this->validate_balance_params($input);
                break;
            case 'loanbalance':
                $response = $this->validate_balance_params($input);
                break;
            case 'credit':
                $response = $this->validate_credit_params($input);
                break;
            case 'dlr':
                $response = $this->validate_dlr_params($input);
                break;
            case 'updorgbal':
                $response = $this->validate_org_bal_upd_params($input);
                break;
            default :
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC,
                        "request $request_is not found");
                break;
        }
        return $response;
    }

    /**
     * Get loan.
     * @param type $raw
     */
    function validate_requestloan_params($raw){
        $errors = array();
        $response = array();
        $loan_source = NULL;
        $account_source=NULL;
        $initiator_account=NULL;
        $receiver_account=NULL;
        $loan_amount=NULL;
        $pin=NULL;
        $type = Configs::LOAN_TYPE_INTERNAL;
        
        Logger::log("RequestHandler::".__FUNCTION__." working ###");
        try{
            $input = json_decode($raw, TRUE);   
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }  
            
            if (isset($input['account_source'])) {
                $account_source = $input['account_source'];
                if (strcasecmp($account_source, "")==0) {
                    array_push($errors, "Empty account_source param");
                }
            } else {
                array_push($errors, "Missing account_source param");
            }
            
            if (isset($input['initiator_account'])) {
                $initiator_account = $input['initiator_account'];
                if (strcasecmp($account_source, "")==0) {
                    array_push($errors, "Empty initiator_account param");
                }
            } else {
                array_push($errors, "Missing initiator_account param");
            }
            
            if (isset($input['receiver_account'])) {
                $receiver_account = $input['receiver_account'];
                if (strcasecmp($receiver_account, "")==0) {
                    array_push($errors, "Empty receiver_account param");
                }
            } else {
                array_push($errors, "Missing receiver_account param");
            }
            
            if (isset($input['loan_amount'])) {
                $loan_amount = $input['loan_amount'];
                if (strcasecmp($loan_amount, "")==0) {
                    array_push($errors, "Empty loan_amount param");
                }
                if(!is_numeric($loan_amount) && $loan_amount <= 0){
                    array_push($errors, "Invalid loan_amount param."
                            . " Must be digit/number > 0");
                }
            } else {
                array_push($errors, "Missing loan_amount param");
            }
            
            if (isset($input['type'])) {
                $type = $input['type'];
            }
            
            Logger::log("RequestHandler::".__FUNCTION__
                    .", customer -> $receiver_account in bank $account_source wants"
                    . " to borrow amount KES $loan_amount from $loan_source ###");
            Logger::log("#####Processing loan#####");
            
            Logger::log("Finished with internal validation, proceed to Core"
                    . " processing #####");            
            if (count($errors) == 0) {
                $core = new Core();
                $response = $core->processLoan($pin, $account_source, 
                        $initiator_account, $receiver_account, $loan_amount,
                        $type);
                $core->flush();                
            } else {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }            
            Logger::log("Core processing finished. Send request back to "
                    . "RequestHandler ###");    
        } catch (Exception $ex) {
            Logger::log("RequestHandler::".__FUNCTION__." |error ->"
                    .$ex->getMessage()." ###");
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                    StatusCodes::STAT_CODE_NOK_DESC,
                    "Missing required parameters");
        }
        return $response;
    }
    
    /**
     * Repay loan.
     * @param type $raw
     */
    function validate_repayloan_params($raw){
        $response = array();
        $source = NULL;
        $destination = NULL;
        $amount = NULL;       
        $pin = NULL;
        $uniqID = NULL;
        $errors = array();        
        Logger::log("RequestHandler::validate_deposit_params() working ###");        
        try {            
            $input = json_decode($raw, TRUE);        
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }        
            if (isset($input['source'])) {
                $source = $input['source'];
                if (strcasecmp($source, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }
            if (isset($input['destination'])) {
                $destination = $input['destination'];
                if (strcasecmp($destination, "")==0) {
                    array_push($errors, StatusCodes::MISSING_DEST_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_DEST_PARAM);
            }
            
            if (isset($input['uniqueID'])) {
                $uniqID = $input['uniqueID'];
                if (strcasecmp($uniqID, "")==0) {
                    array_push($errors, StatusCodes::MISSING_UNIQ_ID);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_UNIQ_ID);
            }   
            
            if (isset($input['amount'])) {
                $amount = (double)$input['amount'];
                if($amount <= 0){
                    array_push($errors, 
                            StatusCodes::AMOUNT_LESS_THAN_ZERO_ERROR);
                }
            } else {
               array_push($errors, StatusCodes::MISSING_AMT_PARAM);
            }
            
            Logger::log("Finished with internal validation, proceed to Core"
                    . " processing #####");            
            if (count($errors) == 0) {
                $core = new Core();
                $core_response = $core->processLoanRepayment($pin, $source,
                        $destination, $amount, $uniqID);
                $core->flush();
                $response = $core_response;
            } else {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }            
        } catch (Exception $ex) {
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        return $response;
    }
    
    /**
     * Validates the parameters for deposit
     * @param type $input
     */
    function validate_deposit_params($raw) {
        $response = array();
        $source = NULL;
        $destination = NULL;
        $amount = NULL;
        $firstname = NULL;
        $lastname = NULL;
        $uniqID = NULL;        
        $account_no= NULL;
        $pin = NULL;
        $errors = array();        
        Logger::log("RequestHandler::validate_deposit_params() working ###");        
        try {            
            $input = json_decode($raw, TRUE);        
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }        
            if (isset($input['source'])) {
                $source = $input['source'];
                if (strcasecmp($source, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }
            if (isset($input['destination'])) {
                $destination = $input['destination'];
                if (strcasecmp($destination, "")==0) {
                    array_push($errors, StatusCodes::MISSING_DEST_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_DEST_PARAM);
            }
            if (isset($input['amount'])) {
                $amount = (double)$input['amount'];
                if($amount <= 0){
                    array_push($errors, 
                            StatusCodes::AMOUNT_LESS_THAN_ZERO_ERROR);
                }
            } else {
               array_push($errors, StatusCodes::MISSING_AMT_PARAM);
            }
            if (isset($input['uniqueID'])) {
                $uniqID = $input['uniqueID'];
                if (strcasecmp($uniqID, "")==0) {
                    array_push($errors, StatusCodes::MISSING_UNIQ_ID);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_UNIQ_ID);
            }            
            if (isset($input['firstname'])) {
                $firstname = $input['firstname'];
            }            
            if (isset($input['lastname'])) {
                $lastname = $input['lastname'];
            }     
            if (isset($input['account_no'])) {
                $account_no = $input['account_no'];
            }     
            Logger::log("Finished with internal validation, proceed to Core"
                    . " processing #####");            
            if (count($errors) == 0) {
                $core = new Core();
                $core_response = $core->processDeposit($pin, $source,
                        $destination, $amount, $uniqID, $firstname,
                        $lastname,$account_no);
                $core->flush();
                $response = $core_response;
            } else {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }            
            Logger::log("Core processing finished. Send request back to "
                    . "RequestHandler ###");            
        } catch (Exception $ex) {
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        return $response;
    }
    
    /**
     * Validates the parameters for deposit
     * @param type $input
     */
    function validate_credit_params($raw) {
        $response = array();
        $source = NULL;
        $destination = NULL;
        $amount = NULL;
        $uniqID = NULL;        
        $pin = NULL;
        $errors = array();        
        Logger::log("RequestHandler::validate_credit_params() working ###");        
        try {            
            $input = json_decode($raw, TRUE);        
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }        
            if (isset($input['source'])) {
                $source = $input['source'];
                if (strcasecmp($source, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }
            if (isset($input['destination'])) {
                $destination = $input['destination'];
                if (strcasecmp($destination, "")==0) {
                    array_push($errors, StatusCodes::MISSING_DEST_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_DEST_PARAM);
            }
            if (isset($input['amount'])) {
                $amount = (double)$input['amount'];
                if($amount <= 0){
                    array_push($errors, 
                            StatusCodes::AMOUNT_LESS_THAN_ZERO_ERROR);
                }
            } else {
               array_push($errors, StatusCodes::MISSING_AMT_PARAM);
            }
            if (isset($input['uniqueID'])) {
                $uniqID = $input['uniqueID'];
                if (strcasecmp($uniqID, "")==0) {
                    array_push($errors, StatusCodes::MISSING_UNIQ_ID);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_UNIQ_ID);
            }            
            Logger::log("Finished with internal validation, proceed to Core"
                    . " processing #####");            
            if (count($errors) == 0) {
                $core = new Core();
                $response =$core->processCredit($pin, 
                        $source, $destination, $amount, $uniqID); 
                $core->flush();
            } else {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }            
            Logger::log("Core processing finished. Send request back to "
                    . "RequestHandler ###");            
        } catch (Exception $ex) {
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        return $response;
    }

    /**
     * Validates the parameters for withdrawals
     * @param type $raw
     */
    function validate_withdrawal_params($raw) {
        $response = array();
        $source = NULL;
        $destination = NULL;
        $amount = NULL;   
        $pin = NULL;
        $withdraw_type = 'b2c';
        $errors = array();        
        Logger::log("RequestHandler::validate_withdrawal_params() working ###");
        try {
            $input = json_decode($raw, TRUE);            
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }            
            if (isset($input['source'])) {
                $source = $input['source'];
                if (strcasecmp($source, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }
            if (isset($input['destination'])) {
                $destination = $input['destination'];
                if (strcasecmp($destination, "")==0) {
                    array_push($errors, StatusCodes::MISSING_DEST_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_DEST_PARAM);
            }
            if (isset($input['amount'])) {
                $amount = (double)$input['amount'];
                if($amount <= 0){
                    array_push($errors, 
                            StatusCodes::AMOUNT_LESS_THAN_ZERO_ERROR);
                }
            } else {
               array_push($errors, StatusCodes::MISSING_AMT_PARAM);
            }            
            if (isset($input['type'])) {
                $type = $input['type'];
                if(strcasecmp($type, "") !=0 ){
                    $withdraw_type = $type;
                }
            }
            if (count($errors) > 0) {                
                Logger::log("RequestHandler::validate_withdrawal_params()"
                        . "-Errors with payload received");                
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, $errors);
                return $response;
            }
            Logger::log("RequestHandler::validate_withdrawal_params()->"
                    . "working ####");            
            $core = new Core();
            $response = $core->processWithdrawal($source, $destination, $amount
                    , $pin, $withdraw_type);
            $core->flush();
            Logger::log("RequestHandler::validate_withdrawal_params()->"
                    . "finished. Send response back");
            return $response;
        } catch (Exception $ex) {
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        return $response;
    }
    
    /**
     * Validate GetBalance parameters.
     * @param type $raw
     */
    function validate_balance_params($raw){
        $response = array();
        $errors = array();        
        $source = NULL;
        $pin=NULL;        
        Logger::log("RequestHandler::validate_balance_params() working ###");        
        try {
            $input = json_decode($raw, TRUE);
            if (isset($input['account'])) {
                $source = $input['account'];
                if (strcasecmp($source, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }            
            if (isset($input['pin'])) {
                $pin = $input['pin'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }            
            if (count($errors)>0) {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, $errors);
            } else {
                $core = new Core();               
                $response = $core->processBalance($source, $pin);
                $core->flush();
            }
        } catch (Exception $ex) {            
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }        
        Logger::log("RequestHandler::validate_balance_params()->finished");        
        return $response;
    }
    
    /**
     * Add account
     * @param type $raw
     */
    function validate_add_acc_params($raw){
        $response = array();
        $errors = array();
        $mobile_no = NULL;
        $initial_amt = NULL;
        $notify = NULL;
        $firstname = NULL;
        $lastname = NULL;        
        Logger::log("RequestHandler::validate_add_acc_params()->start");
        try {
            $input = json_decode($raw, TRUE);
            if (isset($input['msisdn'])) {
                $mobile_no = $input['msisdn'];
                if (strcasecmp($mobile_no, "")==0) {
                    array_push($errors, StatusCodes::MISSING_MOBILE_NUMBER);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_MOBILE_NUMBER);
            }            
            if (isset($input['amount'])) {
                $initial_amt = $input['amount'];
                if(is_numeric($initial_amt)){
                    $initial_amt = (double)$initial_amt;
                }
            }            
            if (isset($input['firstname'])) {
                $firstname = $input['firstname'];                
            }            
            if (isset($input['lastname'])) {
                $lastname = $input['lastname'];                
            }            
            if (isset($input['notify'])) {
                $notify = $input['notify'];                
            }            
            if(count($errors) > 0) {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
                return $response;
            }            
            Logger::log("RequestHandler::validate_add_acc_params()->push to "
                    . "Core processor ####");            
            //$core = new Core();
            //$response = $core->createCustAccount($mobile_no, $initial_amt,
            //        $firstname,$lastname,$notify);
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC);
            return $response;
            //$core->flush();
            //return $response;            
        } catch (Exception $ex) {
             $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
                     StatusCodes::STAT_CODE_NOK_DESC,
                     "Missing required parameters", $ex->getMessage());
        }
        Logger::log("RequestHandler::validate_add_acc_params()->finished");
        return $response;
    }
    
    /**
     * Validate interest/bonus
     * @param type $raw
     */
    function validate_interest_params($raw) {
        $response = array();
        $errors = array();        
        $destination = NULL;
        $pin=NULL;
        $amount=NULL;        
        Logger::log("RequestHandler::validate_interest_params() working ###");        
        try {
            $input = json_decode($raw, TRUE);
            if (isset($input['destination'])) {
                $destination = $input['destination'];
                if (strcasecmp($destination, "")==0) {
                    array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_SOURCE_PARAM);
            }            
            if (isset($input['authorization'])) {
                $pin = $input['authorization'];
                if (strcasecmp($pin, "")==0) {
                    array_push($errors, StatusCodes::MISSING_PIN_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_PIN_PARAM);
            }
            
            if (isset($input['amount'])) {
                $amount = (double)$input['amount'];
                if($amount <= 0){
                    array_push($errors, StatusCodes::AMOUNT_LESS_THAN_ZERO_ERROR);
                }
            } else {
               array_push($errors, StatusCodes::MISSING_AMT_PARAM);
            }            
            if (count($errors)>0) {
                $response = Utils::format_response(StatusCodes::STAT_CODE_NOK,
                        StatusCodes::STAT_CODE_NOK_DESC, $errors);
            } else {
                $core = new Core();               
                $response = $core->processInterest($pin, $destination, $amount);
                $core->flush();
            }
        } catch (Exception $ex) {            
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }        
        Logger::log("RequestHandler::validate_interest_params()->finished");        
        return $response;
    }

    /**
     * Update status of transactions.
     * @param type $raw
     */
    function validate_dlr_params($raw) {
        $response = array();
        $errors = array();
        Logger::log("RequestHandler::validate_dlr_params()->started"); 
        try {
            $input = json_decode($raw, TRUE);
            if (isset($input['id'])) {
                $trans_id = $input['id'];
                if (strcasecmp($trans_id, "")==0) {
                    array_push($errors, StatusCodes::MISSING_TRX_ID);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_TRX_ID);
            }
            if (isset($input['status'])) {
                $status = $input['status'];
                if (strcasecmp($status, "")==0) {
                    array_push($errors, StatusCodes::MISSING_STATUS_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_STATUS_PARAM);
            }
            $external_receipt_no = NULL;
            if (isset($input['external_receipt_no'])) {
                $external_receipt_no = $input['external_receipt_no'];
            }

            if (count($errors)>0){
                return Utils::format_response(
                        StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }
            #inovke core
            $core = new Core();
            $response = $core->processDLR($trans_id, $status, 
                    $external_receipt_no);
            $core->flush();
            return $response;
        }  catch (Exception $ex){
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        Logger::log("RequestHandler::validate_dlr_params()->finished"); 
        return $response;
    }
    
    /**
     * Update status of transactions.
     * @param type $raw
     */
    function validate_org_bal_upd_params($raw) {
        $response = array();
        $errors = array();
        Logger::log("RequestHandler::validate_org_bal_upd_params()->started"); 
        try {
            $input = json_decode($raw, TRUE);
            if (isset($input['code'])) {
                $code = $input['code'];
                if (strcasecmp($code, "")==0) {
                    array_push($errors, StatusCodes::MISSING_ORG_CODE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_ORG_CODE_PARAM);
            }
            if (isset($input['balance'])) {
                $balance = $input['balance'];
                if (strcasecmp($balance, "")==0) {
                    array_push($errors, StatusCodes::MISSING_ORG_BALANCE_PARAM);
                }
            } else {
                array_push($errors, StatusCodes::MISSING_ORG_BALANCE_PARAM);
            }
            if (count($errors)>0){
                return Utils::format_response(
                        StatusCodes::STAT_CODE_NOK, 
                        StatusCodes::STAT_CODE_NOK_DESC,$errors);
            }
            #inovke core
            $core = new Core();
            $response = $core->processOrgBalance($code,(float)$balance);
            $core->flush();
            return $response;
        }  catch (Exception $ex){
            $response = Utils::format_response(StatusCodes::STAT_CODE_NOK, 
            StatusCodes::STAT_CODE_NOK_DESC,"Missing required parameters");
        }
        Logger::log("RequestHandler::validate_dlr_params()->finished"); 
        return $response;
    }
}
