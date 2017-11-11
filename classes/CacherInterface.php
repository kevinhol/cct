<?php

interface CacherInterface {

    
    static function getCache();
    
    static function getData($key);
    
    static function setData($key, $data, $expire = 0);
    
    static function deleteData($key);
}

?>
