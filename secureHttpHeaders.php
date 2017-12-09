<?php 



// https://www.owasp.org/index.php/OWASP_Secure_Headers_Project#xfo_bp
// start output of secure headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('X-Permitted-Cross-Domain-Policies: none');
header('access-control-allow-origin: ' . $siteConfigs['website_www']) ;
header('content-type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Powered-By: magik'); // don't let users know what is generating the pages
header('Server: magik');  // Remove Server Identifier

/*
 * From https://content-security-policy.com/
 * Content-Security-Policy HTTP response header helps  reduce XSS risks on modern browsers by declaring what dynamic resources are allowed to load via a HTTP Header
 * 
 * more info here 
 * https://www.w3.org/TR/CSP2/#directive-script-src
 * https://developers.google.com/web/fundamentals/security/csp/
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/script-src
 * https://www.owasp.org/index.php/OWASP_Secure_Headers_Project#xfo_bp
 * 
 * As I am currently still developing and updating the source code creating file hashes is not time efficient so we use nonce for the allowed scripts 
 */

if(isset($_SESSION['environment'])){
    $callbackServers = $_SESSION['environment'];
}
else{
    $callbackServers = '';
    
    foreach ($siteConfigs['cloudEnvs'] as $server){
        $callbackServers =  $callbackServers . " " . $server; 
    }
}
$hash = get_src_nonce_hash($siteConfigs);

$csp = "Content-Security-Policy:
        default-src 'self';
        script-src 'self' 'nonce-$hash';
        style-src  'self';
        img-src 'self';
        object-src 'none';
        frame-src 'none';
        font-src 'self';
        connect-src 'self';
        form-action 'self'  $callbackServers ;
";
header(str_replace("
        ", " ", $csp));


?>