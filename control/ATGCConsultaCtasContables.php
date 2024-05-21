<?php
ini_set('display_errors','1');
require_once __DIR__ . '/../vendor/autoload.php';

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\JSONFlattenedSerializer;
/*************************************************
 *
 *
 * OBTENER ACCESS TOKEN
 *
 **************************************************/


$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://sigep.sigma.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=FEA520426600:Wk5yBGCh5TeT8jUG5lPkwIT25Jmlwav5XqtxhCrmgr5Yc0iaAMPZgLILZZPC7mjxk5tUgVusBs0RXlSDkIuWq2qNat2KsUM3E4q7",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    //CURLOPT_MAXREDIRS => 10,
    //CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

/*if (isset($_POST['action'])) {

    if ($_POST['action']=='register') {
        $nroPreventivo = $_POST['nroPreventivo'];
        $nroCompromiso = $_POST['nroCompromiso'];
        $nroDevengado= $_POST['nroDevengado'];
        $nroPago= $_POST['nroPago'];
        $fecha = "13/05/2019";

    }
}*/





if ($err) {
    echo "cURL Error #:" . $err;
} else {
    /*************************************************
     *
     *
     * HACER PETICION GET
     *
     **************************************************/
    $token_response = json_decode($response);
    //var_dump($token_response);exit;
    $access_token = $token_response->{'access_token'};


    /*****************************************************************************/
    $jsonConverter = new StandardConverter();
    $param_p = array("gestion" => "2022", "perfil" => "915");
    $param_p = $jsonConverter->encode($param_p);
    $curl_p = curl_init();

    $curl_array_p = array(
        CURLOPT_URL => 'https://sigep.sigma.gob.bo/rsbeneficiarios/api/cambiaperfil',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => array(
            "Authorization: bearer " . $access_token,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ),
        CURLOPT_POSTFIELDS => $param_p
    );

    curl_setopt_array($curl_p,$curl_array_p);
    $response_p = curl_exec($curl_p);
    $err_p = curl_error($curl_p);
    $http_code_p = curl_getinfo( $curl_p, CURLINFO_HTTP_CODE );
    curl_close($curl_p);

    //var_dump($response_p);exit;
    /*****************************************************************************/

    $curl = curl_init();
    curl_setopt_array($curl, array(
        //CURLOPT_URL => "http://sigeppre-wl12.sigma.gob.bo/rsbeneficiarios/api/v1/beneficiarios/natural?numeroDocumento=&primerApellido=ZABALA&segundoApellido=MITA&nombres=ROMER&fechaNacimiento=19-03-1984",
        //CURLOPT_URL => "https://sigep.sigma.gob.bo/rclasificadores/api/v1/libreta?fecha_consulta=".'11/09/2019'."&id_entidad=494",
        //CURLOPT_URL => "https://sigep.sigma.gob.bo/rsclasificadores/api/v1/cuentasbancarias/cuentabancaria?idEntidad=494&fecha_consulta='04-07-2022'",
        CURLOPT_URL => "https://sigep.sigma.gob.bo/rsclasificadores/api/v1/cuentascontables/lista?ente=326&fecha=04-07-2022",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: bearer " . $access_token,
            "Cache-Control: no-cache",
            "Postman-Token: 011d15eb-f4ff-48db-85a6-1b380958342b"
        ),
    ));

    $response = curl_exec($curl);//var_dump('$response',curl_getinfo($curl));exit;
    $err = curl_error($curl);

    curl_close($curl);
    //var_dump($response);exit;
    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        /*************************************************
         *
         *
         * DESSERIALIZAR MENSAJE
         *
         **************************************************/
        // The algorithm manager with the HS256 algorithm.
        $algorithmManager = AlgorithmManager::create([
            new RS512()
        ]);

        // We instantiate our JWS Verifier.
        $jwsVerifier = new JWSVerifier(
            $algorithmManager
        );
        $jwk = JWKFactory::createFromKeyFile(
            '../ssu.key', // The filename
            null
        );

        // The JSON Converter.
        $jsonConverter = new StandardConverter();
        //var_dump('respuesta:', $response);

        $token = $response;

        $usus=json_decode($token, true);
        //var_dump('BENEFICIARIO:',$usus['data']['beneficiario']);

        echo '<pre>' . var_export(json_decode($token), true) . '</pre>';

        $serializer = new JSONFlattenedSerializer($jsonConverter);

        // We try to load the token.
        $jws = $serializer->unserialize($token);
        echo $jws;
        echo '<pre>' . var_export(json_decode($jws->getPayload()), true) . '</pre>';
        //var_dump($jws);
        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }
}