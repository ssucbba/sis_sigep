<?php
/**
*@package pXP
*@file gen-ACTServiceRequest.php
*@author  (admin)
*@date 27-12-2018 13:10:13
*@description Clase que recibe los parametros enviados por la vista para mandar a la capa de Modelo
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

class ACTServiceRequest extends ACTbase{    
			
	function listarServiceRequest(){
		$this->objParam->defecto('ordenacion','id_service_request');
		$this->objParam->defecto('dir_ordenacion','asc');

        if($this->objParam->getParametro('tipo_documento') != ''){
            $this->objParam->addFiltro("tsr.tipo_documento = ''".$this->objParam->getParametro('tipo_documento')."''");
        }

		if($this->objParam->getParametro('tipoReporte')=='excel_grid' || $this->objParam->getParametro('tipoReporte')=='pdf_grid'){
			$this->objReporte = new Reporte($this->objParam,$this);
			$this->res = $this->objReporte->generarReporteListado('MODServiceRequest','listarServiceRequest');
		} else{
			$this->objFunc=$this->create('MODServiceRequest');
			
			$this->res=$this->objFunc->listarServiceRequest($this->objParam);
		}
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
				
	function insertarServiceRequest(){
		/*$this->objFunc=$this->create('MODServiceRequest');
		$this->res=$this->objFunc->insertarServiceRequest($this->objParam);	
		$this->res->imprimirRespuesta($this->res->generarJson());*/

        $this->objFunc=$this->create('MODServiceRequest');
        if($this->objParam->insertar('id_service_request')){
            $this->res=$this->objFunc->insertarServiceRequest($this->objParam);
        } else{
            $this->res=$this->objFunc->modificarServiceRequest($this->objParam);
        }
        $this->res->imprimirRespuesta($this->res->generarJson());
	}
	
	function getServiceStatus(){
		$this->objFunc=$this->create('MODServiceRequest');	
		$this->res=$this->objFunc->getServiceStatus($this->objParam);	
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
						
	function eliminarServiceRequest(){
		$this->objFunc=$this->create('MODServiceRequest');
		$this->res=$this->objFunc->eliminarServiceRequest($this->objParam);
		$this->res->imprimirRespuesta($this->res->generarJson());
	}

    /*{developer: franklin.espinoza, date:15/09/2020, description: "Elimina C31 Sistema Sigep"}*/
    function revertirProcesoSigep(){
        $this->objFunc=$this->create('MODServiceRequest');
        $this->res=$this->objFunc->revertirProcesoSigep($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:27/12/2022, description: "Elimina C21 Sistema Sigep"}*/
    function revertirProcesoSigepC21(){
        $this->objFunc=$this->create('MODServiceRequestC21');
        $this->res=$this->objFunc->revertirProcesoSigepC21($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:15/09/2020, description: "Verifica C31 Sistema Sigep"}*/
    function readyProcesoSigep(){ //var_dump('valores', $this->objParam->getParametro('id_service_request'), $this->objParam->getParametro('direction'), $this->objParam->getParametro('momento'));exit;
        $this->objFunc=$this->create('MODServiceRequest');
        $this->res=$this->objFunc->readyProcesoSigep($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:15/09/2020, description: "Verifica C31 Sistema Sigep"}*/
    function setupSigepProcess(){ //var_dump('valores', $this->objParam->getParametro('id_service_request'), $this->objParam->getParametro('direction'), $this->objParam->getParametro('momento'));exit;
        $this->objFunc=$this->create('MODServiceRequest');
        $this->res=$this->objFunc->setupSigepProcess($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }



    /*{developer: franklin.espinoza, date:15/09/2020, description: "Verifica C31 Sistema Sigep"}*/
    function procesarEstadoRevertidoC31(){

        $response_status = true;
        while($response_status) {

            $this->objFunc=$this->create('MODSigepServiceRequest');
            $this->res=$this->objFunc->procesarServices($this->objParam);

            $response = $this->res->datos;


            $next = $response['end_process'];
            if( $next ){
                $response_status = $next;
            }else{
                $response_status = false;
            }

        }

        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /***************************************************** C21 *****************************************************/
    function insertarServiceRequestC21(){
        $this->objFunc=$this->create('MODServiceRequestC21');
        $this->res=$this->objFunc->insertarServiceRequestC21($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function getServiceStatusC21(){
        $this->objFunc=$this->create('MODServiceRequestC21');
        $this->res=$this->objFunc->getServiceStatusC21($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:22/12/2021, description: "Verifica C21 Sistema Sigep"}*/
    function readyProcesoSigepC21(){
        $this->objFunc=$this->create('MODServiceRequestC21');
        $this->res=$this->objFunc->readyProcesoSigepC21($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:28/12/2021, description: "Procesa estados en el Sistema Sigep"}*/
    function processChangeStates(){
        $this->objFunc=$this->create('MODServiceRequestC21');
        $this->res=$this->objFunc->processChangeStates($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /*{developer: franklin.espinoza, date:27/12/2022, description: "Procesa todos los C21 revertidos Sistema Sigep"}*/
    function procesarEstadoRevertidoC21(){

        $response_status = true;
        while($response_status) {

            $this->objFunc=$this->create('MODSigepServiceRequestC21');
            $this->res=$this->objFunc->procesarServicesC21($this->objParam);

            $response = $this->res->datos;


            $next = $response['end_process'];
            if( $next ){
                $response_status = $next;
            }else{
                $response_status = false;
            }

        }

        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /***************************************************** C21 *****************************************************/

    /*************************************** VERIFY STATUS C31 ***************************************/
    function verificarStatusEntregaC31(){

        $id_service_request = $this->objParam->getParametro('id_service_request');
        $estado = $this->objParam->getParametro('estado');

        $cone = new conexion();
        $link = $cone->conectarpdo();

        $sql = "select json_object_agg(fields.name, fields.value::integer) field
                from (  select par.name, par.value
                        from sigep.tservice_request ser
                        inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                        inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                        inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                        where ser.id_service_request = $id_service_request and par.input_output in ('input')
                        and tsig.sigep_service_name = 'egaDocumento' and par.name in ('nroPago','nroSecuencia')
                        union all
                        select par.name, par.value
                        from sigep.tservice_request ser
                        inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                        inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                        inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                        where ser.id_service_request = $id_service_request and par.input_output in ('output')
                        and tsig.sigep_service_name = 'egaDocumento' and par.name in ('nroPreventivo', 'nroCompromiso', 'nroDevengado')
                ) fields";

        $consulta = $link->query($sql);
        $consulta->execute();
        $fields = json_decode($consulta->fetchAll(PDO::FETCH_ASSOC)[0]['field'],true);

        $sql = "select tum.refresh_token, tum.access_token, tum.authorization_code
                from sigep.tuser_mapping tum
                where tum.sigep_user = 'CSO313059200'/*tum.fecha_mod::date = current_date*/ limit 1";

        $consulta = $link->query($sql);
        $consulta->execute();
        $authorization = $consulta->fetchAll(PDO::FETCH_ASSOC)[0];

        /** ************************ BEGIN ACCESS TOKEN *********************** **/
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => "https://sigep.gob.bo/rsseguridad/apiseg/token?grant_type=refresh_token&client_id=0&redirect_uri=%2Fmodulo%2Fapiseg%2Fredirect&client_secret=0&refresh_token=".$authorization['refresh_token'],
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

        $token_response = json_decode($response);
        $access_token = $token_response->{'access_token'};

        /** ************************ END ACCESS TOKEN *********************** **/

        /** ************************ BEGIN CAMBIO PERFIL *********************** **/
        $jsonConverter = new StandardConverter();

        $param_p = array("gestion" => "2023", "perfil" => "5");
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
                "Authorization: bearer " . $access_token,
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
        /** ************************ END CAMBIO PERFIL *********************** **/

        /*************************************************
         *
         *
         * HACER PETICION GET
         *
         **************************************************/
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => "https://sigep.gob.bo/ejecucion-gasto/api/v1/egadocumento?gestion=2023&idEntidad=494&idDa=15&nroPreventivo=".$fields['nroPreventivo']."&nroCompromiso=".$fields['nroCompromiso']."&nroDevengado=".$fields['nroDevengado']."&nroPago=".$fields['nroPago']."&nroSecuencia=".$fields['nroSecuencia'],
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
                __DIR__ . '/../ssu.key',    // The filename
                null,                       // Secret if the key is encrypted
                [
                    'use' => 'sig',         // Additional parameters
                    'kid' => 'ssuws'
                ]
            );
            // The JSON Converter.
            $jsonConverter = new StandardConverter();
            $token = $response;
            $serializer = new JSONFlattenedSerializer($jsonConverter);

            // We try to load the token.
            $jws = $serializer->unserialize($token);
            $payload = json_decode($jws->getPayload(), true)['data'];
            $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

            $sql = "select sigep.f_status_change_c31($id_service_request,'$estado','".strtolower($payload['estado'])."')";
            $stmt = $link->prepare($sql);
            $stmt->execute();

            $this->res = new Mensaje();
            $this->res->setMensaje(
                'EXITO',
                'driver.php',
                'Service Request C31',
                'Service Request C31',
                'control',
                'sigep.ft_service_request_ime',
                'SIG_VERIFY_STATUS',
                'IME'
            );
            $this->res->setDatos($payload);
        }

        /*$this->objFunc=$this->create('MODServiceRequest');
        $this->res=$this->objFunc->verificarStatusEntregaC31($this->objParam);*/
        $this->res->imprimirRespuesta($this->res->generarJson());
    }
    /*************************************** VERIFY STATUS C31 ***************************************/

}

?>