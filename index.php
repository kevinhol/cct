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
// this will also strip Extended ASCII Codes eg é ú ü
foreach ($_POST as $key => $value) {
    $key[$value] = filter_var(trim($value) , FILTER_FLAG_STRIP_LOW , FILTER_FLAG_STRIP_HIGH);
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
        logout($siteConfigs, "Invalid or empty Oauth authorization code provided", $siteConfigs['boostrapAlertTypes'][3]);
    }
});

$app->post('/getApiTokens', function () use ($siteConfigs) {
    
    // first ensure there is a session set
    if (! isset($_SESSION['client_id'], $_SESSION['oauth_authorization_code'], $_SESSION['environment'])) {
        logout($siteConfigs, "Session check failed before getting Api Tokens", $siteConfigs['boostrapAlertTypes'][3]);
    }
    
    $setApiTokensResponse = setApiTokens($siteConfigs, $_POST);
    
    if (isset($setApiTokensResponse['error']) && isset($setApiTokensResponse['go_back']) && $setApiTokensResponse['go_back'] == true) {
           
            // we the referrer before redirection back to the location. 
            $referrerUrl = parse_url($_SERVER['HTTP_REFERER']);
            $designedReferrer = $siteConfigs['website_www'] . $siteConfigs['callBackSlug'];
            $actualReferrer = $referrerUrl['scheme'] . "://" . $referrerUrl['host'] . $referrerUrl['path'];
            
            if($designedReferrer != $actualReferrer){
                logout($siteConfigs, "Error detected with server referrer during token request", $siteConfigs['boostrapAlertTypes'][3]);
            }
            
            $appendToUrl = getMsgToAppendToUrl($siteConfigs, $setApiTokensResponse['error'], $siteConfigs['boostrapAlertTypes'][3], 1);
            
            header('Location: ' . $_SERVER['HTTP_REFERER'] . $appendToUrl);
            exit;
    } else if (isset($setApiTokensResponse['success'])) {
        // at this point we sbould be user authenicated and validated and have tokens to access api
        // show the homepage
        
        $userCreated = createUser($siteConfigs);

        if($userCreated){
            header('Location: '.  $siteConfigs['website_www'] . '/Home');
            exit();
        }
        else{
            logout($siteConfigs, "Sorry we failed to retrieve your user account data.", $siteConfigs['boostrapAlertTypes'][3]); // failed to create the user             
        }

    } else {
        // something weird has gone on.. time to debug
        var_dump($setApiTokensResponse); 
        exit;
        logout($siteConfigs, $setApiTokensResponse['error'], $siteConfigs['boostrapAlertTypes'][3]);
    }
});

$app->post('/login', function () use ($siteConfigs) {
    
    if (verifyEnvSelection($siteConfigs, $_POST) && verifyAppID($siteConfigs, $_POST)) {
        
        $_SESSION['environment'] = $_POST['environment'];
        $_SESSION['client_id']   = $_POST['app-id'];
        
        // buildcall backurl
        $Oauth_url = $_POST['environment'] . $siteConfigs['oAuthAuthorizationSlug'] . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=" . $_SESSION['client_id'];
        
        // should check the cloud server response to a bad app-id value .. eg "oauth_invalid_clientid" if the user is already logged in
        
        header('Location: ' . $Oauth_url);
        exit();
    } else {
        logout($siteConfigs, "Environment or App Id verification failed.", $siteConfigs['boostrapAlertTypes'][3]);
    }
});

$app->get('/Home', function () use ($siteConfigs) {
    
    if (! isset($_SESSION["user"])) {
        logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
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
    
    $subscribers = getSubscriberList($siteConfigs);
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
        logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
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
        logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
    }
    
    $viewData = array(
        'title' => 'Files',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Files");
    $view->display($viewData);
});

$app->get('/logout', function () use ($siteConfigs) {
    logout($siteConfigs, "You have successfully logged out", $siteConfigs['boostrapAlertTypes'][0]);
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
