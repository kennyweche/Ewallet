<?php
require_once 'Configs.php';

class DB {
    var $conn = NULL;
    var $insert_id;
    
    /**
     * COnstructor for DB.
     */
    function __construct() {
        $this->conn = new mysqli(Configs::db_host, Configs::db_user,
                Configs::db_pass, Configs::db_name);
    }
    
    /**
     * Clean input string.
     * 
     * @param type $input
     * @return type
     */
    function _clean_input($input){
        return $this->conn->real_escape_string($input);
    }
    
    function setAutoCommitOff(){
        $this->conn->autocommit(FALSE);
    }
    
    function commitIt(){
        $this->conn->commit();
    }
    
    function rollItBack(){
        $this->conn->rollback();
    }
    
    /**
     * Add single record to database.
     * @param type $query
     */
    function add_record($query) {
        try {
            $this->conn->autocommit(FALSE);
            $this->conn->query($query);
            $this->insert_id = $this->conn->insert_id;
            $this->conn->commit();
            return TRUE;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return FALSE;
        }
        return FALSE;
    }
    
    /**
     * Get insert id.
     * @return type
     */
    function get_last_insertid(){
        return $this->insert_id;
    }
    
    /**
     * Update a record.
     * @param type $query
     */
    function update_record($query){
        $status = FALSE;
        try{
            $this->conn->autocommit(FALSE);
            if($this->conn->query($query)){
                $status = TRUE;
            }
            $this->conn->commit();
        } catch (Exception $ex) {
            $this->conn->rollback();
            $status = FALSE;
        }
        return $status;
    }
    
    /**
     * Fetch record.
     * @param type $query
     */
    function get_record($query){
        $result = $this->conn->query($query);
        $data = array();
        if($result) {
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }        
        return $data;
    }
    
    /**
     * Get the status code description.
     * 
     * @param type $code
     * @return type
     */
    function _get_status_desc($code){
        $query = "SELECT description FROM status_codes WHERE status_codes=$code;";
        $data = $this->get_record($query)[0];
        return $data['description'];
    }
    
    function terminate(){
        $this->conn->close();
    }
}
