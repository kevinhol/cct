<?php
use Httpful\Request;

// Slim auto loader
require 'vendor/autoload.php';

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

// sanitize all post array
foreach ($_POST as $key => $value) {
    $key[$value] = trim($value);
}
filter_var_array($_POST, FILTER_SANITIZE_STRING);

// hide all errors now;
// error_reporting(0);
// ini_set("display_errors", 0);

session_name($siteConfigs['user_session_key']);
session_start();

// Instantiate Slim
$app = new \Slim\Slim();

$app->get('/', function () use ($siteConfigs) {
    
    $viewData = array(
        'title' => 'Login',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Login");
    $view->display($viewData);
});

$app->get($siteConfigs['callBackSlug'], function () use ($siteConfigs) {
    
    if (! isset($_SESSION['client_id'])) {
        logout($siteConfigs);
    }
    
    if (isset($_GET['code']) && ctype_alnum($_GET['code']) && strlen($_GET['code']) <= 256) {
        
        $_SESSION['oauth_authorization_code'] = $_GET['code'];
        
        $viewData = array(
            'title' => 'Access Tokens',
            'siteConfigs' => $siteConfigs
        );
        $view = ViewFactory::createTwigView("AccessTokens");
        $view->display($viewData);
    } else {
        logout($siteConfigs, "Invalid or empty Oauth authorization code provided", true);
    }
});

$app->post('/getApiTokens', function () use ($siteConfigs) {
    
    // first ensure there is a session set
    if (! isset($_SESSION['client_id'], $_SESSION['oauth_authorization_code'], $_SESSION['environment'])) {
        $error_message = "/getApiTokens failed on session check";
        logout($siteConfigs, $error_message, true);
    }
    
    $setApiTokensResponse = setApiTokens($siteConfigs, $_POST);
    
    if (isset($setApiTokensResponse['error'])) {
        logout($siteConfigs, $api_tokens['error'], true);
    } else if (isset($setApiTokensResponse['success'])) {
        // at this point we sbould be user authenicated and validated and have tokens to access api
        // show the homepage
        echo "</hr><p>  at this point we sbould be user authenicated and validated and have tokens to access api </br> show the homepage </p>";
        // set up auth user and call getUserIdentity etc from functions line 126 onwards
        exit();
    } else {
        // something weird has gone on.. time to debug
        // var_dump($setApiTokensResponse);
    }
});

$app->post('/login', function () use ($siteConfigs) {
    
    if (verifyEnvSelection($siteConfigs, $_POST) && verifyAppID($siteConfigs, $_POST)) {
        
        $_SESSION['environment'] = $_POST['environment'];
        $_SESSION['client_id']   = $_POST['app-id'];
        
        // buildcall backurl
        $Oauth_url = $_POST['environment'] . $siteConfigs['oAuthAuthorizationSlug'] . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=";
        
        header('Location: ' . $Oauth_url . $_SESSION['client_id']);
        exit();
    } else {
        logout($siteConfigs, "Oauth authorization failed", true);
    }
});

$app->get('/Home', function () use ($siteConfigs) {
    
    if (! isset($_SESSION["user"])) {
        logout($siteConfigs, "You are not logged in yet", true);
    }
    
    $viewData = array(
        'title' => 'Home',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Home");
    $view->display($viewData);
});

$app->get('/People', function () use ($siteConfigs) {
    
    if (! isset($_SESSION["user"])) {
        logout($siteConfigs, "You are not logged in yet", true);
    }
    
    $subscribers = getSubscriberList($siteConfigs, $_SESSION['user']);
    $persons[] = array();
    
    if (count($subscribers['List'])) {
        foreach ($subscribers['List'] as $key => $value) {
            array_push($persons, $value['Person']);
        }
    }
    
    $viewData = array(
        'title' => 'People',
        'siteConfigs' => $siteConfigs,
        'subscribers' => $persons
    );
    $view = ViewFactory::createTwigView("People");
    $view->display($viewData);
});

$app->get('/Communities', function () use ($siteConfigs) {
    
    if (! isset($_SESSION["user"])) {
        logout($siteConfigs, "You are not logged in yet", true);
    }
    
    $viewData = array(
        'title' => 'Communities',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Communities");
    $view->display($viewData);
});

$app->get('/Files', function () use ($siteConfigs) {
    
    if (! isset($_SESSION["user"])) {
        logout($siteConfigs, "You are not logged in yet", true);
    }
    
    $viewData = array(
        'title' => 'Files',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Files");
    $view->display($viewData);
});

$app->get('/logout', function () use ($siteConfigs) {
    logout($siteConfigs);
});

$app->notFound(function () use ($siteConfigs) {
    $viewData = array(
        'title' => '404',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("404");
    $view->display($viewData);
});

$app->run();
?>
