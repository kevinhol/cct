<?php

spl_autoload_register('Autoloader::load');

class Autoloader {

    private static $basepath = ".";
    private static $extensions = array(
        ".php"
        , ".class.php"
    );
    private static $directories = array(
        "classes"
        , "models"
        , "views"
        , "controllers"
    );

    public static function load($class) {
        if ($class == null) {
            return;
        }
        spl_autoload_extensions(implode(',', self::$extensions));
        spl_autoload_register(NULL, false); // flush existing autoloads

        foreach (self::$extensions as $ext) {
            foreach (self::$directories as $dir) {
                $filePath = self::$basepath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $class . $ext;
                if (file_exists($filePath)) {
                      require $filePath;
                }
            }
        }
        
    }

}

?>
