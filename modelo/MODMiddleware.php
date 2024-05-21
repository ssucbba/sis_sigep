<?php
/**
 *@package  SSUC
 *@file     MODMiddleware.php
 *@author   (FRANKLIN ESPINOZA ALVAREZ)
 *@date     10-01-2024 12:35:55
 *@description Clase que envia los parametros requeridos a la Base de datos para la ejecucion de las funciones, y que recibe la respuesta del resultado de la ejecucion de las mismas
 */

class MODMiddleware extends MODbase
{

    function __construct(CTParametro $pParam)
    {
        parent::__construct($pParam);
    }

    function loadServices(){
        //Definicion de variables para ejecucion del procedimiento
        $this->procedimiento='sigep.ft_middleware_ime';
        $this->transaccion='SIG_SERVICE_LOAD';
        $this->tipo_procedimiento='IME';

        //Define los parametros para la funcion
        $this->setParametro('document','document','jsonb');

        //Ejecuta la instruccion
        $this->armarConsulta();//echo $this->consulta;exit;
        $this->ejecutarConsulta();

        //Devuelve la respuesta
        return $this->respuesta;
    }

    function runServices(){
        //Definicion de variables para ejecucion del procedimiento
        $this->procedimiento='sigep.ft_param_ime';
        $this->transaccion='SIG_SERVICE_RUN';
        $this->tipo_procedimiento='IME';

        //Define los parametros para la funcion
        $this->setParametro('id_service_request','id_service_request','integer');

        //Ejecuta la instruccion
        $this->armarConsulta();
        $this->ejecutarConsulta();

        //Devuelve la respuesta
        return $this->respuesta;
    }
}
?>