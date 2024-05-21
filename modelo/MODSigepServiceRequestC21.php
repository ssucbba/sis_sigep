<?php
/**
 *@package pXP
 *@file gen-MODSigepServiceRequestC21.php
 *@author  (admin)
 *@date 14-09-2021
 *@description Clase que envia los parametros requeridos a la Base de datos para la ejecucion de las funciones, y que recibe la respuesta del resultado de la ejecucion de las mismas
 */
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
class MODSigepServiceRequestC21 extends MODbase{
    private $canceledServices = array();
    /*begin franklin.espinoza*/
    private $response_status = true;
    private $end_process = true;
    /*private $request_methods = array();
    private $total_methods = 0;
    private $total_services = 0;
    private $sigep_status = '';*/
    private $check_cycle = true;
    /*end franklin.espinoza*/

    function __construct(CTParametro $pParam){
        parent::__construct($pParam);
    }

    /**************** procesarServicesC21, Procesamiento de seervicio para documentos C21 ************************/
    function procesarServicesC21() { //var_dump('llega procesarServicesC21');exit;

        $estado_c21 = $this->objParam->getParametro('estado_c21');
        $momento = $this->objParam->getParametro('momento');//var_dump('procesarServicesC21', $estado_c21, $momento);exit;
        $cone = new conexion();
        $link = $cone->conectarpdo();
        $procesando = $this->verificarProcesamiento($link);
        //var_dump('llega procesarServicesC21 s', $procesando );exit;
        if ($procesando == 'no') {
            /**/
            $sql = "SELECT ssr.id_sigep_service_request,ssr.id_service_request,ssr.status,ssr.user_name,ssr.queue_id, ssr.queue_revert_id,tsr.sigep_url,tsr.method_type,
					tsr.queue_url,tsr.queue_method,tsr.revert_url,tsr.revert_method,tsr.sigep_main_container, tsr.require_change_perfil, tsr.sigep_service_name
					FROM sigep.tsigep_service_request ssr 
					JOIN sigep.ttype_sigep_service_request tsr ON tsr.id_type_sigep_service_request = ssr.id_type_sigep_service_request
                    JOIN sigep.ttype_service_request tts ON tts.id_type_service_request = tsr.id_type_service_request
					WHERE ssr.estado_reg = 'activo' AND ssr.status IN ('next_to_execute','next_to_revert') AND tts.tipo_documento = 'c21'
					ORDER BY ssr.id_service_request ASC, ssr.exec_order ASC";
            try {
                //$record = $link->query($sql);
                foreach ($record = $link->query($sql) as $row) {

                    if($row['sigep_service_name'] != 'verificaC21' && $row['sigep_service_name'] != 'apruebaC21'){
                        $this->check_cycle = false;
                        $this->procesarService($link,$row);
                    }

                    if($row['sigep_service_name'] == 'verificaC21' || $row['sigep_service_name'] == 'apruebaC21'){
                        if($momento == 'new'){
                            $this->check_cycle = true;
                        }else {
                            if ($estado_c21 == 'elaborado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c21 == 'verificado' && $row['status'] == 'next_to_revert') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c21 == 'verificado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c21 == 'aprobado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            }
                        }
                    }
                }

                $this->modificaProcesamiento($link,'no');

                if($this->check_cycle){
                    $this->end_process = false;
                }

                $this->respuesta=new Mensaje();
                $this->respuesta->setDatos(array(
                    'end_process'    => $this->end_process
                ));

                $this->respuesta->setMensaje('EXITO',$this->nombre_archivo,'Procesamiento exitoso SIGEP','Procesamiento exitoso SIGEP','modelo',$this->nombre_archivo,'procesarServicesC21','IME','');

            } catch (Exception $e) {
                $this->modificaProcesamiento($link,'no');
                $this->respuesta=new Mensaje();
                $this->respuesta->setMensaje('ERROR',$this->nombre_archivo,$e->getMessage(),$e->getMessage(),'modelo','','','','');
            }
            return $this->respuesta;
        } else {
            $this->respuesta=new Mensaje();
            $mensaje = "Existe un proceso activo, no es posible procesar en este momento";
            $this->respuesta->setMensaje('ERROR',$this->nombre_archivo,$mensaje,$mensaje,'modelo','','','','');
            return $this->respuesta;
        }
    }

    function verificarProcesamiento($link) {
        $sql = "SELECT  valor
				FROM pxp.variable_global 
				WHERE variable = 'sigep_processing'";

        foreach ($link->query($sql) as $row) {
            $valor = $row['valor'];
        }

        if ($valor == 'si') {
            return $valor;
        } else {
            $this->modificaProcesamiento($link,'si');
            return 'no';
        }
    }

    function modificaProcesamiento($link, $valor) {
        $sql = "UPDATE  pxp.variable_global 
				SET valor = '" . $valor . "'
				WHERE variable = 'sigep_processing'";

        $stmt = $link->prepare($sql);
        $stmt->execute();
    }

    function procesarService($link,$servicio){
        if (!in_array($servicio['id_service_request'], $this->canceledServices)) { //El servicio no fue cancelado
            $accessToken = $this->getToken($link,$servicio['user_name']);//var_export($accessToken);exit;
            if ($accessToken == "0") { //ocurrio un error al generar el acces token
                $this->serviceError($link,$servicio['id_sigep_service_request'],$servicio['id_service_request'],"Error al generar access token para el usuario: ".$servicio['user_name'],'si', 'token');
            } else {
                if ($servicio['status'] == 'next_to_execute') {
                    $this->procesarSigep($link, $accessToken, $servicio['status'], $servicio['sigep_url'], $servicio['method_type'],
                        $servicio['id_sigep_service_request'], $servicio['id_service_request'], '', $servicio['require_change_perfil']);
                } else if ($servicio['status'] == 'next_to_revert') {
                    //var_dump('a 4');exit;
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['revert_url'],$servicio['revert_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'], '', $servicio['require_change_perfil']);
                }
            }
        }
    }

    function getToken($link,$username) {
        $sql = "SELECT  um.refresh_token,um.access_token,
				(um.date_issued_at + (um.expires_in - 100) * interval '1 second') < now() as expired
				FROM sigep.tuser_mapping um 
				WHERE estado_reg = 'activo' AND lower(pxp_user) = lower('" . $username . "')";

        foreach ($link->query($sql) as $row) { //var_dump('$row',$row,$username);EXIT;
            if ($row['expired']) {
                /*************************************************
                 * OBTENER ACCESS TOKEN
                 *************************************************/

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://sigep.sigma.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=". $row['refresh_token'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
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
                    return "0";
                } else {
                    if ($this->isJson($response)) {
                        $token_response = json_decode($response);

                        $sql = "UPDATE  sigep.tuser_mapping
								SET access_token = '" . $token_response->{'access_token'} . "',
								date_issued_at = now(),
								expires_in = " . $token_response->{'expires_in'} . "
								WHERE estado_reg = 'activo' AND lower(pxp_user) = lower('" . $username . "')";

                        $stmt = $link->prepare($sql);
                        $stmt->execute();

                        return $token_response->{'access_token'};
                    } else {
                        return "0";
                    }
                }
            } else {
                return $row['access_token'];
            }
        }
        return "0";
    }

    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    function serviceError($link,$id_sigep_service_request,$id_service_request, $error,$fatal, $type) {

        $this->resetParametros();

        $this->transaccion = 'SIG_SISERROR_C21_UPD';
        $this->procedimiento = 'sigep.ft_sigep_service_request_c21_ime';
        $this->tipo_procedimiento = 'IME';
        $this->arreglo['id_sigep_service_request'] = $id_sigep_service_request;
        $this->arreglo['error'] = $error;
        $this->arreglo['fatal'] = $fatal;
        $this->arreglo['type_error'] = $type;

        $this->setParametro('id_sigep_service_request','id_sigep_service_request','integer');
        $this->setParametro('error','error','text');
        $this->setParametro('fatal','fatal','varchar');
        $this->setParametro('type_error','type_error','varchar');

        $this->armarConsulta();

        $stmt = $link->prepare($this->consulta);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //recupera parametros dekvuelto depues de insertar ... (id_formula)
        $resp_procedimiento = $this->divRespuesta($result['f_intermediario_ime']);

        $respuesta = $resp_procedimiento['datos'];
        if ($respuesta['cancelar_servicio'] == 'si') {
            array_push($this->canceledServices,$id_service_request);
        }
    }

    function getInputParams($link, $id_sigep_service_request,$status) {
        //var_dump('atributos:',$id_sigep_service_request, $status);

        if ($status == "next_to_execute" || $status == 'next_to_revert') {
            $res = array();
            $input_output =($status == 'next_to_execute') ? "input" : "revert";
            $sql = "SELECT  name, value,ctype
					FROM sigep.trequest_param
					WHERE input_output = '$input_output' and id_sigep_service_request = $id_sigep_service_request";

            foreach ($link->query($sql) as $row) {
                if ($row['value'] == 'NULL') {
                    $res[$row['name']] = NULL;
                } else if ($row['ctype'] == 'NUMERIC') {
                    $res[$row['name']] = (float)$row['value'];
                } else if ($row['ctype'] == 'INTEGER') {
                    $res[$row['name']] = (int)$row['value'];
                } else {
                    $res[$row['name']] = $row['value'];
                }
            }
            //var_dump('next_to_execute data:',$res);
            return $res;

        } else if ($status == "pending_queue" || $status == 'pending_queue_revert') {
            $queue_field = ($status == 'pending_queue') ? "queue_id" : "queue_revert_id";
            $sql = "SELECT   $queue_field as value
					FROM sigep.tsigep_service_request
					WHERE id_sigep_service_request = $id_sigep_service_request";

            foreach ($link->query($sql) as $row) {
                $res = $row['value'];
            }
            //var_dump('pending_queue data:',$res);
            return $res;
        }

    }

    function procesarSigep ($link, $accessToken, $status, $url, $method, $id_sigep_service_request, $id_service_request, $sigep_container = '', $require_change_perfil='no') {
        $algorithmManager = AlgorithmManager::create([
            new RS512(),
        ]);

        $jwk = JWKFactory::createFromKeyFile(
            __DIR__ . '/../ssu.key', // The filename
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
        //obtener parametros
        $params = $this->getInputParams($link, $id_sigep_service_request,$status);
        //var_dump('prueba parametros, url, metodo:', $params, $url, $method, $status, $require_change_perfil);exit;
        //var_dump('respuesta decode:',$params, $accessToken, $url, $method);exit;
        //var_dump($params, $require_change_perfil, $url, $method, $accessToken);exit;

        if ($method != 'GET') {
            if ($params['gestion'] == 2020 && $require_change_perfil != 'si') {
                //var_dump('CAMBIO PERFIL IF');exit;
                $param_p = array("gestion" => "2020", "perfil" => $require_change_perfil);
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
                        "Authorization: bearer " . $accessToken,
                        "Cache-Control: no-cache",
                        "Content-Type: application/json"
                    ),
                    CURLOPT_POSTFIELDS => $param_p
                );

                curl_setopt_array($curl_p, $curl_array_p);
                $response_p = curl_exec($curl_p);
                $err_p = curl_error($curl_p);
                $http_code_p = curl_getinfo($curl_p, CURLINFO_HTTP_CODE);
                curl_close($curl_p);
            } else { //var_dump('CAMBIO PERFIL ELSE', strval($params['gestion']), $require_change_perfil);exit;
                $param_p = array("gestion" => strval($params['gestion']), "perfil" => $require_change_perfil);
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
                        "Authorization: bearer " . $accessToken,
                        "Cache-Control: no-cache",
                        "Content-Type: application/json"
                    ),
                    CURLOPT_POSTFIELDS => $param_p
                );

                curl_setopt_array($curl_p, $curl_array_p);
                $response_p = curl_exec($curl_p);
                $err_p = curl_error($curl_p);
                $http_code_p = curl_getinfo($curl_p, CURLINFO_HTTP_CODE);
                curl_close($curl_p);
                /*var_dump('gestion',$params['gestion']);
                var_dump('$require_change_perfil',$require_change_perfil);
                var_dump('$response_p',$response_p);exit;*/
            }
        }

        if($require_change_perfil == 'si'){
            $param_p = array("gestion" => "2021", "perfil" => "914");
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
                    "Authorization: bearer " . $accessToken,
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
        }
        //var_export($accessToken);exit;
        $curl = curl_init();
        $curl_array = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                "Authorization: bearer " . $accessToken,
                "Cache-Control: no-cache"
            )
        );
        // The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
        if ($method != 'GET') {
            $payload = $jsonConverter->encode($params);
            $jws = $jwsBuilder
                ->create()                               // We want to create a new JWS
                ->withPayload($payload)                  // We set the payload
                ->addSignature($jwk, ['alg' => 'RS512'],['kid' => 'ssuws']) // We add a signature with a simple protected header
                ->build();

            $serializer = new JSONFlattenedSerializer($jsonConverter); // The serializer
            $token = $serializer->serialize($jws, 0); // We serialize the signature at index 0 (we only have one signature).

            $curl_array[CURLOPT_POSTFIELDS] = $token;//var_dump('$token',$token);exit;

            array_push($curl_array[CURLOPT_HTTPHEADER],"Content-Type: application/json");

        }else {
            $curl_array[CURLOPT_URL] =  $url . "?" . http_build_query($params);
        }

        curl_setopt_array($curl,$curl_array);

        $response = curl_exec($curl);//ejecuta seervicio
        //var_export($curl_array);
        /*var_dump('$token',$token);
        var_dump('curl_getinfo',curl_getinfo($curl));
        var_dump('$response',$response);
        var_dump('$method',$method);
        var_dump('$params',$params);exit;*/
        $err = curl_error($curl);
        $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        //var_dump('error decode:',$err, $http_code);exit;

        curl_close($curl);

        if ($err) {
            $this->request_status = false;
            $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al ejecutar curl en sigep para status : $status ,archivo:MODSigepServiceRequestC21,  funcion: procesarSigepC21 ",'si', 'curl');
        } else {
            //DESSERIALIZAR MENSAJE

            // The algorithm manager with the HS256 algorithm.
            $algorithmManager = AlgorithmManager::create([
                new RS512()
            ]);

            // We instantiate our JWS Verifier.
            $jwsVerifier = new JWSVerifier(
                $algorithmManager
            );

            $jwk = JWKFactory::createFromKeyFile(
                __DIR__ . '/../ssu.key', // The filename
                null
            );

            // The JSON Converter.
            $jsonConverter = new StandardConverter();
            $token = $response;
            //var_dump('token:',isset($token), $token == null ,$token=='', is_null($token),  empty($token), !empty($token), empty($token='hola') );

            $serializer = new JSONFlattenedSerializer($jsonConverter);

            // We try to load the token.
            try {
                if($method == 'GET' && $status == 'next_to_execute'){
                    $resObj = json_decode($token, true);
                }else {
                    if( !empty($token) ) {
                        $jws = $serializer->unserialize($token);
                        $resObj = json_decode($jws->getPayload(), true);
                    }
                }
                //var_dump('$resObj', $resObj);
                /*$jws = $serializer->unserialize($token);
               $resObj = json_decode($jws->getPayload(), true);*/
                //var_dump('$resObj',$resObj);
                // var_dump('$http_code',$http_code);exit;
                if (isset($resObj['data']['errores']) || $http_code == '500') {
                    $this->serviceError($link, $id_sigep_service_request, $id_service_request, "MENSAJE:" . $resObj['data']['errores'][0]['mensaje'] . ",CAUSA:" . $resObj['data']['errores'][0]['causa'] . ",ACCION: " . $resObj['data']['errores'][0]['accion'], 'no', 'request ' . $http_code);
                } else {
                    $this->response_status = true;
                    $this->registrarProcesoExitoso($link,$id_sigep_service_request,($sigep_container == '' ? $resObj['data']:$resObj['data'][$sigep_container]),$id_service_request);
                }

            } catch (Exception $e) {;
                $this->response_status = true;
                //$this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al desserializar respuesta : ". $token,'si', 'unserialize');
                $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al desserializar respuesta : ". $e,'si', 'unserialize');
            }
        }
    }

    function registrarProcesoExitoso($link,$id_sigep_service_request,$resObj,$id_service_request) {

        $momento = $this->objParam->getParametro('momento');

        $names = "";
        $values = "";

        if( !is_null($resObj) ) {

            foreach ($resObj as $key => $value) {
                $names .= $key . "||";
                $values .= $value . "||";
            }
            $names = substr($names, 0, -2);
            $values = substr($values, 0, -2);
        }
        //var_dump('valores:');exit;
        //var_dump('valores:', $values);exit;
        $this->resetParametros();
        $this->transaccion = 'SIG_SISSUCC_C21_UPD';
        $this->procedimiento = 'sigep.ft_sigep_service_request_c21_ime';
        $this->tipo_procedimiento = 'IME';
        $this->arreglo['id_sigep_service_request'] = $id_sigep_service_request;
        $this->arreglo['names_output'] = $names;
        $this->arreglo['values_output'] = $values;
        $this->arreglo['momento'] = $momento;
        //var_dump('valores:', $this->arreglo['values_output']);
        //  var_dump('nombres:', $this->arreglo['names_output']);
        $this->setParametro('id_sigep_service_request','id_sigep_service_request','integer');
        $this->setParametro('names_output','names_output','text');
        $this->setParametro('values_output','values_output','text');
        $this->setParametro('momento','momento','text');

        $this->armarConsulta();
        $stmt = $link->prepare($this->consulta);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        //var_dump('resultado consulta:', $result);

        $resp_procedimiento = $this->divRespuesta($result['f_intermediario_ime']);
        //$this->end_process = $resp_procedimiento['datos']['end_process'];
        if ($resp_procedimiento['tipo_respuesta']=='ERROR') {
            $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al llamar a la transaccion : SIG_SISSUCC_C21_UPD ,en archivo:MODSigepServiceRequestC21,  funcion: registrarProcesoExitoso ",'si', 'transaccion');
        }

    }

}
?>