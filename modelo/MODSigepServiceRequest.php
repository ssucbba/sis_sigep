<?php
/**
 *@package pXP
 *@file gen-MODSigepServiceRequest.php
 *@author  (admin)
 *@date 27-12-2018 12:23:23
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
class MODSigepServiceRequest extends MODbase{
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

    function listarSigepServiceRequest(){
        //Definicion de variables para ejecucion del procedimientp
        $this->procedimiento='sigep.ft_sigep_service_request_sel';
        $this->transaccion='SIG_SSR_SEL';
        $this->tipo_procedimiento='SEL';//tipo de transaccion

        //Definicion de la lista del resultado del query
        $this->captura('id_sigep_service_request','int4');
        $this->captura('id_service_request','int4');
        $this->captura('id_type_sigep_service_request','int4');
        $this->captura('estado_reg','varchar');
        $this->captura('status','varchar');
        $this->captura('date_queue_sent','timestamp');
        $this->captura('date_request_sent','timestamp');
        $this->captura('last_message','text');
        $this->captura('id_usuario_reg','int4');
        $this->captura('usuario_ai','varchar');
        $this->captura('fecha_reg','timestamp');
        $this->captura('id_usuario_ai','int4');
        $this->captura('id_usuario_mod','int4');
        $this->captura('fecha_mod','timestamp');
        $this->captura('usr_reg','varchar');
        $this->captura('usr_mod','varchar');

        $this->captura('sigep_service_name','varchar');
        $this->captura('last_message_revert','text');
        $this->captura('user_name','varchar');
        $this->captura('queue_id','varchar');
        $this->captura('queue_revert_id','varchar');



        //Ejecuta la instruccion
        $this->armarConsulta();//echo $this->consulta;exit;
        $this->ejecutarConsulta();

        //Devuelve la respuesta
        return $this->respuesta;
    }

    function modificarSigepServiceRequest(){
        //Definicion de variables para ejecucion del procedimiento
        $this->procedimiento='sigep.ft_sigep_service_request_ime';
        $this->transaccion='SIG_SISERE_UPD';
        $this->tipo_procedimiento='IME';

        //Define los parametros para la funcion
        $this->setParametro('id_sigep_service_request','id_sigep_service_request','int4');
        $this->setParametro('id_service_request','id_service_request','int4');
        $this->setParametro('id_type_sigep_service_request','id_type_sigep_service_request','int4');
        $this->setParametro('estado_reg','estado_reg','varchar');

        $this->setParametro('status','status','varchar');
        $this->setParametro('date_queue_sent','date_queue_sent','timestamp');
        $this->setParametro('date_request_sent','date_request_sent','timestamp');
        $this->setParametro('last_message','last_message','text');

        $this->setParametro('sigep_service_name','sigep_service_name','varchar');
        $this->setParametro('last_message_revert','last_message_revert','text');
        $this->setParametro('user_name','user_name','varchar');
        $this->setParametro('queue_id','queue_id','varchar');
        $this->setParametro('queue_revert_id','queue_revert_id','varchar');


        //Ejecuta la instruccion
        $this->armarConsulta();
        $this->ejecutarConsulta();

        //Devuelve la respuesta
        return $this->respuesta;
    }

    function procesarServices() {//var_dump(__DIR__. ' Bolivia');exit;

        $estado_c31 = $this->objParam->getParametro('estado_c31');//var_dump('procesarServices',$estado_c31);exit;
        $momento = $this->objParam->getParametro('momento');
        $cone = new conexion();
        $link = $cone->conectarpdo();
        $procesando = $this->verificarProcesamiento($link);

        if ($procesando == 'no') {
            $sql = "SELECT ssr.id_sigep_service_request,ssr.id_service_request,ssr.status,ssr.user_name,ssr.queue_id, ssr.queue_revert_id,tsr.sigep_url,tsr.method_type,
					tsr.queue_url,tsr.queue_method,tsr.revert_url,tsr.revert_method,tsr.sigep_main_container, tsr.require_change_perfil, tsr.sigep_service_name
					FROM sigep.tsigep_service_request ssr 
					JOIN sigep.ttype_sigep_service_request tsr ON tsr.id_type_sigep_service_request = ssr.id_type_sigep_service_request
					WHERE ssr.estado_reg = 'activo' AND ssr.status IN ('next_to_execute','pending_queue','next_to_revert','pending_queue_revert')
					ORDER BY ssr.id_service_request ASC, ssr.exec_order ASC";
            try {
                foreach ($record = $link->query($sql) as $row) {

                    /*$this->total_services = $row['total_services'];
                    $this->total_methods = count($this->request_methods);
                    $this->sigep_status = $row['status'];*/
                    //var_dump(' BUS SERVICIO $row', $row);exit;
                    if($row['sigep_service_name'] != 'verificaDoc' && $row['sigep_service_name'] != 'apruebaDoc' && $row['sigep_service_name'] != 'firmaDoc'){
                        $this->check_cycle = false;
                        $this->procesarService($link,$row);
                    }


                    /*begin franklin.espinoza 16/09/2020*/
                    if($row['sigep_service_name'] == 'verificaDoc' || $row['sigep_service_name'] == 'apruebaDoc' || $row['sigep_service_name'] == 'firmaDoc'){
                        if($momento == 'new'){
                            $this->check_cycle = true;
                        }else {
                            if ($estado_c31 == 'elaborado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c31 == 'elaborado' && $row['status'] == 'pending_queue') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            } else if ($estado_c31 == 'verificado' && $row['status'] == 'next_to_revert') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c31 == 'verificado' && $row['status'] == 'pending_queue_revert') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            } else if ($estado_c31 == 'verificado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c31 == 'verificado' && $row['status'] == 'pending_queue') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            } else if ($estado_c31 == 'aprobado' && $row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($estado_c31 == 'aprobado' && $row['status'] == 'pending_queue') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            } /*else {
                                $this->procesarService($link, $row);
                            }*/
                        }
                    }
                    /*end franklin.espinoza 16/09/2020*/
                }
                $this->modificaProcesamiento($link,'no');

                if($this->check_cycle){
                    $this->end_process = false;
                }

                $this->respuesta=new Mensaje();
                $this->respuesta->setDatos(array(
                    'end_process'    => $this->end_process
                ));

                $this->respuesta->setMensaje('EXITO',$this->nombre_archivo,'Procesamiento exitoso SIGEP','Procesamiento exitoso SIGEP','modelo',$this->nombre_archivo,'procesarServices','IME','');
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
                } else if ($servicio['status'] == 'pending_queue') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['queue_url'],$servicio['queue_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'],$servicio['sigep_main_container'],$servicio['require_change_perfil']);
                } else if ($servicio['status'] == 'next_to_revert') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['revert_url'],$servicio['revert_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'], '', $servicio['require_change_perfil']);

                } else if ($servicio['status'] == 'pending_queue_revert') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['queue_url'],$servicio['queue_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'],$servicio['sigep_main_container'], $servicio['require_change_perfil']);
                }
            }
        }
    }
    function serviceError($link,$id_sigep_service_request,$id_service_request, $error,$fatal, $type) {

        $this->resetParametros();

        $this->transaccion = 'SIG_SISERROR_UPD';
        $this->procedimiento = 'sigep.ft_sigep_service_request_ime';
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

        //recupera parametros devuelto depues de insertar ... (id_formula)
        $resp_procedimiento = $this->divRespuesta($result['f_intermediario_ime']);

        $respuesta = $resp_procedimiento['datos'];
        if ($respuesta['cancelar_servicio'] == 'si') {
            array_push($this->canceledServices,$id_service_request);
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
            if ($params['gestion'] == 2021 && $status != 'pending_queue' && $status != 'pending_queue_revert' && $require_change_perfil != 'si') {

                $param_p = array("gestion" => strval($params['gestion']), "perfil" => $require_change_perfil);
                $param_p = $jsonConverter->encode($param_p);
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

                /*var_dump('A', $require_change_perfil, strval($params['gestion']));
                var_dump('$accessToken', $accessToken);
                var_dump('$response_p', $response_p);exit;*/
            }else{
                $param_p = array("gestion" => strval($params['gestion']), "perfil" => $require_change_perfil);
                $param_p = $jsonConverter->encode($param_p);
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
            }
        }
        
        if($require_change_perfil == 'si'){
            $param_p = array("gestion" =>  "2024", "perfil" => "914");
            $param_p = $jsonConverter->encode($param_p);
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
            if ($status == "pending_queue" || $status == 'pending_queue_revert') {
                $curl_array[CURLOPT_URL] =  $url . "/" . $params;

            } else {
                $curl_array[CURLOPT_URL] =  $url . "?" . http_build_query($params);
            }

        }
        /*var_dump('$params',$params);
        var_dump('$curl',$curl);
        var_dump('$curl_array',$curl_array);exit;*/
        curl_setopt_array($curl,$curl_array);

        $response = curl_exec($curl);
        //var_export($curl_array);
        /*var_export(curl_getinfo($curl));
        var_export('===================================================');
        var_dump('response',$response, $method, $params);exit;*/
        $err = curl_error($curl);
        $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        //var_dump('error decode:',$err);exit;

        curl_close($curl);

        if ($err) {
            $this->request_status = false;
            $parameters = json_encode($params);
            $sql = "insert into sigep.trequest_error(id_usuario_reg,id_service_request,id_sigep_service_request,description,params,url) 
                    values (1,$id_service_request,$id_sigep_service_request,'error request curl','$parameters'::jsonb,'$url')";
            $consulta = $link->query($sql);
            $consulta->execute();
            //$this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al ejecutar curl en sigep para status : $status ,archivo:MODSigepServiceRequest,  funcion: procesarSigep ",'si', 'curl');
            $this->procesarSigep ($link, $accessToken, $status, $url, $method, $id_sigep_service_request, $id_service_request, $sigep_container, $require_change_perfil);
            $this->response_status = true;
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

                if (isset($resObj['data']['errores']) || $http_code == '500') {
                    if( ($resObj['data']['errores'])[0]['mensaje'] != 'EGA-99999' ){
                        //$this->procesarSigep ($link, $accessToken, $status, $url, $method, $id_sigep_service_request, $id_service_request, $sigep_container , $require_change_perfil);
                        $this->serviceError($link, $id_sigep_service_request, $id_service_request, "MENSAJE:" . $resObj['data']['errores'][0]['mensaje'] . ",CAUSA:" . $resObj['data']['errores'][0]['causa'] . ",ACCION: " . $resObj['data']['errores'][0]['accion'], 'no', 'request ' . $http_code);
                    }else{
                        $this->response_status = false;
                    }
                } else if(isset($resObj['data']['C31']) && !is_array($resObj['data']['C31'])){
                    $this->response_status = true;
                    $this->serviceError($link,$id_sigep_service_request,$id_service_request, $resObj['data']['C31'], 'no', 'C31 '.$http_code);
                } else {
                    /*if( $this->total_services == 1 ){
                        $this->end_process = false;
                    }*/
                    $this->response_status = true;
                    $this->registrarProcesoExitoso($link,$id_sigep_service_request,($sigep_container == '' ? $resObj['data']:$resObj['data'][$sigep_container]),$id_service_request);
                }

            } catch (Exception $e) {
                $this->response_status = true;
                $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al desserializar respuesta : ". $token,'si', 'unserialize');
            }
        }
    }
    function registrarProcesoExitoso($link,$id_sigep_service_request,$resObj,$id_service_request) {

        /*begin franklin.espinoza 21/09/2020*/
        $momento = $this->objParam->getParametro('momento');
        /*end franklin.espinoza 21/09/2020*/

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

        //var_dump('valores:', $values);exit;
        $this->resetParametros();
        $this->transaccion = 'SIG_SISSUCC_UPD';
        $this->procedimiento = 'sigep.ft_sigep_service_request_ime';
        $this->tipo_procedimiento = 'IME';
        $this->arreglo['id_sigep_service_request'] = $id_sigep_service_request;
        $this->arreglo['names_output'] = $names;
        $this->arreglo['values_output'] = $values;
        $this->arreglo['momento'] = $momento;
        //var_dump('valores:', $this->arreglo['values_output']);
        //var_dump('nombres:', $this->arreglo['names_output']);
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
            $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al llamar a la transaccion : SIG_SISSUCC_UPD ,en archivo:MODSigepServiceRequest,  funcion: registrarProcesoExitoso ",'si', 'transaccion');
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

    function getToken($link,$username) {
        $sql = "SELECT  um.refresh_token,um.access_token,
				(um.date_issued_at + (um.expires_in - 100) * interval '1 second') < now() as expired
				FROM sigep.tuser_mapping um 
				WHERE estado_reg = 'activo' AND lower(pxp_user) = lower('" . $username . "')";


        foreach ($link->query($sql) as $row) {
            if ($row['expired']) {
                /*************************************************
                 *
                 *
                 * OBTENER ACCESS TOKEN
                 *
                 **************************************************/


                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://sigep.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=". $row['refresh_token'],
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


    /*{developer: franklin.espinoza, date:15/09/2020, description: "Elimina C31 Sistema Sigep"}*/
    function setupSigepProcess(){ //var_dump('setupSigepProcess',$this->objParam->getParametro('json_data'));exit;


        $id_service_request = $this->objParam->getParametro('id_service_request');
        $cone = new conexion();
        $link = $cone->conectarpdo();

        $res = array();
        $sql = "select par.name, par.value, par.ctype, sig.user_name
                from sigep.tservice_request ser 
                inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                where ser.id_service_request = $id_service_request and par.input_output in ('input') and tsig.sigep_service_name = 'egaDocumento' 
                      and par.name in ('gestion','idEntidad','idDa','nroPago','nroDevengadoSip',
                                       'nroSecuencia','tipoFormulario','tipoDocumento','tipoEjecucion','preventivo','compromiso', 
                                       'devengado','pago','devengadoSip','pagoSip','regularizacion','fechaElaboracion','claseGastoCip',
                                       'claseGastoSip','moneda','fechaTipoCambio','compraVenta','totalAutorizadoMo',
                                       'totalRetencionesMo', 'totalMultasMo', 'liquidoPagableMo')
                union all

                select par.name, par.value, par.ctype, sig.user_name
                from sigep.tservice_request ser
                inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                where ser.id_service_request = $id_service_request and par.input_output in ('output') and tsig.sigep_service_name = 'egaDocumento'
                      and par.name in ('nroPreventivo','nroCompromiso','nroDevengado')";


        $user = '';
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
            $user = $row['user_name'];
        }


        /*$sql = "select
        ";*/
        //$res['nroPreventivo'] = $this->objParam->getParametro('preventivo');
        //$res['nroCompromiso'] = $this->objParam->getParametro('compromiso');
        //$res['nroDevengado'] = $this->objParam->getParametro('devengado');
        //$res['nroPago'] = $this->objParam->getParametro('pago');
        //$res['nroDevengadoSip'] = $this->objParam->getParametro('nro_devengado_sip');
        //$clase_gasto = $this->objParam->getParametro('clase_comprobante');
        //$res['nroDevengadoSip'] = 0;
        /*if($clase_gasto == 5){
            $res['pagoSip'] = 'S';
        }else{
            $res['pagoSip'] = 'N';
        }*/

        //$res['idCatpry'] = NULL;
        //$res['sigade'] = NULL;
        //$res['otfin'] = NULL;
        $res['resumenOperacion'] = $this->objParam->getParametro('json_data');
        //var_dump($res);exit;
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
        //var_dump('$res',$res,$user);exit;
        //obtener parametros
        $params = $res;
        $url = 'https://sigep.gob.bo/ejecucion-gasto/api/v1/egadocumento';
        $accessToken = $this->getToken($link,$user);
        $method = 'PUT';

        //var_dump('setupSigepProcess', $params, $accessToken);exit;

        /*if ($method != 'GET') {

            $param_p = array("gestion" => strval(2024), "perfil" =>'5');
            $param_p = $jsonConverter->encode($param_p);
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

        }*/

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

        }

        curl_setopt_array($curl,$curl_array);
        $response = curl_exec($curl);

        //$response_info = curl_getinfo($curl);var_dump('$response_info',$response_info);exit;
        //var_export($response_info['http_code']);
        //var_dump('response',$response);exit;

        $err = curl_error($curl);
        $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close($curl);

        //var_dump('error decode:',$err, $http_code);



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


        if( !empty($token) ) {
            $jws = $serializer->unserialize($token);
            $resObj = json_decode($jws->getPayload(), true);
        }

        //var_dump('resObj', $resObj);exit;



        if ($err) {
            $this->request_status = false;
            /*$this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al ejecutar curl en sigep para status : $status ,archivo:MODSigepServiceRequest,  funcion: procesarSigep ",'si', 'curl');*/
        } else {
            /*//DESSERIALIZAR MENSAJE

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


                if (isset($resObj['data']['errores']) || $http_code == '500') {
                    if( ($resObj['data']['errores'])[0]['mensaje'] != 'EGA-99999' ){

                        $this->serviceError($link, $id_sigep_service_request, $id_service_request, "MENSAJE:" . $resObj['data']['errores'][0]['mensaje'] . ",CAUSA:" . $resObj['data']['errores'][0]['causa'] . ",ACCION: " . $resObj['data']['errores'][0]['accion'], 'no', 'request ' . $http_code);
                    }else{
                        $this->response_status = false;
                    }
                } else if(isset($resObj['data']['C31']) && !is_array($resObj['data']['C31'])){
                    $this->response_status = true;
                    $this->serviceError($link,$id_sigep_service_request,$id_service_request, $resObj['data']['C31'], 'no', 'C31 '.$http_code);
                } else {

                    $this->response_status = true;
                    $this->registrarProcesoExitoso($link,$id_sigep_service_request,($sigep_container == '' ? $resObj['data']:$resObj['data'][$sigep_container]),$id_service_request);
                }

            } catch (Exception $e) {
                $this->response_status = true;
                $this->serviceError($link,$id_sigep_service_request,$id_service_request,"Error al desserializar respuesta : ". $token,'si', 'unserialize');
            }*/
        }

        $response_status = false;
        if($http_code == 200){
            $response_status = true;
        }

        $this->respuesta=new Mensaje();
        $this->respuesta->setDatos(array(
            'response_status' => $response_status,
            'idCola' => $resObj['data']['idCola'],
            'http_code' => $http_code
        ));
        if($response_status){
            $this->respuesta->setMensaje('EXITO', $this->nombre_archivo, 'Procesamiento exitoso SIGEP', 'Procesamiento exitoso SIGEP', 'modelo', $this->nombre_archivo, 'setupSigepProcess', 'IME', '');
        }else{
            $this->respuesta->setMensaje('FALLA', $this->nombre_archivo, 'Procesamiento fallido SIGEP', 'Procesamiento fallido SIGEP', 'modelo', $this->nombre_archivo, 'setupSigepProcess', 'IME', '');
        }
        return $this->respuesta;
    }

    function getCustomInputParams() {

        $cone = new conexion();
        $link = $cone->conectarpdo();

            $res = array();
            $sql = "select par.name, par.value, par.ctype, sig.user_name
                    from sigep.tservice_request ser 
                    inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                    inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                    inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                    where ser.id_service_request = 180 and par.input_output in ('input', 'output') and tsig.sigep_service_name = 'egaDocumento' 
                    and par.name in ('gestion','idEntidad','idDa','nroCompromiso','nroDevengado','nroPago','nroSecuencia')";

            $res = $link->query($sql);
            var_dump('getCustomInputParams',$res);exit;
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
    }

    /*********** {developer:franklin.espinoza, description: Procesa service de VoBo, date:01/12/2020} ***********/
    function procesarServicesApproval(){

        $id_service_request = $this->objParam->getParametro('id_service_request');
        $estado_c31 = $this->objParam->getParametro('estado_c31');
        $momento = $this->objParam->getParametro('momento');

        $cone = new conexion();
        $link = $cone->conectarpdo();
        $procesando = $this->verificarProcesamiento($link);

        if ($procesando == 'no') {
            $sql = "SELECT ssr.id_sigep_service_request,ssr.id_service_request,ssr.status,ssr.user_name,ssr.queue_id, ssr.queue_revert_id,tsr.sigep_url,tsr.method_type,
					tsr.queue_url,tsr.queue_method,tsr.revert_url,tsr.revert_method,tsr.sigep_main_container, tsr.require_change_perfil, tsr.sigep_service_name
					FROM sigep.tsigep_service_request ssr 
					JOIN sigep.ttype_sigep_service_request tsr ON tsr.id_type_sigep_service_request = ssr.id_type_sigep_service_request
					WHERE ssr.estado_reg = 'activo' AND ssr.status IN ('next_to_execute','pending_queue','next_to_revert','pending_queue_revert') and 
                    tsr.sigep_service_name in ('verificaDoc', 'apruebaDoc', 'firmaDoc') and ssr.id_service_request = ".$id_service_request."
					ORDER BY ssr.id_service_request ASC, ssr.exec_order ASC";
            try {
                foreach ($record = $link->query($sql) as $row) {
                    /*begin franklin.espinoza 16/09/2020*/
                    if($row['sigep_service_name'] == 'verificaDoc' || $row['sigep_service_name'] == 'apruebaDoc' || $row['sigep_service_name'] == 'firmaDoc'){

                            if ($row['status'] == 'next_to_execute') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ( $row['status'] == 'pending_queue') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            } else if ($row['status'] == 'next_to_revert') {
                                $this->check_cycle = false;
                                $this->procesarService($link, $row);
                            } else if ($row['status'] == 'pending_queue_revert') {
                                $this->procesarService($link, $row);
                                $this->check_cycle = $this->response_status;
                            }

                    }
                    /*end franklin.espinoza 16/09/2020*/
                }
                $this->modificaProcesamiento($link,'no');

                if($this->check_cycle){
                    $this->end_process = false;
                }

                $this->respuesta=new Mensaje();
                $this->respuesta->setDatos(array(
                    'end_process'    => $this->end_process
                ));

                $this->respuesta->setMensaje('EXITO',$this->nombre_archivo,'Procesamiento exitoso SIGEP','Procesamiento exitoso SIGEP','modelo',$this->nombre_archivo,'procesarServices','IME','');
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

    function procesarServiceApproval($link,$servicio){
        if (!in_array($servicio['id_service_request'], $this->canceledServices)) { //El servicio no fue cancelado

            $accessToken = $this->getToken($link,$servicio['user_name']);//var_export($accessToken);exit;
            if ($accessToken == "0") { //ocurrio un error al generar el acces token
                $this->serviceError($link,$servicio['id_sigep_service_request'],$servicio['id_service_request'],"Error al generar access token para el usuario: ".$servicio['user_name'],'si', 'token');
            } else {
                if ($servicio['status'] == 'next_to_execute') {
                    $this->procesarSigep($link, $accessToken, $servicio['status'], $servicio['sigep_url'], $servicio['method_type'],
                        $servicio['id_sigep_service_request'], $servicio['id_service_request'], '', $servicio['require_change_perfil']);
                } else if ($servicio['status'] == 'pending_queue') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['queue_url'],$servicio['queue_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'],$servicio['sigep_main_container'],$servicio['require_change_perfil']);
                } else if ($servicio['status'] == 'next_to_revert') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['revert_url'],$servicio['revert_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'], '', $servicio['require_change_perfil']);

                } else if ($servicio['status'] == 'pending_queue_revert') {
                    $this->procesarSigep($link,$accessToken,$servicio['status'],$servicio['queue_url'],$servicio['queue_method'],
                        $servicio['id_sigep_service_request'],$servicio['id_service_request'],$servicio['sigep_main_container'], $servicio['require_change_perfil']);
                }
            }
        }
    }
    /*********** {developer:franklin.espinoza, description: Procesa service de VoBo, date:01/12/2020} ***********/

}
?>