<?php

class ViewFactory {

    public static function createTwigView($template) {

        $template = ucfirst($template . ".twig");

        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(basename(__DIR__) . "/templates");

        $twig = new Twig_Environment($loader, array(
            'debug' => true,
//            'cache' => './tmp/cache',
            'auto_reload' => true
        ));

        $twig->addExtension(new Twig_Extension_Debug());
        $twig->addGlobal("session", $_SESSION);
        
        return $twig->loadTemplate($template);
    }

}

?>
