<?php
/**
*@package pXP
*@file gen-ACTSigepServiceRequest.php
*@author  (admin)
*@date 27-12-2018 12:23:23
*@description Clase que recibe los parametros enviados por la vista para mandar a la capa de Modelo
*/

class ACTSigepServiceRequest extends ACTbase{    
			
	function listarSigepServiceRequest(){
		$this->objParam->defecto('ordenacion','id_sigep_service_request');
		if ($this->objParam->getParametro('id_service_request') != '') {
			$this->objParam->addFiltro("ssr.id_service_request = ". $this->objParam->getParametro('id_service_request'));
		}
		$this->objParam->defecto('dir_ordenacion','asc');
		//var_dump($this->objParam->getParametro('tipoReporte'), $this->objParam->getParametro('id_service_request'));exit;
		if($this->objParam->getParametro('tipoReporte')=='excel_grid' || $this->objParam->getParametro('tipoReporte')=='pdf_grid'){
			$this->objReporte = new Reporte($this->objParam,$this);
			$this->res = $this->objReporte->generarReporteListado('MODSigepServiceRequest','listarSigepServiceRequest');
		} else{
			$this->objFunc=$this->create('MODSigepServiceRequest');
			
			$this->res=$this->objFunc->listarSigepServiceRequest($this->objParam);
		}
		$this->res->imprimirRespuesta($this->res->generarJson());
	}

    function onConsumirServicio(){

        $id_sigep_service_request = $this->objParam->getParametro('id_sigep_service_request');
        $cone = new conexion();
        $link = $cone->conectarpdo();

        $sql = "UPDATE  sigep.tsigep_service_request   SET
                    status = 'next_to_execute'
                WHERE id_sigep_service_request = $id_sigep_service_request";

        $stmt = $link->prepare($sql);
        $stmt->execute();

        /****************** SEND PROCESSING *******************/
        $response_status = true;
        $this->objFunc=$this->create('MODSigepServiceRequest');

        while( $response_status ) {

            $this->res=$this->objFunc->procesarServices($this->objParam);

            $this->objParam->addParametro('user', 'admin');
            $this->objParam->addParametro('estado_c31', 'elaborado');
            $this->objParam->addParametro('momento', 'new');

            $this->datos = $this->res->getDatos();

            $next = $this->datos['end_process'];
            if( $next ){
                $response_status = $next;
            }else{
                $response_status = false;
            }
        }
        /****************** SEND PROCESSING *******************/

        $this->res->imprimirRespuesta($this->res->generarJson());
    }

	function procesarServices(){
		$this->objFunc=$this->create('MODSigepServiceRequest');	
		$this->res=$this->objFunc->procesarServices($this->objParam);
		$this->res->imprimirRespuesta($this->res->generarJson());
	}

    /*{developer: franklin.espinoza, date:15/09/2020, description: "Verifica C31 Sistema Sigep"}*/
    function setupSigepProcess(){
        $this->objFunc=$this->create('MODSigepServiceRequest');
        $this->res=$this->objFunc->setupSigepProcess($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    /* Documentos C21 */
    function procesarServicesC21(){
        $this->objFunc=$this->create('MODSigepServiceRequestC21');
        $this->res=$this->objFunc->procesarServicesC21($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function insertarSigepServiceRequest(){
        $this->objFunc=$this->create('MODSigepServiceRequest');
        if($this->objParam->insertar('id_sigep_service_request')){
            $this->res=$this->objFunc->insertarSigepServiceRequest($this->objParam);
        } else{
            $this->res=$this->objFunc->modificarSigepServiceRequest($this->objParam);
        }
        $this->res->imprimirRespuesta($this->res->generarJson());
    }
			
}

?>