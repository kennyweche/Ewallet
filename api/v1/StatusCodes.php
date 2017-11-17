<?php

class StatusCodes{
    const MISSING_SOURCE_PARAM="Missing source account parameter in payload";
    const MISSING_PIN_PARAM="Missing PIN/AUTHORIZATION parameter in payload";
    const MISSING_DEST_PARAM="Missing destination account parameter in payload";
    const MISSING_AMT_PARAM="Missing amount parameter in payload";
    const MISSING_TYP_PARAM="Missing request parameter in payload";
    const MISSING_CHANNEL_PARAM="Missing channel parameter in payload";
    const MISSING_CHANNEL_VALUE="Missing channel value in payload";
    const AMOUNT_LESS_THAN_ZERO_ERROR = "Amount cannot be less than 0";
    const MISSING_UNIQ_ID = "Missing unique transaction identifier";
    const MISSING_MOBILE_NUMBER = "Missing mobile number in payload";
    const MISSING_TRX_ID = "Missing transaction id in payload";
    const MISSING_STATUS_PARAM = "Missing status in payload";
    const MISSING_ORG_CODE_PARAM = "Missing Organization Code in payload";
    const MISSING_ORG_BALANCE_PARAM = "Missing Organization balance amount in payload";
    const STAT_CODE_OK = 200;
    const STAT_CODE_OK_DESC ="OK";
    const STAT_CODE_NOK = 201;
    const STAT_CODE_NOK_DESC ="FAIL";
    const STAT_PAYMENT_RECEIVED =100;
    const STAT_DUPLICATE_PAYMENT =101;
    const STAT_PROCESSING_ERROR = 102;
    const STAT_PROCESSING_ERROR_DESC = "An unknown error occurred during processing";
    const STAT_ACCOUNT_NOT_REAL = 103;
    const STAT_CODE_COMPLETE = 'COMPLETE';
}