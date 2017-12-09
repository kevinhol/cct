<?php

use Httpful\Request;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
///*Bu fonksiyonu kullanarak kolayca curl yi kullanabilirsiniz.
//Kullanımı*/
//echo curl("http://gencbilgin.net/");

require "vendor/autoload.php";

$uri = "https://apps.collabservsvt1.swg.usma.ibm.com/api/bss/resource/subscriber";
$response = \Httpful\Request::get($uri)
        ->authenticateWith('kevinhol@ivthouse.com', 'pass0909')
        ->send();

$myfile = fopen("newfile.txt", "w");
fwrite($myfile, $response);
fclose($myfile);

exit;
?>
