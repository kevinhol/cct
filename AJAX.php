<?php
// Slim auto loader
require_once 'vendor/autoload.php';

// Project specific libs & functions
require_once 'libs/common.php';
require_once 'libs/functions.php';


// Load the site configs
$siteConfigs = getSiteConfigs();

// Project auto loader
require_once 'Autoloader.php';
