<?php

use Httpful\Request;

//// create a new cURL resource
//function curl($url,$posts=""){
//$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_HEADER, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)");
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
//curl_setopt($ch, CURLOPT_POST, $posts ? 0 :1); 
//curl_setopt($ch, CURLOPT_POSTFIELDS,$posts);
//$icerik = curl_exec($ch);
//return $icerik;
//curl_close($ch);
//} 

require "vendor/autoload.php";

$uri = "https://apps.collabservsvt1.swg.usma.ibm.com/api/bss/resource/subscriber";
$response = \Httpful\Request::get($uri)
        ->authenticateWith('XXXXXXXX', 'XXXXXXXXXX')
        ->send();

$myfile = fopen("newfile.txt", "w");
fwrite($myfile, $response);
fclose($myfile);

exit;
?>
