<?php

use Httpful\Request;

// Slim auto loader
require 'vendor/autoload.php';

// Project specific libs & functions
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
sanitize_input($_POST);

session_name($siteConfigs['user_session_key']);
session_start();

// Instantiate Slim
$app = new \Slim\Slim();

// set env for test/debug on localhost
$debugMode = (substr($_SERVER['SERVER_NAME'], 0, strlen("localhost")) === "localhost") ? true : false;
if ($debugMode) {
    // for testing purposes of the UI
    $siteConfigs['pages_with_forms'] = 'Playground';
    
    $siteConfigs["website_www"] = $siteConfigs["debug_website_www"];
    $_SESSION['user']['subscriberid'] = 1;
    $_SESSION['user']['email'] = 'test@test.com';
} else {
    // hide all errors now;
    error_reporting(0);
    ini_set("display_errors", 0);
}

require_once 'secureHttpHeaders.php';

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
        
        if ($designedReferrer != $actualReferrer) {
            logout($siteConfigs, "Error detected with server referrer during token request", $siteConfigs['boostrapAlertTypes'][3]);
        }
        
        $appendToUrl = getMsgToAppendToUrl($siteConfigs, $setApiTokensResponse['error'], $siteConfigs['boostrapAlertTypes'][3], 1);
        
        header('Location: ' . $_SERVER['HTTP_REFERER'] . $appendToUrl);
        exit();
    } else if (isset($setApiTokensResponse['success'])) {
        // at this point we sbould be user authenicated and validated and have tokens to access api
        // show the homepage
        
        $userCreated = createUser($siteConfigs);
        
        if ($userCreated) {
            header('Location: ' . $siteConfigs['website_www'] . '/Home');
            exit();
        } else {
            logout($siteConfigs, "Sorry we failed to retrieve your user account data.", $siteConfigs['boostrapAlertTypes'][3]); // failed to create the user
        }
    } else {
        // something weird has gone on.. time to debug
        var_dump($setApiTokensResponse);
        exit();
        logout($siteConfigs, $setApiTokensResponse['error'], $siteConfigs['boostrapAlertTypes'][3]);
    }
});

$app->post('/login', function () use ($siteConfigs) {
    
    if (verifyEnvSelection($siteConfigs, $_POST) && verifyAppID($siteConfigs, $_POST)) {
        
        $_SESSION['environment'] = $_POST['environment'];
        $_SESSION['client_id'] = $_POST['app-id'];
        
        // buildcall backurl
        $Oauth_url = $_SESSION['environment'] . $siteConfigs['oAuthAuthorizationSlug'] . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=" . $_SESSION['client_id'];
        
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
        logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
    }
    
    // set up for pagination
    if (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 1) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }
    
    $subscribers = getSubscriberList($siteConfigs, $page);
    
    $subscriberList = empty($subscribers['List']) ? array() : $subscribers['List'];
    
    // unfortunately for pagination to work we must make a second
    // request to test if there are more subscribers available
    $lookahead = getSubscriberList($siteConfigs, $page + 1);
    
    $more = empty($lookahead['List']) ? 0 : 1;
    
    $viewData = array(
        'title' => 'People',
        'siteConfigs' => $siteConfigs,
        'subscribers' => $subscribers['List'],
        'page' => $page,
        'more' => $more
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
    
    logout($siteConfigs, "You have been successfully logged out", $siteConfigs['boostrapAlertTypes'][0]);
});

$app->notFound(function () use ($siteConfigs) {
    $viewData = array(
        'title' => '404',
        'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("404");
    $view->display($viewData);
});

$app->get('/logout', function () use ($siteConfigs) {
    logout($siteConfigs, "You have successfully logged out", $siteConfigs['boostrapAlertTypes'][0]);
});

$app->get('/Playground', function () use ($siteConfigs) {
    
    $subscribers = array();
    $subscribers['List'] = array();
    
    for ($i = 0; $i < 5; $i ++) {
        
        $state = ($i % 2 == 0) ? "ACTIVE" : "PENDING";
        if ($i == 3) {
            $state = "REMOVE_PENDING";
        }
        $person = array(
            "Id" => $i,
            "Person" => array(
                "DisplayName" => "TestU" . $i,
                "EmailAddress" => "TestU" . $i . "@mail.com",
                "RoleSet" => array(
                    "User",
                    "Administrator"
                )
            ),
            "SubscriberState" => $state
        
        );
        
        array_push($subscribers['List'], $person);
    }
    
    // set up for pagination
    if (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 1) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }
    
    // unfortunately for pagination to work we must make a second
    // request to test if there are more subscribers available
    // $lookahead = getSubscriberList($siteConfigs, $page + 1 );
    $more = 1;
    
    $viewData = array(
        'title' => 'People',
        'siteConfigs' => $siteConfigs,
        'subscribers' => $subscribers['List'],
        'page' => $page,
        'more' => $more
    );
    
    $view = ViewFactory::createTwigView("Playground");
    $view->display($viewData);
});

/*
 * Ajax endpoint $siteConfigs['ajaxEndpointSlug']
 */
$app->post('/ajax', function () use ($siteConfigs) {
    
    $response = array();
    
    if (! isset($_SESSION["user"])) {
        $response["success"] = false;
        $response["message"] = "You are not logged in";
        $response["action"] = "logout";
    } else if (! (isset($_POST['action']) && in_array($_POST['action'], $siteConfigs['validAjaxActions']))) {
        $response["success"] = false;
        $response["message"] = "Your request is not a valid";
        $response["action"] = "";
    } else {
        $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'suspendUser':
            case 'unsuspendUser':
                
                $suspend = ($action == 'suspendUser') ? true : false;
                
                $functionResponse = suspendUser($siteConfigs, $_POST['subscriberId'], $suspend);
                
                if ($functionResponse["success"] == true) {
                    $response["success"] = true;
                } else {
                    $response["success"] = false;
                    $response["message"] = "Sorry, an error occurred processing your request.";
                    $response["action"] = "";
                    $response["debug"] = $functionResponse;
                }
                break;
            
            default:
                $response["success"] = false;
                $response["message"] = "Sorry, an error occurred processing your request.";
                $response["action"] = "";
                $response["debug"] = "default case";
        }
    }
    
    echo json_encode($response);
    exit();
});

// $siteConfigs['ajaxEndpointSlug']
$app->get('/ajax', function () use ($siteConfigs) {
    
    $response = array();
    
    if (! isset($_SESSION["user"])) {
        $response["success"] = false;
        $response["message"] = "You are not logged in";
        $response["action"] = "logout";
    } else if (! (isset($_GET['action']) && in_array($_GET['action'], $siteConfigs['validAjaxActions']))) {
        $response["success"] = false;
        $response["message"] = "Your request is not a valid";
        $response["action"] = "";
    } else {
        
        $action = filter_var($_GET['action'], FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'searchUser':
                if (empty($_GET['dataString']) || filter_var($_GET['dataString'], FILTER_SANITIZE_STRING) === false || strlen($_GET['dataString']) < 3) {
                    $response["success"] = false;
                } else {
                    $functionResponse = searchUser($siteConfigs, $_GET['dataString']);
                    
                    if ($functionResponse) {
                        $response["success"] = true;
                        $response["suggestions"] = $functionResponse;
                    } else {
                        $response["success"] = false;
                        $response["message"] = "Sorry, an error occurred processing your request.";
                        $response["action"] = "";
                        $response["debug"] = $functionResponse;
                    }
                }
                break;
            default:
        }
    }
    
    echo json_encode($response);
    exit();
});

$app->run();
?>
