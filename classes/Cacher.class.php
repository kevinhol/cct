<?php

class Cacher implements CacherInterface {

    protected static $cacher = null;
    private static $settings;

    private function __construct() {
        
    }

    public static function getcache() {
        return self::getInstance();
    }

    public static function getInstance() {
        if (!empty(self::$cacher)) {
            return self::$cacher;
        }
        try {
            self::$settings = parse_ini_file("./configs/main.ini");
            if (!class_exists("Memcache")) {
                return false;
            }
            self::$cacher = new Memcache();
            self::$cacher->connect(
                self::$settings['cache_server_host']
                , self::$settings['cache_server_port']
            );
        } catch (Exception $e) {
            // TODO log error and mitigate..
            echo "Error connecting memcache";
            die();
        }
        return self::$cacher;
    }

    public static function getData($cache_key) {
        if (self::getInstance()) {
            return self::getInstance()->get($cache_key);
        }
        return false;
    }

    public static function setData($cache_key, $data, $expire = 0) {
        if (self::getInstance()) {
            return self::getInstance()->set($cache_key, $data, MEMCACHE_COMPRESSED, $expire);
        }
        return false;
    }

    public static function deleteData($cache_key) {
        if (self::getInstance()) {
            return self::getInstance()->delete($cache_key);
        }
        return false;
    }

}

?>
