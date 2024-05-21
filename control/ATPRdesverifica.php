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


/*****************************************************
 *
 *
 * SERIALIZAR MENSAJE
 *
 *
 * ********************************************************/
// The algorithm manager with the HS256 algorithm.
$algorithmManager = AlgorithmManager::create([
    new RS512(),
]);


$jwk = JWKFactory::createFromKeyFile(
    '../ssu.key', // The filename
    null,                   // Secret if the key is encrypted
    [
        'use' => 'sig',         // Additional parameters
        'kid' => 'ssuws'
    ]
);

$jsonConverter = new StandardConverter();

// We instantiate our JWS Builder.
$jwsBuilder = new JWSBuilder(
    $jsonConverter,
    $algorithmManager
);

// The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
$payload = $jsonConverter->encode([
    'gestion' => 2023,
    'idEntidad' => 494,
    'idDa' => 15,
    'nroPreventivo' => 0,
    'nroCompromiso' => 0,
    'nroDevengado' => 2091,
    'nroPago' => 0,
    'nroSecuencia' => 0
]);

$jws = $jwsBuilder
    ->create()                               // We want to create a new JWS
    ->withPayload($payload)                  // We set the payload
    ->addSignature($jwk, ['alg' => 'RS512'],['kid' => 'ssuws']) // We add a signature with a simple protected header
    ->build();


$serializer = new JSONFlattenedSerializer($jsonConverter); // The serializer

$token = $serializer->serialize($jws, 0); // We serialize the signature at index 0 (we only have one signature).


/*************************************************
 *
 *
 * OBTENER ACCESS TOKEN
 *
 **************************************************/


$curl = curl_init();

curl_setopt_array($curl, array(

    CURLOPT_URL => "https://sigep.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=RBC523208200:PdInyzK9WfrrGd4eL80tPDGfNO7lLZUmLv70ayz1fgeOwf88J2pXD5YTFxH9BKufr6cFmXhJDLEeIvxMvhPC3EJkNbjSy2BRtEPe",
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

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    /*************************************************
     *
     *
     * HACER PETICION POST
     *
     **************************************************/
    $token_response = json_decode($response);
    $access_token = $token_response->{'access_token'};


    /** ************************CAMBIO PERFIL*********************** **/
    $jsonConverterP = new StandardConverter();

    $param_p = array("gestion" => "2023", "perfil" => "104");
    $param_p = $jsonConverterP->encode($param_p);
    $curl_p = curl_init();

    $curl_array_p = array(
        CURLOPT_URL => 'https://sigep.gob.bo/rsbeneficiarios/api/cambiaperfil',
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

    curl_setopt_array($curl_p, $curl_array_p);
    $response_p = curl_exec($curl_p);
    $err_p = curl_error($curl_p);
    $http_code_p = curl_getinfo($curl_p, CURLINFO_HTTP_CODE);//var_dump(curl_getinfo($curl_p));exit;
    curl_close($curl_p);

    /** ************************CAMBIO PERFIL*********************** **/

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://sigep.gob.bo/ejecucion-gasto/api/v1/egadocumentos/desverifica",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $token,
        CURLOPT_HTTPHEADER => array(
            "authorization: bearer " . $access_token,
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: a3949f68-6846-29c1-0219-282f88c61cbb"
        ),
    ));

    $response = curl_exec($curl);

    $err = curl_error($curl);

    curl_close($curl);

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
        //echo $response;
        $token = $response;
        $serializer = new JSONFlattenedSerializer($jsonConverter);

        // We try to load the token.
        $jws = $serializer->unserialize($token);
        echo '<pre>' . var_export(json_decode($jws->getPayload()), true) . '</pre>';
var_dump($token);
        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }
}