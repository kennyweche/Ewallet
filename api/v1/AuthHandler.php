<?php

require_once 'libs/auth/PasswordHash.php';

class Auth {
    var $t_hasher;
    
    /**
     * Constructor.
     * @param type $pin
     */
    function __construct() {
        $this->t_hasher = new PasswordHash(8, FALSE);
    }
    
    /**
     * Generate a secure hash pin.
     * @return type
     */
    function generate_secure_pin($plaintext_pin){
        return $this->t_hasher->HashPassword($plaintext_pin);
    }
    
    /**
     * Validate password
     * @param type $input
     * @param type $correct
     * @return type
     */
    function isAuthenticated($input, $correct){
        if($this->t_hasher->CheckPassword($input,$correct)==1){
            return TRUE;
        }
        return FALSE;
    }
}
