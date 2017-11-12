<?php

use Httpful\Request;

// Slim auto loader
require 'vendor/autoload.php';
//require_once 'vendor/composer/autoload.php';
// Project specific libs & functions
require_once 'libs/common.php';
require_once 'libs/functions.php';

// Load the site configs
$siteConfigs = getSiteConfigs();

// Project auto loader
require_once 'Autoloader.php';

// Do server output compression 
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip")) {
    ob_start("ob_gzhandler");
} else {
    ob_start("sanitize_output");
}

// hide all errors now;
//error_reporting(0);
//ini_set("display_errors", 0);

session_name($siteConfigs['user_session_key']);
session_start();


// Instantiate Slim 
$app = new \Slim\Slim();

$app->get('/', function () use ($siteConfigs) {
    var_dump($siteConfigs, $_SESSION);

    $viewData = array(
        'title' => 'Login'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Login");
    $view->display($viewData);
});

$app->get('/oauthResponse', function () use ($siteConfigs) {

     var_dump("<br>/oauthResponse entered.. session dump..", $_SESSION);
    // first ensure there is a session set
    if( ! isset($_SESSION['client_id'])){
        echo "session not set" ; exit;
       // logout($siteConfigs);
    }
    
//     var_dump($_GET);
//     echo "<br>TEST forctype: " . ctype_alnum($_GET['code']);
//     echo "<br>TEST strlen: " . (strlen($_GET['code']) == 255);
    

    if(isset($_GET['code']) && ctype_alnum($_GET['code']) && strlen($_GET['code']) == 255){
        
        $_SESSION['oauth_authorization_code'] = $_GET['code'];
        
        $viewData = array(
            'title' => 'Access Tokens'
            , 'siteConfigs' => $siteConfigs
        );
        $view = ViewFactory::createTwigView("AccessTokens");
        $view->display($viewData);
    }
    else{
        header('Location: ' . $siteConfigs['website_www']) . "/login?msg=Invalid or empty Oauth authorization code provided";
        exit;
    }
});

$app->post('/getApiTokens', function () use ($siteConfigs) {
    
    // first ensure there is a session set
    if( ! isset($_SESSION['client_id'], $_SESSION['oauth_authorization_code'], $_SESSION['environment'])){
        logout($siteConfigs);
    }
    
    if(isset($_POST['client_secret']) && ctype_alnum($_POST['client_secret']) && strlen($_POST['client_secret']) == 255){
        
        $_SESSION["client_secret"] = $_POST['client_secret'];
        
        // buildcall backurl
        $Oauth_url = "9.70.209.35" . $siteConfigs['oAuthTokenSlug'] . "&callback_uri=" . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=" . $_SESSION['client_id'] . "&client_secret=" . $_SESSION["client_secret"] . "&code=" . $_SESSION['oauth_authorization_code'] ;
       
        try{
            $result = shell_exec("wget " . $Oauth_url);
            var_dump("</br>Shell_EXec:</br> " , $result);
            exit;
            $_h = curl_init();
            curl_setopt($_h, CURLOPT_HEADER, 1);
            curl_setopt($_h, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($_h, CURLOPT_URL, $Oauth_url );
            // curl_setopt($_h, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
            curl_setopt($_h, CURLOPT_DNS_CACHE_TIMEOUT, 2 );
            curl_setopt($_h, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($_h, CURLOPT_TIMEOUT, 600);
            var_dump(curl_exec($_h));
            var_dump(curl_getinfo($_h));
            var_dump(curl_error($_h)); 
            
            
            echo "<br><br><br>" ;
        }catch(Exception $e){
            echo "<br> Error getting content  :" ;
            var_dump($e);
            exit;
        }
        echo $Oauth_url; exit;
    }
    else{
        header('Location: ' . $siteConfigs['website_www']) . "/login?msg=Invalid or empty Oauth authorization code provided";
        exit;
    }
});
        

$app->post('/login', function () use ($siteConfigs) {
  
    // buildcall backurl
    $Oauth_url = $_POST['environment']  . $siteConfigs['oAuthAuthorizationSlug'] . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=";
    if (processOauthLogin($siteConfigs, $_POST)) {
        
        header('Location: ' . $Oauth_url . $_SESSION['client_id'] );
        exit();
    } else {
        header('Location: ' . $siteConfigs['website_www']) . "/login?msg=failed Oauth authorization";
        exit;
    }
});

$app->get('/Home', function () use ($siteConfigs) {

    if (!isset($_SESSION["user"])) {
        logout($siteConfigs);
        exit();
    }

    $viewData = array(
        'title' => 'Home'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Home");
    $view->display($viewData);
});

$app->get('/People', function () use ($siteConfigs) {

    if (!isset($_SESSION["user"])) {
        header('Location: ' . $siteConfigs['website_www']);
        exit();
    }


    $subscribers = getSubscriberList($siteConfigs, $_SESSION['user']);
    $persons[]   = array();

    if (count($subscribers['List'])) {
        foreach ($subscribers['List'] as $key => $value) {
            array_push($persons, $value['Person']);
        }
    }

  //  var_dump($persons); exit;
    $viewData = array(
        'title' => 'People'
        , 'siteConfigs' => $siteConfigs
        , 'subscribers' => $persons
    );
    $view = ViewFactory::createTwigView("People");
    $view->display($viewData);
});

$app->get('/Communities', function () use ($siteConfigs) {

    if (!isset($_SESSION["user"])) {
        header('Location: ' . $siteConfigs['website_www']);
        exit();
    }

    $viewData = array(
        'title' => 'Communities'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Communities");
    $view->display($viewData);
});

$app->get('/Files', function () use ($siteConfigs) {

    if (!isset($_SESSION["user"])) {
        header('Location: ' . $siteConfigs['website_www']);
        exit();
    }

    $viewData = array(
        'title' => 'Files'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Files");
    $view->display($viewData);
});

$app->get('/logout', function () use ($siteConfigs) {
    logout($siteConfigs);
});

$app->notFound(function () use ($siteConfigs) {
    $viewData = array(
        'title' => '404'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("404");
    $view->display($viewData);
});

$app->run();
?>
