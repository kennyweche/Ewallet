<?php

class CacheMessages {
    public static $CONNECT_ERROR=100;
    public static $SAVE_TO_CACHE_ERROR=101;
    public static $RETRIEVE_FROM_CACHE_ERROR=102;
    public static $SAVE_TO_CACHE_SUCCESS=103;
    public static $RETRIEVE_FROM_CACHE_SUCCESS=104;
    public static $DESCRIPTIONS = array(
        100=>"Connection Error",
        101=>"Error saving data to the cache",
        102=>"Error retrieving data from the cache",
        103=>"Data saved to cache",
        104=>"Retrieved data from cache",
    );
    
}
/**
 * Cache Manager.
 */
class MemcacheLib {
    /**
     * Time object/variable should stay in cache before being refreshed.
     * @var long. 
     */
    private $MEMCACHE_TIMEOUT_VALUE = 86400;
    private $mem_server = 'localhost';
    private $mem_port = 6379;
    private $redis;
    /**
     * setDefaultMemcacheTimeout.
     * @param type $timeout
     */
    private function setMemcacheTimeout($timeout) {
        $this->MEMCACHE_TIMEOUT_VALUE = $timeout;
    }
    /**
     * Constructor.
     * @param type $timeout
     */
    public function __construct($timeout = NULL, $server = NULL, $port = NULL) {
        
        if ($timeout != NULL) {
            $this->setMemcacheTimeout($timeout);
        }
        if ($server != NULL) {
            $this->mem_server = server;
        }
        if ($timeout != NULL) {
            if ($port > 0 && $port <= 65535) {
                $this->mem_port = port;
            } else {
                $this->mem_port = 6379;
            }
        }
        try {
            $this->redis = new Redis();
            $this->redis->connect($this->mem_server, $this->mem_port);
        } catch (Exception $e) {
            #Subsequent errors will be caught within the functions you are
            #calling
        }
    }
    /**
     * 
     * @param type $key
     * @param type $object
     */
    public function saveToCache($key, $object) {
        $result = array();
        try {
           $this->redis->set($key, $object, $this->MEMCACHE_TIMEOUT_VALUE);
           $result = array(
                "status"=>  CacheMessages::$SAVE_TO_CACHE_SUCCESS,
                "message"=> CacheMessages::$DESCRIPTIONS[
                    CacheMessages::$SAVE_TO_CACHE_SUCCESS]." { $key }",
                "error"=>NULL,
                "cacheTimeout"=>  $this->MEMCACHE_TIMEOUT_VALUE ." (s)",
                "results"=>array()
            );
        } catch (Exception $ex) {
            $result = array(
                "status"=>  CacheMessages::$SAVE_TO_CACHE_ERROR,
                "message"=> CacheMessages::$DESCRIPTIONS[
                    CacheMessages::$SAVE_TO_CACHE_ERROR]." { $key }",
                "error"=>$ex->getMessage(),
                "cacheTimeout"=>  $this->MEMCACHE_TIMEOUT_VALUE ." (s)",
                "results"=>array()
            );
        }
        return $result;
    }
    /**
     * 
     * @param type $key
     */
    public function retrieveFromCache($key) {
        $result = NULL;
        try {
            $data = $this->redis->get($key);
            $result = array(
                "status"=>  CacheMessages::$RETRIEVE_FROM_CACHE_SUCCESS,
                "message"=> CacheMessages::$DESCRIPTIONS[
                    CacheMessages::$RETRIEVE_FROM_CACHE_SUCCESS]." { $key }",
                "error"=>NULL,
                "cacheTimeout"=>  $this->MEMCACHE_TIMEOUT_VALUE ." (s)",
                "results"=>$data
            );
        } catch (Exception $ex) {
            $result = array(
                "status"=>  CacheMessages::$RETRIEVE_FROM_CACHE_ERROR,
                "message"=> CacheMessages::$DESCRIPTIONS[
                    CacheMessages::$RETRIEVE_FROM_CACHE_ERROR]." { $key }",
                "error"=>$ex->getMessage(),
                "cacheTimeout"=>  $this->MEMCACHE_TIMEOUT_VALUE ." (s)",
                "results"=>array(),
            );
        }
        return $result;
    }
}

