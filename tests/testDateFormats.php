<?php
$date = "2013-10-21T08:08:49.546Z";

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

// echo date('Y');
echo formatDate($date);


function getPageNumFromCommUrl($url)
{
    $arr = parse_url($url);
    
    parse_str($arr['query'], $get_params);
    
    return $get_params['_page'];
}

echo PHP_EOL;

$str = "https://apps.collabservintegration.com/communities/service/atom/communities/all?_ps=15&_page=1";

echo getPageNumFromCommUrl($str);


exit();
?>
