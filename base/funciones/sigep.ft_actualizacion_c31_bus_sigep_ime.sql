CREATE OR REPLACE FUNCTION sigep.ft_actualizacion_c31_bus_sigep_ime (
  p_administrador integer,
  p_id_usuario integer,
  v_entrega_json json
)
RETURNS varchar LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
PARALLEL UNSAFE
COST 100
AS'
/**************************************************************************
 SISTEMA:		Sistema de Sigep
 FUNCION: 		sigep.ft_actualizacion_c31_bus_sigep_ime
 DESCRIPCION:   Funcion que actualiza el c31 desde el bus sigep
 AUTOR: 		Maylee Perez Pastor
 FECHA:	        13-10-2023 16:01:32
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:

 DESCRIPCION:
 AUTOR:
 FECHA:
***************************************************************************/

DECLARE

     v_nro_requerimiento    	integer;
     --v_parametros           	record;
     v_registros_op         	record;
     va_tipo_pago				varchar[];
     v_id_requerimiento     	integer;
     v_resp		            	varchar;
	 v_nombre_funcion        	text;
     --v_anho 					integer;
     v_id_obligacion_det 	    integer;
	 v_registros    			record;
     v_hstore_registros 		hstore; --hstore
     --v_date 					date;
     v_registros_det 			record;
     v_id_centro_costo_dos 		integer;
     v_id_obligacion_pago_sg 	varchar[];
     v_id_gestion_sg 			varchar[];
     v_preguntar 				varchar;
     v_pre_integrar_presupuestos	varchar;
     v_id_administrador			integer;
     v_id_partida  				integer;

     --v_hstore_registros_pp		hstore;
     v_registros_pp				record;
     v_id_obligacion_pp 	    integer;

     v_resp_pp					varchar;
     v_id_obligacion_pago_sg_pp varchar[];
     v_id_gestion_sg_pp			varchar[];
     v_id_tipo_documento_ant	integer;


     v_c31						varchar;
     v_c31_sg 					varchar[];
     v_id_service_request_sg	varchar[];
     v_ent_json					json;
     v_c31_json					json;

    v_fields                    jsonb;
    v_fields_response           jsonb=''[]''::jsonb;
    v_status_service			varchar;
    --v_status_service            jsonb;
    v_status					varchar;--jsonb;
	v_contador					integer;
BEGIN
 	v_nombre_funcion = ''sigep.ft_actualizacion_c31_bus_sigep_ime'';

  	--raise exception ''llegaSIGEP DOS '';

		   ---------------------------------------------------------------
           -- actualizar campo c31
           ---------------------------------------------------------------
           --recupera dato

            for v_ent_json in select * from json_array_elements(v_entrega_json) loop

                select jsonb_object_agg(fields.name, fields.value::integer) into  v_fields
                          from (  select par.name, par.value
                                  from sigep.tservice_request ser
                                  inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                                  inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                                  inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                                  where ser.id_service_request = (v_ent_json->>''id_service_request'')::integer
                                  and par.input_output in (''input'')
                                  and tsig.sigep_service_name = ''egaDocumento'' and par.name in (''nroPago'',''nroSecuencia'')
                                  union all
                                  select par.name, par.value--json_object_agg(par.name, par.value)
                                  from sigep.tservice_request ser
                                  inner join sigep.tsigep_service_request sig on sig.id_service_request = ser.id_service_request
                                  inner join sigep.trequest_param par on par.id_sigep_service_request = sig.id_sigep_service_request
                                  inner join sigep.ttype_sigep_service_request tsig on tsig.id_type_sigep_service_request = sig.id_type_sigep_service_request
                                  where ser.id_service_request = (v_ent_json->>''id_service_request'')::integer
                                  and par.input_output in (''output'')
                                  and tsig.sigep_service_name = ''egaDocumento'' and par.name in (''nroPreventivo'', ''nroCompromiso'', ''nroDevengado'')
                          ) fields;

                          --verificando que los estados mencionados sean success
                          select count(distinct tsr.status) into v_contador
                          from sigep.tsigep_service_request tsr
                          inner join sigep.ttype_sigep_service_request tts on tts.id_type_sigep_service_request = tsr.id_type_sigep_service_request
                          where  tsr.status = ''success''
                          and  tsr.id_service_request = (v_ent_json->>''id_service_request'')::integer
                          and tts.sigep_service_name in (''egaDocumento'',''egaPartida'',''egaRespaldo'',''egaBeneficiario'',''egaBoleta'',''egaCuentaContable'',''cuentaLibreta'');

                          select tsr.status into v_status_service
                          from sigep.tservice_request tsr
                          where tsr.id_service_request = (v_ent_json->>''id_service_request'')::integer;

                          IF v_contador = 1 and v_status_service = ''pending''  THEN
                          	v_status= ''success''; --todos success
                          else
                          	v_status= ''error'';
                          END IF;

                v_fields_response = v_fields_response || (v_fields||(''{"id_service_request":''||(v_ent_json->>''id_service_request'')||'',"status":"''||v_status||''"}'')::jsonb)::jsonb;

            end loop;

          --Devuelve la respuesta
          return v_fields_response::varchar;

EXCEPTION

	WHEN OTHERS THEN
		v_resp='''';
		v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',SQLERRM);
		v_resp = pxp.f_agrega_clave(v_resp,''codigo_error'',SQLSTATE);
		v_resp = pxp.f_agrega_clave(v_resp,''procedimientos'',v_nombre_funcion);
        v_resp = pxp.f_agrega_clave(v_resp,''foo'',''barr'');
        v_resp = pxp.f_agrega_clave(v_resp,''preguntar'',v_preguntar);

		raise exception ''%'',v_resp;

END;
';

ALTER FUNCTION sigep.ft_actualizacion_c31_bus_sigep_ime (p_administrador integer, p_id_usuario integer, v_entrega_json json)
  OWNER TO postgres;