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

/**
 *
 * @param mixed $input
 *            string or array to be sanitized
 * @return mixed sanitized input as output
 */
function sanitize_input($input)
{
    if (! is_array($input)) {
        return filter_var(trim($input), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    } else {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize_input($value);
        }
    }
    return $input;
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
    
    $user['userspace'] = "/userspace/__" . hash_hmac('sha1',  $user['email'], $siteConfigs['user_session_key']) . "/";
    
    setUserSessionVars($siteConfigs, $user);
    var_dump($user, $_SESSION);
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
    $url = $_SESSION['environment'] . $siteConfigs['userIdentitySlug'];
    
    $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
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
function getSubscriberList($siteConfigs, $_pageNumber = 1, $_pageSize = 15)
{
    $url = $_SESSION['environment'] . $siteConfigs['subscriberResourceSlug'] . "?_pageNumber=" . $_pageNumber . "&_pageSize=" . $_pageSize;
    
    $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
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

/**
 *
 * @param Array $siteConfigs
 * @param int $id
 *            The user id
 * @param boolean $suspend
 *            This dictates to suspend or unsuspend the user
 * @return boolean|\Httpful\Response
 */
function suspendUser($siteConfigs, $id, $suspend = true)
{
    if (! (isset($_SESSION['environment'], $_SESSION['user']) && ctype_digit($id) && $id > 0)) {
        return false;
    }
    
    $xop = ($suspend) ? 'suspendSubscriber' : 'unSuspendSubscriber';
    
    $url = $_SESSION['environment'] . $siteConfigs['subscriberResourceSlug'] . '/' . $id;

    $ret = [];
    
    // Set up CUrL
    $headers[] = 'Authorization: ' . $_SESSION['access_token_header'] ; 
    $headers[] = 'x-operation:' . $xop ;
    
    $fields = [
        "id" => $id ,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1); // return the response headers

    $response = curl_exec($ch);
    
    if ($response === false) {
        $return = ('Curl error: ' . curl_error($ch));
        curl_close($ch);
        return $return;
    }
    curl_close($ch);
    
    list($headers, $resp) = explode("\r\n\r\n", $response, 2);

    // https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=HTTP_status_codes_bss&content=apicontent
    // search for a HTTP/1.1 20X response header 
    $success = preg_grep($siteConfigs['http20Xheader'], explode("\n", $headers));

    // Finish CUrL
    $ret['success'] = count($success) ? true:false;  
    
    return $ret;
}

function restoreUser($siteConfigs, $id)
{
    if (! (isset($_SESSION['environment'], $_SESSION['user']) && ctype_digit($id) && $id > 0)) {
        return false;
    }
    
    $url = $_SESSION['environment'] . $siteConfigs['subscriberResourceSlug'] . '/' . $id;
    
    $ret = [];
    
    // Set up CUrL
    $headers[] = 'Authorization: ' . $_SESSION['access_token_header'] ;
    $headers[] = 'x-operation: restoreSubscriber' ;
    
    $fields = [
        "id" => $id ,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1); // return the response headers
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $return = ('Curl error: ' . curl_error($ch));
        curl_close($ch);
        return $return;
    }
    curl_close($ch);
    
    list($headers, $resp) = explode("\r\n\r\n", $response, 2);
    
    // https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=HTTP_status_codes_bss&content=apicontent
    // search for a HTTP/1.1 20X response header
    $success = preg_grep($siteConfigs['http20Xheader'], explode("\n", $headers));
    
    // Finish CUrL
    $ret['success'] = count($success) ? true:false;
    
    return $ret;
}

function deleteUser($siteConfigs, $id, $fullDelete = false)
{
    if (! (isset($_SESSION['environment'], $_SESSION['user']) && ctype_digit($id) && $id > 0)) {
        return false;
    }
    
    $fullDeleteParams = ($fullDelete) ? '': '?moveToSoftDelete=true' ;
    
    $url = $_SESSION['environment'] . $siteConfigs['subscriberResourceSlug'] . '/' . $id . $fullDeleteParams;
    
    $ret = [];
    
    // Set up CUrL
    $headers[] = 'Authorization: ' . $_SESSION['access_token_header'] ;
    
    $fields = [
        "id" => $id ,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    // curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1); // return the response headers
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $return = ('Curl error: ' . curl_error($ch));
        curl_close($ch);
        return $return;
    }
    curl_close($ch);
    
    list($headers, $resp) = explode("\r\n\r\n", $response, 2);
    
    // https://www-10.lotus.com/ldd/appdevwiki.nsf/xpAPIViewer.xsp?lookupName=API+Reference#action=openDocument&res_title=HTTP_status_codes_bss&content=apicontent
    // search for a HTTP/1.1 20X response header
    $success = preg_grep($siteConfigs['http20Xheader'], explode("\n", $headers));
    
    // Finish CUrL
    $ret['success'] = count($success) ? true:false;

    return $ret;
}

function searchUser($siteConfigs, $searhString, $searchOnlyNameAndEmail = true)
{
    if (! (isset($_SESSION['environment'], $_SESSION['user']) && ctype_alnum($searhString))) {
        return false;
    }
    
    $nameEmail = $searchOnlyNameAndEmail ? "true" : "false";
    
    $url = $_SESSION['environment'] . $siteConfigs['searchPeopleSlug'] . "?searchOnlyNameAndEmail=" . $nameEmail . "&query=" . $searhString;
    
    $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
    return json_decode($response, true);
}

function get_src_nonce_hash($siteConfigs){
    
    if(isset($_SESSION['src_nonce_hash']) && strlen($_SESSION['src_nonce_hash']) == $siteConfigs['src_hash_length'] ) {
        return $_SESSION['src_nonce_hash'] ;
    }
    $val = hash($siteConfigs['src_hash_algo'], time() . session_id() . $siteConfigs['src_hash_key'], false);
    
    $_SESSION['src_nonce_hash'] = $val;
    
    return $val;
}

function getCommunitiesData($siteConfigs, $pageNumber = 1, $pageSize = 15)
{
    $url = $_SESSION['environment'] . $siteConfigs['communitiesListSlug'] . "?page=" . $pageNumber . "&ps=" . $pageSize;
    
    $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
    return parseCommunityData($response);
}

function parseCommunityData($xml)
{
    // should be stored in main.ini ?
    $linkRelArr = [
        'logo',
        'member-list',
        'bookmarks',
        'remote-applications',
        'forum-topics',
        'invitations-list',
        'widgets',
        'pages',
        'subcommunities',
        'activitystream',
        'microblog',
        'community-broadcasts'
    ];
    
    // should be stored in main.ini ?
    $relBase = 'http://www.ibm.com/xmlns/prod/sn/';
    
    $parsedCommunities = array();
    
    // in reality this will be the API response and will be called as $dom->loadXML(API_RESPONSE)
    // $xmlSource = "C:\Users\IBM_ADMIN\Downloads\allcomm.xml";
    
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($xml);
    
    $domList = $dom->getElementsByTagNameNS('http://a9.com/-/spec/opensearch/1.1/', '*');
    
    $feed_total_results = (stripos($domList->item(0)->tagName, 'totalResults') !== false) ? $domList->item(0)->textContent : 0;
    
    $feed_start_index = (stripos($domList->item(1)->tagName, 'startIndex') !== false) ? $domList->item(1)->textContent : 0;
    
    $feed_items_per_page = (stripos($domList->item(2)->tagName, 'itemsPerPage') !== false) ? $domList->item(2)->textContent : 0;
    
    // loop through the entries to set pagination links
    $domList = $dom->getElementsByTagNameNS('http://www.w3.org/2005/Atom', 'link');
    
    $find = array("first", "last", "next", "prev");
    
    for($i = 0 ; $i < $domList->length; $i++){
        
        $item = $domList->item($i);
        $rel = $item->getAttribute('rel');
        
        if(in_array($rel, $find)){
            $pagination[$rel] = $item->getAttribute('href');
        }
    }
    
    // loop through the entries of communities
    $communities = $dom->getElementsByTagName('entry');
    
    for ($i = 0; $i < $communities->length; $i ++) {
        
        $community = $communities->item($i);
        $title = $community->getElementsByTagName("title")->item(0)->textContent;
        
        $communityUuid = $community->getElementsByTagName("communityUuid")->item(0)->textContent;
        
        $links = $community->getElementsByTagName("link");
        
        $commLinks = array();
        
        /* Clone the original array so we can pop the matched one off to improve processing speed */
        $linkRelArrCopy = array_merge(array(), $linkRelArr);
        
        foreach ($links as $link) {
            
            $rel = $link->getAttribute('rel');
            
            // get the html link for opening the community directly.
            if($rel == 'alternate'){
                $commLinks[$rel] = $link->getAttribute('href');
            }
            // otherwise parse the links to get the different app / atom links
            else{
                $replace = str_replace($relBase, '', $rel);
                
                if ($key = array_search($replace, $linkRelArrCopy) !== false) {
                    $commLinks[$replace] = $link->getAttribute('href');
                    unset($linkRelArrCopy[$replace]);
                }
            }
        }
        
        
        $memberCount = $community
        ->getElementsByTagName("membercount")
        ->item(0)->textContent;
        
        $type = $community
        ->getElementsByTagName("communityType")
        ->item(0)->textContent;
        
        $listWhenRestricted = $community
        ->getElementsByTagName("listWhenRestricted")
        ->item(0)->textContent;
        
        $orgId = $community
        ->getElementsByTagName("orgId")
        ->item(0)->textContent;
        
        $published = $community->getElementsByTagName("published")
        ->item(0)->textContent;
        
        $updated = $community->getElementsByTagName("updated")
        ->item(0)->textContent;
        
        $summary = $community
        ->getElementsByTagName("summary")
        ->item(0)->textContent;
        
        $authorXml = $community->getElementsByTagName("author");
        $author['name'] = $authorXml->item(0)
        ->getElementsByTagName("name")
        ->item(0)->textContent;
        $author['userid'] = $authorXml->item(0)
        ->getElementsByTagName("userid")
        ->item(0)->textContent;
        $author['userState'] = $authorXml->item(0)
        ->getElementsByTagName("userState")
        ->item(0)->textContent;
        $author['orgId'] = $authorXml->item(0)
        ->getElementsByTagName("orgId")
        ->item(0)->textContent;
        $author['isExternal'] = $authorXml->item(0)
        ->getElementsByTagName("isExternal")
        ->item(0)->textContent;
        
        $contributorXml = $community->getElementsByTagName("contributor");
        $contributor['name'] = $contributorXml->item(0)
        ->getElementsByTagName("name")
        ->item(0)->textContent;
        $contributor['userid'] = $contributorXml->item(0)
        ->getElementsByTagName("userid")
        ->item(0)->textContent;
        $contributor['userState'] = $contributorXml->item(0)
        ->getElementsByTagName("userState")
        ->item(0)->textContent;
        $contributor['orgId'] = $contributorXml->item(0)
        ->getElementsByTagName("orgId")
        ->item(0)->textContent;
        $contributor['isExternal'] = $contributorXml->item(0)
        ->getElementsByTagName("isExternal")
        ->item(0)->textContent;
        
        $memberEmailPrivileges = $community
        ->getElementsByTagName("memberEmailPrivileges")
        ->item(0)->textContent;
        
        $isExternal = $community->getElementsByTagName("isExternal")
        ->item(0)->textContent;
        
        
        $parsedCommunity = array();
        
        $parsedCommunity['title'] = $title;
        $parsedCommunity['communityUuid'] = $communityUuid;
        $parsedCommunity['links'] = $commLinks;
        $parsedCommunity['memberCount'] = $memberCount;
        $parsedCommunity['type'] = $type;
        $parsedCommunity['listWhenRestricted'] = $listWhenRestricted;
        $parsedCommunity['orgId'] = $orgId;
        $parsedCommunity['published'] = formatDate($published);
        $parsedCommunity['updated'] = formatDate($updated);
        $parsedCommunity['summary'] = $summary;
        $parsedCommunity['author'] = $author;
        $parsedCommunity['contributor'] = $contributor;
        $parsedCommunity['memberEmailPrivileges'] = $memberEmailPrivileges;
        $parsedCommunity['isExternal'] = $isExternal;
        
        array_push($parsedCommunities, $parsedCommunity);
    }
    
    $communityData = array();
    
    $communityData['totalCommunityCount'] = $feed_total_results;
    $communityData['startIndex'] = $feed_start_index;
    $communityData['itemsPerPage'] = $feed_items_per_page;
    $communityData['pagination'] = $pagination;
    $communityData['List'] = $parsedCommunities;
    
    return $communityData;
}

function formatDate($value)
{
    $arr = date_parse($value);
    $timestamp = strtotime($value);
    $am = true;
    $date = date('d/m/Y', $timestamp);
    
    if ($arr['hour'] >= 12) {
        $am = false;
    }
    
    if ($arr['minute'] < 9) {
        $arr['minute'] = '0' . $arr['minute'];
    }
    
    $am_pm = ($am) ? "am" : "pm";
    
    if ($date == date('d/m/Y')) {
        $dateStr = 'Today at ' . $arr['hour'] . ":" . $arr['minute'] . $am_pm;
    } else if ($date == date('d/m/Y', time() - (24 * 60 * 60))) {
        $dateStr = 'Yesterday at ' . $arr['hour'] . ":" . $arr['minute'] . $am_pm;
    } else {
        
        $currentYear = date('Y');
        $year = date('Y', $timestamp);
        $month = date('M', $timestamp);
        
        if ($currentYear != $year) {
            $dateStr = $month . " " . $arr['day'] . ", " . $year;
        } else {
            $dateStr = $month . " " . $arr['day'] . ", " . $year;
        }
    }
    return $dateStr;
}


function getPageNumFromCommUrl($url)
{
    $arr = parse_url($url);
    parse_str($arr['query'], $get_params);
    
    return $get_params['page'];
}



function getCommunityMembers($siteConfigs, $commUuid, $includeInvitees = true){
    
    if (! (isset($_SESSION['environment'], $_SESSION['user']) ) ) {
        return false;
    }
    
    $url = $_SESSION['environment'] . $siteConfigs['memberslistSlug'] . "?communityUuid=" . $commUuid;
    
    $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
    
    $ret['members'] = parseMemberData($response);
    
    if($includeInvitees){
        $url = $_SESSION['environment'] . $siteConfigs['inviteeslistSlug'] . "?communityUuid=" . $commUuid;
        
        $response = \Httpful\Request::get($url)->addHeader('Authorization', $_SESSION['access_token_header'])->send();
        
        $ret['invitees'] = parseMemberData($response);
    }
    return $ret;
    
}

function parseMemberData($xml){
    
    // should be stored in main.ini ?
    $relBase = 'http://www.ibm.com/xmlns/prod/sn/';
    
    $parsedMembers = array();
    
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($xml);
    
    $entries = $dom->getElementsByTagNameNS('http://www.w3.org/2005/Atom', 'entry');
    
    for ($i = 0; $i < $entries->length; $i++) {
        $member = array();
        
        $entry = $entries->item($i)->getElementsByTagName("contributor")->item(0);
        $member['name'] = $entry->getElementsByTagName("name")->item(0)->textContent;
        
        $member['email'] = $entry->getElementsByTagName("email")->item(0)->textContent;
        $member['userid'] = $entry->getElementsByTagName("userid")->item(0)->textContent;
        $member['userState'] = $entry->getElementsByTagName("userState")->item(0)->textContent;
        $member['isExternal'] = $entry->getElementsByTagName("isExternal")->item(0)->textContent;
        
        $role = $entries->item($i)->getElementsByTagName("role")->item(0)->textContent;
        $member['role'] = $role;
        
        $cats = $entries->item($i)->getElementsByTagName("category");
        
        for($j = 0; $j < $cats->length; $j++){
            $term = $cats[$j]->getAttribute('term');
            
            if($term == "business-owner"){
                $member["business_owner"] = true;
            }
        }
        array_push($parsedMembers, $member);
    }
    return $parsedMembers;
}















?>
