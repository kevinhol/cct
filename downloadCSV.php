<?php

// Project specific libs & functions
require_once 'libs/functions.php';

// Load the site configs
$siteConfigs = getSiteConfigs();

sanitize_input($_REQUEST);

// set env for test/debug on localhost
$debugMode = (substr($_SERVER['SERVER_NAME'], 0, strlen("localhost")) === "localhost") ? true : false;
$response = array();

if (! isset($_SESSION["user"])) {
    logout($siteConfigs, "You are not logged in yet", $siteConfigs['boostrapAlertTypes'][2]);
    exit();
} else if (! (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $siteConfigs['validCSVdownloadActions']))) {
    $response['success'] = false;
    $response['message'] = "Invalid or empty action provided";
    echo json_encode($response);
    exit();
} else {
    switch ($_REQUEST['action']) {
        case 'exportCommMembers':
            if (preg_match($siteConfigs['commUuidRegex'], $_REQUEST['commUuid'])) {
                
                $fields = (isset($_REQUEST['fields']) && strlen($_REQUEST['fields'])) ? explode(",", $_REQUEST['fields']) : "";
                exportCommMembers($siteConfigs, $_REQUEST['commUuid'], $fields);
            } else {}
    }
}

/* 
 * https://www.codexworld.com/export-data-to-csv-file-using-php-mysql/
 */
function exportCommMembers($siteConfigs, $commUuid, $userDefFields)
{
    $delimiter = ",";
    $f = fopen('php://memory', 'w');
    
    fputcsv($f, array("MEMBERS"), $delimiter);
    fputcsv($f, array(""), $delimiter);
    // set column headers

    $columnFields = array(
        'email',
        'userid',
        'userState',
        'isExternal'
    );
    
    
    $columnFields = array_uintersect($columnFields, $userDefFields, "strcasecmp");
    
    // add standard fields to start of the array
    array_unshift($columnFields, "name");
    array_push($columnFields, "role");
    
    fputcsv($f, $columnFields, $delimiter);
    
    $data = getCommunityMembers($siteConfigs, $commUuid);
    
    
    foreach ($data['members'] as $member) {
        
        if(!in_array($member['role'], $userDefFields)){
            continue;
        }
        
        $member['role'] = (isset($member['business_owner']) && $member['business_owner']) ? "Business Owner" : $member['role'];
        
        $lineData = array();
        
        foreach ($columnFields as $field){
                array_push($lineData, $member[$field]);
        }
        
        fputcsv($f, $lineData, $delimiter);
    }
    
    if(count($data['invitees'])){
        fputcsv($f, array("INVITEES"), $delimiter);
        fputcsv($f, array(""), $delimiter);
    
        foreach ($data['invitees'] as $invitee) {
            
            if(!in_array($invitee['role'], $userDefFields)){
                continue;
            }
            $lineData = array();
            
            foreach ($columnFields as $field){
                array_push($lineData, $member[$field]);
            }
            
            fputcsv($f, $lineData, $delimiter);
        }
    }
    // move back to beginning of file
    fseek($f, 0);
    
    $filename = "Community Members on " . date('Y-m-d') . " for " . $commUuid;
    
    require_once 'secureHttpHeaders.php';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    fpassthru($f);
}

exit();
;
