<?php
/***
Nombre: ActionDisparaMigracionesRRHH.php
Proposito: Invocar al disparador de migraciones de postgres a sqlserver saltandose los pasos de
 *          autentificacion
 * 		    este archivo se  invoca desde un cron tab en servidor linux
 *          solo deberia llamarse desde ahí, otras llamadas no seran autorizadas
Autor:	ERP SSU (F.E.A)
Fecha:	02/09/2017
 */

include_once(dirname(__FILE__)."/../../lib/lib_control/CTSesion.php");
session_start();
$_SESSION["_SESION"]= new CTSesion();

include(dirname(__FILE__).'/../../lib/DatosGenerales.php');
include_once(dirname(__FILE__).'/../../lib/lib_general/Errores.php');
include_once(dirname(__FILE__).'/../../lib/rest/PxpRestClient2.php');

ob_start();

//estable aprametros ce la cookie de sesion
$_SESSION["_CANTIDAD_ERRORES"]=0;//inicia control

register_shutdown_function('fatalErrorShutdownHandler');
set_exception_handler('exception_handler');
set_error_handler('error_handler');;
include_once(dirname(__FILE__).'/../../lib/lib_control/CTincludes.php');

//$pxpRestClient = PxpRestClient::connect('127.0.0.1',substr($_SESSION["_FOLDER"], 1) .'pxp/lib/rest/')->setCredentialsPxp($_GET['user'],$_GET['pw']);
//PxpRestClient::connect('erp.obairlines.bo', 'rest/',443,'https')->setCredentialsPxp('notificaciones','Mund0libre');
$pxpRestClient = PxpRestClient2::connect('127.0.0.1',substr($_SESSION["_FOLDER"], 1) .'pxp/lib/rest/')->setCredentialsPxp($_GET['user'],$_GET['pw']);


$fecha = new DateTime();
$res = $pxpRestClient->doPost('sigep/ServiceRequest/revertirProcesoSigep',
    array(
        "sys_origin" => 'erp',
        "id_service_request" => 137
    )
); var_dump($res);
$res_json = json_decode($res);//var_dump($res_json);exit;

var_dump($res_json);
exit;


?>