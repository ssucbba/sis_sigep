CREATE OR REPLACE FUNCTION sigep.ft_type_sigep_service_request_ime (
  p_administrador integer,
  p_id_usuario integer,
  p_tabla varchar,
  p_transaccion varchar
)
RETURNS varchar LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
PARALLEL UNSAFE
COST 100
AS'
/**************************************************************************
 SISTEMA:		Sigep
 FUNCION: 		sigep.ft_type_sigep_service_request_ime
 DESCRIPCION:   Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.ttype_sigep_service_request''
 AUTOR: 		 (admin)
 FECHA:	        30-11-2018 15:13:43
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				30-11-2018 15:13:43								Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.ttype_sigep_service_request''
 #
 ***************************************************************************/

DECLARE

	v_nro_requerimiento    	integer;
	v_parametros           	record;
	v_id_requerimiento     	integer;
	v_resp		            varchar;
	v_nombre_funcion        text;
	v_mensaje_error         text;
	v_id_type_sigep_service_request	integer;

BEGIN

    v_nombre_funcion = ''sigep.ft_type_sigep_service_request_ime'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:  ''SIG_TSSR_INS''
 	#DESCRIPCION:	Insercion de registros
 	#AUTOR:		admin
 	#FECHA:		30-11-2018 15:13:43
	***********************************/

	if(p_transaccion=''SIG_TSSR_INS'')then

        begin
        	--Sentencia de la insercion
        	insert into sigep.ttype_sigep_service_request(
			id_type_service_request,
			exec_order,
			queue_method,
			estado_reg,
			time_to_refresh,
			queue_url,
			method_type,
			sigep_service_name,
			sigep_url,
			usuario_ai,
			fecha_reg,
			id_usuario_reg,
			id_usuario_ai,
			fecha_mod,
			id_usuario_mod,
			revert_url,
			revert_method,
			user_param,
			json_main_container
          	) values(
			v_parametros.id_type_service_request,
			v_parametros.exec_order,
			v_parametros.queue_method,
			''activo'',
			v_parametros.time_to_refresh,
			v_parametros.queue_url,
			v_parametros.method_type,
			v_parametros.sigep_service_name,
			v_parametros.sigep_url,
			v_parametros._nombre_usuario_ai,
			now(),
			p_id_usuario,
			v_parametros._id_usuario_ai,
			null,
			null,
			v_parametros.revert_url,
			v_parametros.revert_method,
			v_parametros.user_param,
			v_parametros.json_main_container

			)RETURNING id_type_sigep_service_request into v_id_type_sigep_service_request;

			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep service request almacenado(a) con exito (id_type_sigep_service_request''||v_id_type_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_type_sigep_service_request'',v_id_type_sigep_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_TSSR_MOD''
 	#DESCRIPCION:	Modificacion de registros
 	#AUTOR:		admin
 	#FECHA:		30-11-2018 15:13:43
	***********************************/

	elsif(p_transaccion=''SIG_TSSR_MOD'')then

		begin
			--Sentencia de la modificacion
			update sigep.ttype_sigep_service_request set
			id_type_service_request = v_parametros.id_type_service_request,
			exec_order = v_parametros.exec_order,
			queue_method = v_parametros.queue_method,
			time_to_refresh = v_parametros.time_to_refresh,
			queue_url = v_parametros.queue_url,
			method_type = v_parametros.method_type,
			sigep_service_name = v_parametros.sigep_service_name,
			sigep_url = v_parametros.sigep_url,
			fecha_mod = now(),
			id_usuario_mod = p_id_usuario,
			id_usuario_ai = v_parametros._id_usuario_ai,
			usuario_ai = v_parametros._nombre_usuario_ai,
			revert_url = v_parametros.revert_url,
			revert_method = v_parametros.revert_method,
			user_param = v_parametros.user_param,
			json_main_container = v_parametros.json_main_container,
            estado_reg = v_parametros.estado_reg
			where id_type_sigep_service_request=v_parametros.id_type_sigep_service_request;

			--Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep service request modificado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_type_sigep_service_request'',v_parametros.id_type_sigep_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_TSSR_ELI''
 	#DESCRIPCION:	Eliminacion de registros
 	#AUTOR:		admin
 	#FECHA:		30-11-2018 15:13:43
	***********************************/

	elsif(p_transaccion=''SIG_TSSR_ELI'')then

		begin
			--Sentencia de la eliminacion
			delete from sigep.ttype_sigep_service_request
            where id_type_sigep_service_request=v_parametros.id_type_sigep_service_request;

            delete from sigep.trequest_param
			where id_sigep_service_request in (select id_sigep_service_request from sigep.tsigep_service_request where id_type_sigep_service_request = v_parametros.id_type_sigep_service_request);

            --Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep service request eliminado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_type_sigep_service_request'',v_parametros.id_type_sigep_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	else

    	raise exception ''Transaccion inexistente: %'',p_transaccion;

	end if;

EXCEPTION

	WHEN OTHERS THEN
		v_resp='''';
		v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',SQLERRM);
		v_resp = pxp.f_agrega_clave(v_resp,''codigo_error'',SQLSTATE);
		v_resp = pxp.f_agrega_clave(v_resp,''procedimientos'',v_nombre_funcion);
		raise exception ''%'',v_resp;

END;
';

ALTER FUNCTION sigep.ft_type_sigep_service_request_ime (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;