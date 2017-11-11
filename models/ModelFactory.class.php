<?php

class ModelFactory {

    public static function create($objectString, $args = array()) {
        $objectString = ucfirst($objectString);
        return new $objectString(implode(",", $args));
    }
    
    
    
    
}

?>
