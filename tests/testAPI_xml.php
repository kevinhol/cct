<?php
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
    echo "domList LENGTH: " . $domList->length . PHP_EOL;
    
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


function parseMemberData($xml){
 
    
    // should be stored in main.ini ?
    $relBase = 'http://www.ibm.com/xmlns/prod/sn/';
    
    $parsedMembers = array();
    
    // in reality this will be the API response and will be called as $dom->loadXML(API_RESPONSE)
    // $xmlSource = "C:\Users\IBM_ADMIN\Downloads\allcomm.xml";
    
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    //$dom->loadXML($xml);
    $dom->load('C:\Users\IBM_ADMIN\Downloads\members.xml');
    
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
                $member['"business-owner"'] = true;
            }
        }
        
        array_push($parsedMembers, $member);
    }
    
    return $parsedMembers;
}

var_dump(parseMemberData(''));


?>
