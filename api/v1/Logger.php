<?php

$today = date('Y-m-d');

$LOG_PATH = "/var/log/flask/roamtech/ewallet-$today.log";

class Logger {
    /**
     * Log messages
     * @param type $msg
     */
    public static function log($msg) {
        global $LOG_PATH;
        $fp = fopen($LOG_PATH, "ab+");
        if ($fp) {
            fwrite($fp, date('Y-m-d H:i:s') . "| " . $msg);
            fwrite($fp, "\n");
            fclose($fp);
        }
    }
}
