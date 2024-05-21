
<?php
//var_dump("authorization_code:", $_POST['authorization_code']);

$code = $_POST['authorization_code'];//var_dump('$code:', $code);exit;

define("CALLBACK_URL", "http://172.17.58.62/kerp/sis_sigep/control/ActionInitRefreshToken.php");
define("AUTH_URL", "https://sigep.gob.bo/rsseguridad/login.html");
define("ACCESS_TOKEN_URL", "https://sigep.gob.bo/rsseguridad/apiseg/token");
define("CLIENT_ID", "0");
define("CLIENT_SECRET", "0");
/*define("CALLBACK_URL", "http://192.168.11.130/kerp/sis_sigep/control/ActionInitRefreshToken.php");
define("AUTH_URL", "http://sigeppre-wl12.sigma.gob.bo/rsseguridad/login.html");
define("ACCESS_TOKEN_URL", "http://sigeppre-wl12.sigma.gob.bo/rsseguridad/apiseg/token");
define("CLIENT_ID", "0");
define("CLIENT_SECRET", "0");*/

////iFrame 4 URL to call
$url = AUTH_URL."?"
    ."response_type=code"
    ."&client_id=". urlencode(CLIENT_ID)
    ."&scope=". urlencode(SCOPE)
    ."&redirect_uri=". urlencode(CALLBACK_URL)
;

$curl = curl_init();

$params = array(
    CURLOPT_URL =>  ACCESS_TOKEN_URL."?"
        ."&grant_type=authorization_code"
        ."&client_id=". CLIENT_ID
        ."&redirect_uri=". CALLBACK_URL
        ."&client_secret=". CLIENT_SECRET
        ."&code=".$code,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    //CURLOPT_NOBODY => false,
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded",
        "accept: *",
        "accept-encoding: gzip, deflate",
    ),
);

curl_setopt_array($curl, $params);

$response = curl_exec($curl);
//var_dump('despues de curl:', $response);exit;
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error SIGEP: " . $err;
} else {
    $response = json_decode($response, true);
    if(array_key_exists("error", $response)) echo $response["error_description"];
}


//var_dump('result:', $response['expires_in']);
/*var_dump(array(
        "access_token"=>$_POST['access_token'],
        "refresh_token"=>$_POST['refresh_token'],
        "expires_in"=>$_POST['expires_in'],

    ));*/
include_once(dirname(__FILE__).'/../../lib/rest/PxpRestClient2.php');

//Generamos el documento con REST


$pxpRestClient = PxpRestClient2::connect('172.17.58.62', 'kerp/pxp/lib/rest/')->setCredentialsPxp('admin','admin');

if(!empty($response['access_token'])||!empty($response['refresh_token'])|| !empty($response['expires_in'])) {
    echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: green; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px; align-content: center;'>";
    var_dump(array(
        "access_token"=>$response['access_token'],
        "refresh_token"=>$response['refresh_token'],
        "expires_in"=>$response['expires_in'],
    ));
    echo "</pre>\n";
    $pxpRestClient->doPost('sigep/UserMapping/initRefreshToken',
        array(
            "access_token" => $response['access_token'],
            "refresh_token" => $response['refresh_token'],
            "expires_in" => $response['expires_in'],
            "authorization_code" => $code,
        ));

    //header('Location: http://10.150.0.91/kerp_romel/sis_seguridad/vista/_adm/index.php#main-tabs:RECBTAX');
    //echo "closeCurrentWindow()";
}else{
    echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: red; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 20px; line-height: 13px; align-content: center;'>";
    var_dump('ERROR SIGEP, NO EXISTE UN Authorization_code: ', $code);
    echo "</pre>\n";
}
//header("content-type: text/javascript; charset=UTF-8");
?>

<script>
    console.log('valores', this)

    function funcion(){
        //window.close();
        alert('<?php echo 'work it!';?>');
    }
</script>
