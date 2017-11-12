<?php

function getSiteConfigs() {
    $site_configs = array();
    try {
        $site_configs = parse_ini_file("./configs/main.ini");
    } catch (Exception $e) {
        echo "Error reading config file";
        die();
    }
    return $site_configs;
}

/**
 * Replace the last string occurance of a needle in a haystack
 * 
 * @param type $search
 * @param type $replace
 * @param type $subject
 * @return type
 */
function str_lreplace($search, $replace, $subject) {
    $pos = strrpos($subject, $search);

    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}

function sanitize_output($buffer) {

    $search = array(
        '/\>[^\S ]+/s', // strip whitespaces after tags, except space
        '/[^\S ]+\</s', // strip whitespaces before tags, except space
        '/(\s)+/s'       // shorten multiple whitespace sequences
    );

    $replace = array(
        '>',
        '<',
        '\\1'
    );

    $buffer = preg_replace($search, $replace, $buffer);

    return $buffer;
}

function processOauthLogin($siteConfigs, $loginPostArray){
    
    if (!in_array($loginPostArray['environment'], $siteConfigs['cloudEnvs'])) {
        error_log("User passed invalid environment parameter on login attempt", 1, $siteConfigs['admin_email']);
        return false;
    }

    if(! ( isset($loginPostArray['app-id']) && strlen($loginPostArray['app-id']) == 26  && preg_match('/app_[0-9]{8}_[0-9]{13}/', $loginPostArray['app-id']) ) ){
        error_log("User passed invalid App ID", 1, $siteConfigs['admin_email']);
        return false;
    }
    
    $_SESSION['client_id']   = $loginPostArray['app-id'];
    $_SESSION['environment'] = $loginPostArray['environment'];
    return true;
}


function processLogin($siteConfigs, $loginPostArray) {


    $email = $loginPostArray['email'];
    $pass = $loginPostArray['password'];
    $env = $loginPostArray['environment'];

    $result;
    //check email input is valid
    if (!(strlen($email) && filter_var($email, FILTER_VALIDATE_EMAIL))) {
        error_log("Email '$pass' is detected as invalid on login attempt", 0);
        return false;
    }

    // check password input  is valid
    // https://www.ibm.com/support/knowledgecenter/en/SSL3JX/admin/bss/topics/password_considerations.html
    if ((strlen($pass) < 8)) {
        error_log("Pass '$pass' for user with email '$email' is < 8 chars", 0);
        return false;
    }
    if (!preg_match('/[a-zA-Z]{4,}/', $pass)) {
        error_log("Pass '$pass' for user with email '$email' does not contain min 4 chars", 0);
        return false;
    }
    if (!preg_match('/\d/', $pass)) {
        error_log("Pass '$pass' for user with email '$email' does not contain min 1 digit", 0);
        return false;
    }
    if (preg_match('/\s/', $pass)) {
        error_log("Pass '$pass' for user with email '$email' contains space character", 0);
        return false;
    }

    $containsThreeOrMoreCharRepeats = false;
    foreach (count_chars($pass, 1) as $i => $val) {
        if ($val >= 3) {
            $containsThreeOrMoreCharRepeats = true;
        }
    }
    if ($containsThreeOrMoreCharRepeats) {
        error_log("Pass '$pass' for user with email '$email' contains 3 or more repeated characters", 0);
        return false;
    }
    if ($email == $pass) {
        error_log("Pass '$pass' for user with email '$email' is same as email", 0);
        return false;
    }

    if (!in_array($env, $siteConfigs['cloudEnvs'])) {
        error_log("User $email passed invalid environment parameter on login attempt", 1, $siteConfigs['admin_email']);
        return false;
    }



    $user = getUserIdentity($siteConfigs, $email, $pass, $env);


    if (!is_array($user) || empty($user)) {
        error_log("Failed to get user '$email' on login attempt to $env", 0);
        return false;
    }

    $rolesList = getUserRoles($siteConfigs, $email, $pass, $env, $email);
    
    $user['roles'] = (!empty($rolesList) && $rolesList['List']) ? $rolesList['List']: "" ;
    
    if (! in_array("AppDeveloper", $user['roles'])){
        error_log("User '$email' does not have the required AppDeveloper role applied", 0);
        return false;
    }
    
    if (! in_array("CustomerAdministrator", $user['roles'])){
        error_log("User '$email' does not have the required CustomerAdministrator role applied", 0);
        return false;
    }
    
    $user['userspace'] = "userspace/__" . hash_hmac('sha1', ( time() . $user['email']), $siteConfigs['user_session_key']);
    $user['pass'] = $pass;
    $user['env'] = $env;

    setUserSessionVars($siteConfigs, $user);

    return true;
}

function getUserIdentity($siteConfigs, $email, $pass, $env) {

    $response = \Httpful\Request::get($env . $siteConfigs['userIdentitySlug'])
            ->authenticateWith($email, $pass)
            ->send();

    return json_decode($response, true);
}

function getUserRoles($siteConfigs, $email, $pass, $env, $userEmail) {
    $response = \Httpful\Request::post($env . $siteConfigs['roleListSlug'] . str_replace("@", "%40", $userEmail) )
            ->authenticateWith($email, $pass)
            ->send();

    return json_decode($response, true);
}

/**
 * function to return users in the Org. This can pass in the pagination parameters as described here: 
 * https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=Pagination_support_for_HTTP_GET_calls_bss&content=apicontent
 * 
 * @param type $siteConfigs = Web App configs
 * @param type $user        = The authorized administrative user
 * @param type $_pageNumber 
 * @param type $_pageSize
 * @return type
 */
function getSubscriberList($siteConfigs, $user, $_pageNumber = 1, $_pageSize = 20) {
    
    $response = \Httpful\Request::get($user['env'] . $siteConfigs['subscriberListSlug'] ."_pageNumber=" . $_pageNumber . "&_pageSize=" & $_pageSize)
            ->authenticateWith($user['email'], $user['pass'])
            ->send();

    return json_decode($response, true);
}

function setUserSessionVars($siteconfigs, $user) {
    foreach ($user as $key => $value) {
        $_SESSION['user'][$key] = $value;
    }
}

function logout($siteConfigs) {

    session_destroy();
    header('Location: ' . $siteConfigs['website_www'] . '/');
    exit();
}

?>
