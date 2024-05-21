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
    function loadServices(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->loadServices($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }

    function runServices(){
        $this->objFunc=$this->create('MODMiddleware');
        $this->res=$this->objFunc->runServices($this->objParam);
        $this->res->imprimirRespuesta($this->res->generarJson());
    }
}
?>