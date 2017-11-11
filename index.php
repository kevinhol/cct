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
    //var_dump($siteConfigs);
    $viewData = array(
        'title' => 'Login'
        , 'siteConfigs' => $siteConfigs
    );
    $view = ViewFactory::createTwigView("Login");
    $view->display($viewData);
});

$app->post('/login', function () use ($siteConfigs) {

    if (processLogin($siteConfigs, $_POST)) {
        header('Location: ' . $siteConfigs['website_url'] . '/Home');
        exit();
    } else {
        // TO DO ... handle failed login.. entry in DB for user and then 3 strikes lock account
//        header('Location: ' . $siteConfigs['website_url']) . "/login?msg=Sorry, we cannot match your Username and Password";
//        exit;
    }
});

$app->get('/Home', function () use ($siteConfigs) {

    if (!isset($_SESSION["user"])) {
        header('Location: ' . $siteConfigs['website_url']);
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
        header('Location: ' . $siteConfigs['website_url']);
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
        header('Location: ' . $siteConfigs['website_url']);
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
        header('Location: ' . $siteConfigs['website_url']);
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
