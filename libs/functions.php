<?php

function getSiteConfigs()
{
    $site_configs = array();
    try {
        $site_configs = parse_ini_file("./configs/main.ini");
    } catch (Exception $e) {
        echo "<p>Sorry the application is unavailable at this time.</br>Error reading config file.</p>";
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
function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);
    
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    
    return $subject;
}

function sanitize_output($buffer)
{
    $search = array(
        '/\>[^\S ]+/s', // strip whitespaces after tags, except space
        '/[^\S ]+\</s', // strip whitespaces before tags, except space
        '/(\s)+/s' // shorten multiple whitespace sequences
    );
    
    $replace = array(
        '>',
        '<',
        '\\1'
    );
    
    $buffer = preg_replace($search, $replace, $buffer);
    
    return $buffer;
}

function verifyAppID($siteConfigs, $loginPostArray)
{
    if (! (isset($loginPostArray['app-id']) && strlen($loginPostArray['app-id']) == 26 && preg_match('/app_[0-9]{8}_[0-9]{13}/', $loginPostArray['app-id']))) {
        error_log("User passed invalid App ID", 1, $siteConfigs['admin_email']);
        return false;
    }
    return true;
}

function verifyEnvSelection($siteConfigs, $loginPostArray)
{
    if (! in_array($loginPostArray['environment'], $siteConfigs['cloudEnvs'])) {
        error_log("User passed invalid environment parameter on login attempt", 1, $siteConfigs['admin_email']);
        return false;
    }
    return true;
}

function createUser($siteConfigs)
{
    $user = getUserIdentity($siteConfigs);
    
    if (! is_array($user) || empty($user)) {
        error_log("Failed to get user on login attempt to " . $_SESSION['environment'], 0);
        return false;
    }
    
    $rolesList = getUserRoles($siteConfigs, $user['email']);
    
    $user['roles'] = (! empty($rolesList) && $rolesList['List']) ? $rolesList['List'] : "";
    
    if (! in_array("AppDeveloper", $user['roles'])) {
        error_log("User " . $user['email'] . " does not have the required AppDeveloper role applied", 0);
        return false;
    }
    
    if (! in_array("CustomerAdministrator", $user['roles'])) {
        error_log("User " . $user['email'] . " does not have the required CustomerAdministrator role applied", 0);
        return false;
    }
    
    $user['userspace'] = "userspace/__" . hash_hmac('sha1', (time() . $user['email']), $siteConfigs['user_session_key']);
    
    setUserSessionVars($siteConfigs, $user);
    
    return true;
}

/*
 * This function is now redundant, but leaving here for future reference
 *
 * function processLogin($siteConfigs, $loginPostArray)
 * {
 * $email = $loginPostArray['email'];
 * $pass = $loginPostArray['password'];
 * $env = $loginPostArray['environment'];
 *
 * $result;
 * // check email input is valid
 * if (! (strlen($email) && filter_var($email, FILTER_VALIDATE_EMAIL))) {
 * error_log("Email '$pass' is detected as invalid on login attempt", 0);
 * return false;
 * }
 *
 * // check password input is valid
 * // https://www.ibm.com/support/knowledgecenter/en/SSL3JX/admin/bss/topics/password_considerations.html
 * if ((strlen($pass) < 8)) {
 * error_log("Pass '$pass' for user with email '$email' is < 8 chars", 0);
 * return false;
 * }
 * if (! preg_match('/[a-zA-Z]{4,}/', $pass)) {
 * error_log("Pass '$pass' for user with email '$email' does not contain min 4 chars", 0);
 * return false;
 * }
 * if (! preg_match('/\d/', $pass)) {
 * error_log("Pass '$pass' for user with email '$email' does not contain min 1 digit", 0);
 * return false;
 * }
 * if (preg_match('/\s/', $pass)) {
 * error_log("Pass '$pass' for user with email '$email' contains space character", 0);
 * return false;
 * }
 *
 * $containsThreeOrMoreCharRepeats = false;
 * foreach (count_chars($pass, 1) as $i => $val) {
 * if ($val >= 3) {
 * $containsThreeOrMoreCharRepeats = true;
 * }
 * }
 * if ($containsThreeOrMoreCharRepeats) {
 * error_log("Pass '$pass' for user with email '$email' contains 3 or more repeated characters", 0);
 * return false;
 * }
 * if ($email == $pass) {
 * error_log("Pass '$pass' for user with email '$email' is same as email", 0);
 * return false;
 * }
 *
 * if (! in_array($env, $siteConfigs['cloudEnvs'])) {
 * error_log("User $email passed invalid environment parameter on login attempt", 1, $siteConfigs['admin_email']);
 * return false;
 * }
 *
 * $user = getUserIdentity($siteConfigs);
 *
 * if (! is_array($user) || empty($user)) {
 * error_log("Failed to get user '$email' on login attempt to $env", 0);
 * return false;
 * }
 *
 * $rolesList = getUserRoles($siteConfigs, $email, $pass, $env, $email);
 *
 * $user['roles'] = (! empty($rolesList) && $rolesList['List']) ? $rolesList['List'] : "";
 *
 * if (! in_array("AppDeveloper", $user['roles'])) {
 * error_log("User '$email' does not have the required AppDeveloper role applied", 0);
 * return false;
 * }
 *
 * if (! in_array("CustomerAdministrator", $user['roles'])) {
 * error_log("User '$email' does not have the required CustomerAdministrator role applied", 0);
 * return false;
 * }
 *
 * $user['userspace'] = "userspace/__" . hash_hmac('sha1', (time() . $user['email']), $siteConfigs['user_session_key']);
 * $user['pass'] = $pass;
 * $user['env'] = $env;
 *
 * setUserSessionVars($siteConfigs, $user);
 *
 * return true;
 * }
 */
function getUserIdentity($siteConfigs)
{
    if (! isset($_SESSION['environment'])) {
        return null;
    }
    $response = \Httpful\Request::get($_SESSION['environment'] . $siteConfigs['userIdentitySlug'])->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
    return json_decode($response, true);
}

function getUserRoles($siteConfigs, $userEmail)
{
    if (! isset($_SESSION['environment'])) {
        return null;
    }
    
    $response = \Httpful\Request::post($_SESSION['environment'] . $siteConfigs['roleListSlug'] . str_replace("@", "%40", $userEmail))->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
    return json_decode($response, true);
}

/**
 * function to return users in the Org.
 * This can pass in the pagination parameters as described here:
 * https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=Pagination_support_for_HTTP_GET_calls_bss&content=apicontent
 *
 * @param type $siteConfigs
 *            = Web App configs
 * @param type $user
 *            = The authorized administrative user
 * @param type $_pageNumber
 * @param type $_pageSize
 * @return type
 */
function getSubscriberList($siteConfigs, $_pageNumber = 1, $_pageSize = 20)
{
    $url = $_SESSION['environment'] . $siteConfigs['subscriberListSlug'] . "_pageNumber=" . $_pageNumber . "&_pageSize=" . $_pageSize;
    echo "<br> $url </hr>";
    $response = \Httpful\Request::get($url )->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    var_dump($response); exit;
    return json_decode($response, true);
}

function setUserSessionVars($siteConfigs, $user)
{
    foreach ($user as $key => $value) {
        $_SESSION['user'][$key] = $value;
    }
}

/**
 * var boostrapAlertTypes = ['success','info','warning', 'danger' ];
 *
 * @param unknown $siteConfigs
 * @param string $message
 * @param string $msgType
 */
function logout($siteConfigs, $message = '', $msgType = 'info')
{
    foreach ($_SESSION as $key => $value) {
        unset($_SESSION[$key]);
    }
    
    header('Location: ' . $siteConfigs['website_www'] . getMsgToAppendToUrl($siteConfigs, $message, $msgType, 0));
    exit();
}

function getMsgToAppendToUrl($siteConfigs, $message = '', $msgType = 'info', $fixQueryString = 0)
{
    $msg = "";
    
    switch ($fixQueryString) {
        case 0:
            $msg = "?";
            break;
        case 1:
            $msg = "&";
            break;
        case 2:
            $msg = "";
            break;
        default:
            $msg = "?";
            break;
    }
    
    if (strlen($message)) {
        
        if (! in_array($msgType, $siteConfigs['boostrapAlertTypes'])) {
            // default to info alert type
            $msgType = 'info';
        }
        $msg .= "msgtype=" . $msgType . "&msg=" . $message;
    }
    return $msg;
}

function getAccessTokenAuthorizationHeader($siteConfigs)
{
    if (! isset($_SESSION['OAuthApiTokens'])) {
        // we should log an error here
        echo "<p>OAuthApiTokens not set in getAccessTokenHeader";
        return false;
    }
    
    $tokensArray = $_SESSION['OAuthApiTokens'];
    $nowtime = round(microtime(true) * 1000); // time in milliseconds because $tokensArray['issued_on'] is in milliseconds
    
    if ($nowtime > ($tokensArray['issued_on'] + $tokensArray['expires_in'])) {
        // request new access token
        renewAccessTokens($siteConfigs);
    }
    $authHeader = $tokensArray['token_type'] . " " . $tokensArray["access_token"];
    
    return $authHeader;
}

function renewAccessTokens($siteConfigs)
{
    if (! isset($_SESSION['OAuthApiTokens'])) {
        // we should log an error here
        echo "<p>OAuthApiTokens not set in renewAccessTokens";
        return false;
    }
    
    try {
        // build callback url for server-to-server handshake to gather access tokens
        $Oauth_NewAccessTokens_url = $_SESSION['environment'] . $siteConfigs['oAuthRequestNewTokenSlug'] . $_SESSION['OAuthApiTokens']["refresh_token"] . "&client_id=" . $_SESSION['client_id'] . "&client_secret=" . $_SESSION["client_secret"];
        echo "</hr> </br> Oauth_NewAccessTokens_url: " . $Oauth_NewAccessTokens_url;
        
        $getTokensRequest = \Httpful\Request::get($Oauth_NewAccessTokens_url)->send();
        
        parse_str($getTokensRequest, $tokensResponse);
        
        // We don't document min length as IBM can change that based on any cryptographic algorithm choice. So we only test for token not exceeding the max.
        // https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=Step_3_Exchange_authorization_code_for_access_and_refresh_tokens_sbt&content=apicontent
        if (isset($tokensResponse["access_token"]) && isset($tokensResponse["refresh_token"]) && isset($tokensResponse["issued_on"]) && isset($tokensResponse["expires_in"]) && isset($tokensResponse["token_type"]) && strlen($tokensResponse["access_token"]) <= 256 && strlen($tokensResponse["refresh_token"]) <= 256) {
            $_SESSION['OAuthApiTokens'] = $tokensResponse; // overwrite the previous session array
            $_SESSION['access_token_header'] = getAccessTokenAuthorizationHeader($siteConfigs);
            
            return true;
        } else {
            // log error and handle function response
            echo "<p>we ended up here ? </p>";
            var_dump($tokensResponse);
            echo "</hr>";
            exit();
            return false;
        }
    } catch (Exception $e) {
        $function_response['error'] = "Exception error getting content from curl request in renewAccessTokens()";
        var_dump($e);
        exit();
        return false;
    }
}

function setApiTokens($siteConfigs, $postArr)
{
    $function_response;
    
    if (isset($postArr['client_secret']) && ctype_alnum($postArr['client_secret']) && strlen($postArr['client_secret']) <= 256) {
        
        try {
            // build callback url for server-to-server handshake dance to gather access tokens
            $Oauth_url = $_SESSION['environment'] . $siteConfigs['oAuthTokenSlug'] . "&callback_uri=" . $siteConfigs['website_www'] . $siteConfigs['callBackSlug'] . "&client_id=" . $_SESSION['client_id'] . "&client_secret=" . $postArr["client_secret"] . "&code=" . $_SESSION['oauth_authorization_code'];
            
            $getTokensRequest = \Httpful\Request::get($Oauth_url)->send();
            
            // user entered incorrect secret
            if ($getTokensRequest == "oauth_invalid_clientsecret") {
                $function_response['error'] = "Invalid client secret";
                $function_response['go_back'] = true;
                
                return $function_response;
            }
            
            parse_str($getTokensRequest, $tokensResponse);
            
            if (isset($tokensResponse["access_token"]) && isset($tokensResponse["refresh_token"]) && isset($tokensResponse["issued_on"]) && isset($tokensResponse["expires_in"]) && isset($tokensResponse["token_type"]) && strlen($tokensResponse["access_token"]) <= 256 && strlen($tokensResponse["refresh_token"]) <= 256) {
                // request has returned valid tokens
                $_SESSION['OAuthApiTokens'] = $tokensResponse;
                $_SESSION['access_token_header'] = getAccessTokenAuthorizationHeader($siteConfigs);
                $_SESSION["client_secret"] = $postArr['client_secret'];
                // unset this now as not used anymore
                unset($_SESSION['oauth_authorization_code']);
                
                $function_response['success'] = true;
            } else {
                $function_response['error'] = "Error with tokens content from curl request";
                var_dump($function_response['error'], $tokensResponse);
                exit();
            }
        } catch (Exception $e) {
            $function_response['error'] = "Exception error verifying your client secret";
            $function_response['go_back'] = true;
            
            return $function_response;
        }
    } else {
        $function_response['error'] = "Invalid or empty Oauth client secret provided";
        $function_response['go_back'] = true;
        
        return $function_response;
    }
    return $function_response;
}
?>
