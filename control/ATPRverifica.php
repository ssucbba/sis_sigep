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
if (isset($_POST['action'])) {

    if ($_POST['action']=='register') {
        $nroPreventivo = $_POST['nroPreventivo'];
        $nroCompromiso = $_POST['nroCompromiso'];
        $nroDevengado= $_POST['nroDevengado'];
        $nroPago= $_POST['nroPago'];
        $nroSecuencia= $_POST['nroSecuencia'];

    }
}
// The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
$payload = $jsonConverter->encode([
    'gestion' => 2020,
    'idEntidad' => 494,
    'idDa' => 15,
    'nroPreventivo' => 3889,
    'nroCompromiso' => 1,
    'nroDevengado' => 1,
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

    CURLOPT_URL => "https://sigeps.sigma.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=EZQ885431300:FAdwOql4A1wTALhMHAIjRpdpWMvlniki9nRD6BQZwuwx4K7s5qcg9uefCR3XFN24rxUc4m8Z52OcumDDmKDbXltN5UGhV5DTpe3q",

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
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://sigeps.sigma.gob.bo/ejecucion-gasto/api/v1/egadocumentos/verifica",
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
        var_dump($token);
        $token = $response;
        $serializer = new JSONFlattenedSerializer($jsonConverter);

        // We try to load the token.
        $jws = $serializer->unserialize($token);
        echo '<pre>' . var_export(json_decode($jws->getPayload()), true) . '</pre>';
        var_dump($jws);
        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }
}