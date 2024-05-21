<?php
$data = array("user"=>"sigep","pass"=>$pass);
$data_string = json_encode($data);
$request =  'http://sigeppre-wl12.sigma.gob.bo/rsseguridad/loginapi.html';
$session = curl_init($request);
curl_setopt($session, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($session, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
curl_setopt($session, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
);

$result = curl_exec($session);
curl_close($session);

$respuesta = json_decode($result);
?>
