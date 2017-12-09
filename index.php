<?php

// Slim auto loader
require 'vendor/autoload.php';

// Project specific libs & functions
require_once 'libs/functions.php';

// Load the site configs
$siteConfigs = getSiteConfigs();

// Project auto loader
require_once 'Autoloader.php';

// Do server output compression

if (! isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
    ob_start(sanitize_output);
} elseif (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') == false) {
    if (strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') == false) {
        ob_start("ob_gzhandler");
    } elseif (! ob_start("ob_gzhandler")) {
        ob_start("sanitize_output");
    }
} elseif (! ob_start("ob_gzhandler")) {
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
    $siteConfigs["ajaxEndpointSlug"] = $siteConfigs["application_project_base_folder"] . $siteConfigs["ajaxEndpointSlug"];
    
    $_SESSION['user']['subscriberid'] = 1;
    $_SESSION['user']['email'] = 'test@test.com';
} else {
    // hide all errors now;
    // error_reporting(0);
    // ini_set("display_errors", 0);
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
        
        // we check the referrer before redirection back to the location.
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
        'subscribers' => $subscriberList,
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
    
    // setup for initial page load
    if (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0) {
        $page = $_GET['page'];
    } else {
        $page = $_GET['page'] = 1;
    }
    
    $communities = getCommunitiesData($siteConfigs, $page);
    // var_dump($communities);
    $communitiesList = empty($communities['List']) ? array() : $communities['List'];
    
    // set up for pagination
    $prev = (isset($communities['pagination']['prev'])) ? getPageNumFromCommUrl($communities['pagination']['prev']) : 0;
    $next = (isset($communities['pagination']['next'])) ? getPageNumFromCommUrl($communities['pagination']['next']) : 1;
    
    // $last = getPageNumFromCommUrl($communities['pagination']['last']);
    
    $viewData = array(
        'title' => 'Communities',
        'siteConfigs' => $siteConfigs,
        'communitiesList' => $communitiesList,
        'totalCount' => $communities['totalCommunityCount'],
        'prev' => $prev,
        'next' => $next
    );
    $view = ViewFactory::createTwigView("Communities");
    $view->display($viewData);
});

$app->get('/Community/Members/:Uuid', function ($Uuid) use ($siteConfigs) {
    
    if (! isset($_SESSION['environment'], $_SESSION['user'])) {
        logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
    }
    
    if (! empty($Uuid) && filter_var($Uuid, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW) !== false && preg_match($siteConfigs['commUuidRegex'], $Uuid)) {
        
        $data = getCommunityMembers($siteConfigs, $Uuid);
        
        $viewData = array(
            'title' => 'Members for ',
            'siteConfigs' => $siteConfigs,
            "members" => $data['members'],
            "invitees" => $data['invitees'],
            'commUuid' => $Uuid,
            'prev' => 0,
            'next' => 2 // test temp vars
        );
        
        $view = ViewFactory::createTwigView("CommunityMembers");
        $view->display($viewData);
    } else
        echo "NO LOAD;";
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

$app->get('/downloadCSV', function () use ($siteConfigs) {
        
    require_once 'downloadCSV.php';
    exit();
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

if ($debugMode) {
    $app->get('/Playground', function () use ($siteConfigs) {
        
        $subscribers = array();
        $subscribers['List'] = array();
        
        for ($i = 0; $i < 10; $i ++) {
            
            $state = ($i % 2 == 0) ? "ACTIVE" : "PENDING";
            if ($i == 3) {
                $state = "REMOVE_PENDING";
            }
            if ($i == 8) {
                $state = "SOFT_DELETED";
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
}
/*
 * Ajax endpoint $siteConfigs['ajaxEndpointSlug']
 */
$app->post($siteConfigs['ajaxEndpointSlug'], function () use ($siteConfigs) {
    
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
        $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
        
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
            case 'deleteUser':
                
                $fullDelete = (isset($_REQUEST['soft']) && $_REQUEST['fullDelete'] == true) ? true : false;
                
                $functionResponse = deleteUser($siteConfigs, $_POST['subscriberId'], $fullDelete);
                
                if ($functionResponse["success"] == true) {
                    $response["success"] = true;
                } else {
                    $response["success"] = false;
                    $response["message"] = "Sorry, an error occurred processing your request.";
                    $response["action"] = "";
                    $response["debug"] = $functionResponse;
                }
                break;
            case 'restoreUser':
                
                $softDelete = (isset($_REQUEST['soft']) && $_REQUEST['soft'] == true) ? true : false;
                
                $functionResponse = restoreUser($siteConfigs, $_POST['subscriberId']);
                
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
$app->get($siteConfigs['ajaxEndpointSlug'], function () use ($siteConfigs) {
    
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
        
        switch ($_GET['action']) {
            case 'searchUser':
                if (empty($_GET['dataString']) || filter_var($_GET['dataString'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW) === false || strlen($_GET['dataString']) < 3) {
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
