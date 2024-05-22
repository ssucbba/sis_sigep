<?php
/**
 *@package  SSUC
 *@file     ACTMiddleware.php
 *@author   (FRANKLIN ESPINOZA ALVAREZ)
 *@date     10-01-2024 12:35:55
 *@description Clase que recibe los parametros enviados por la vista para mandar a la capa de Modelo
 */

class ACTMiddleware extends ACTbase
{
    function loadCbteC31Services(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->loadCbteC31Services($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function loadCbteC21Services(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->loadCbteC21Services($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function loadBenefServices(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->loadBenefServices($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function runServices(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->runServices($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }
}
?>