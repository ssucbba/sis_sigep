CREATE OR REPLACE FUNCTION sigep.ft_service_request_c21_ime (
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
 FUNCION: 		sigep.ft_service_request_c21_ime
 DESCRIPCION:   Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tservice_request''
 AUTOR: 		 (admin)
 FECHA:	        13-09-2021
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				13-09-2021 			alan.portugal					Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tservice_request''
 #
 ***************************************************************************/

DECLARE

	v_nro_requerimiento    	integer;
	v_parametros           	record;
	v_id_requerimiento     	integer;
	v_resp		            varchar;
	v_nombre_funcion        text;
	v_mensaje_error         text;
	v_id_service_request	integer;
    v_id_type_service_request	integer;

    --franklin.espinoza 16/08/2020
    v_service				record;
	v_exec_order			integer;
    v_process				boolean = false;
    v_estado_sigep			varchar;

    v_numero				integer;
    v_gestion 				integer;
BEGIN

    v_nombre_funcion = ''sigep.ft_service_request_c21_ime'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:  ''SIG_SERE_C21_INS''
 	#DESCRIPCION:	Insercion de registros
 	#AUTOR:		admin
 	#FECHA:		13-09-2021
	***********************************/

	if(p_transaccion=''SIG_SERE_C21_INS'')then

        begin
        	--Sentencia de la insercion
            select id_type_service_request into v_id_type_service_request
            from  sigep.ttype_service_request
            where service_code = v_parametros.service_code;
			--raise ''v_id_type_service_request: %, %'',v_id_type_service_request,v_parametros.service_code;

        	insert into sigep.tservice_request(
              id_type_service_request,
              estado_reg,
              status,
              sys_origin,
              ip_origin,
              fecha_reg,
              id_usuario_reg
            ) values(
              v_id_type_service_request,
              ''activo'',
              ''pending'',
              v_parametros.sys_origin,
              v_parametros.ip_origin,
              now(),
              p_id_usuario
			)RETURNING id_service_request into v_id_service_request;

			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Service Request C21 almacenado(a) con exito (id_service_request''||v_id_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_service_request'',v_id_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

    /*********************************
 	#TRANSACCION:  ''SIG_REVERT_STATUS_C21''
 	#DESCRIPCION:	Revertir los estados de los servicios sigep
 	#AUTOR:		alan.portugal
 	#FECHA:		20-09-2021
	***********************************/

	elsif(p_transaccion=''SIG_REVERT_STATUS_C21'')then

		begin
			--Sentencia de la eliminacion

            update 	sigep.tsigep_service_request
        	set status = ''canceled''
        	where id_service_request = v_parametros.id_service_request and status in (''next_to_execute'', ''pending'', ''success_revert'');
        	v_exec_order = 1;
            --revertir anteriores exitosos
            for v_service in   select tsr.id_sigep_service_request, tsr.status, tsr.id_service_request, tsr.exec_order, tssr.revert_url
                               from sigep.tsigep_service_request  tsr
                               inner join sigep.ttype_sigep_service_request tssr on tssr.id_type_sigep_service_request = tsr.id_type_sigep_service_request

                               inner join sigep.ttype_service_request tts ON tts.id_type_service_request = tssr.id_type_service_request

                               where tsr.id_service_request = v_parametros.id_service_request and tsr.status = ''success'' and tts.tipo_documento = ''c21''
                               order by tsr.exec_order DESC  loop

        		if v_service.revert_url != '''' and v_service.revert_url is not null then
                  update 	sigep.tsigep_service_request set
                  status = (case when v_exec_order = 1 then ''next_to_revert'' else ''pending_revert'' end),
                  exec_order = v_exec_order
                  where id_sigep_service_request = v_service.id_sigep_service_request and
                        id_service_request = v_parametros.id_service_request;

                  v_exec_order = v_exec_order + 1;
                else
                	update 	sigep.tsigep_service_request set
                  		status = ''canceled''
                	where id_sigep_service_request = v_service.id_sigep_service_request and
                          id_service_request = v_parametros.id_service_request;
                end if;
                v_process = true;
            end loop;


            --Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Service ready to roll back'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_service_request'',v_parametros.id_service_request::varchar);
            v_resp = pxp.f_agrega_clave(v_resp,''v_process'',v_process::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;
    /*********************************
 	#TRANSACCION:   ''SIG_CHANGE_STATE''
 	#DESCRIPCION:	Cambiar estado de documentos C21 sigep
 	#AUTOR:			franklin.espinoza
 	#FECHA:			22-12-2020 13:10:13
	***********************************/

	elsif(p_transaccion=''SIG_CHANGE_STATE'')then

		begin

        	if v_parametros.estado_reg = ''elaborado''  then
            	v_estado_sigep = ''verificaC21'';
            elsif v_parametros.estado_reg = ''verificado'' then
            	if v_parametros.estado_reg = ''verificado'' and v_parametros.direction = ''next'' then
            		v_estado_sigep = ''apruebaC21'';
                else
                	v_estado_sigep = ''verificaC21'';
                end if;

            end if;

			--Sentencia de la eliminacion
			select ts.status, tsr.id_sigep_service_request, tsr.exec_order
            into v_service
            from sigep.tservice_request ts
            inner join sigep.tsigep_service_request tsr on tsr.id_service_request = ts.id_service_request
            inner join sigep.ttype_sigep_service_request tssr on tssr.id_type_sigep_service_request = tsr.id_type_sigep_service_request
            inner join sigep.ttype_service_request tts ON tts.id_type_service_request = tssr.id_type_service_request
            where ts.id_service_request = v_parametros.id_service_request and tssr.sigep_service_name = v_estado_sigep and  tts.tipo_documento = ''c21'';

            select par.value
            into v_gestion
            from sigep.trequest_param par
            where par.id_sigep_service_request = v_service.id_sigep_service_request and par.name in (''gestion'') and par.input_output = ''revert'';

            if v_service.status = ''success'' or v_service.status = ''pending'' or v_service.status = ''canceled'' then
            	if v_parametros.direction = ''next'' then

                    update 	sigep.tsigep_service_request set
                  		status = ''next_to_execute''
                  	where id_sigep_service_request = v_service.id_sigep_service_request and id_service_request = v_parametros.id_service_request;

                    for v_service in select tsr.id_sigep_service_request, tsr.status, tsr.id_service_request, tsr.exec_order
                                     from sigep.tsigep_service_request  tsr
                                     where tsr.id_service_request = v_parametros.id_service_request and tsr.exec_order > v_service.exec_order
                                     order by tsr.exec_order asc  loop

                        update 	sigep.tsigep_service_request set
                        status = ''canceled''
                        where id_sigep_service_request = v_service.id_sigep_service_request and id_service_request = v_parametros.id_service_request;
                    end loop;

                elsif v_parametros.direction = ''previous'' then

                	/*select cor.numero
                    into v_numero
                    from sigep.tcorrelativo cor
                    where cor.gestion = v_gestion;

                    update sigep.trequest_param set
                    	value = v_numero
                    where id_sigep_service_request = v_service.id_sigep_service_request and name in (''idSolicitud'') and input_output = ''revert'';

                    update sigep.tcorrelativo set
                  		numero = numero + 1
              		where gestion = v_gestion;*/

                	update 	sigep.tsigep_service_request set
                		status = ''next_to_revert''
                	where id_sigep_service_request = v_service.id_sigep_service_request and
                	      id_service_request = v_parametros.id_service_request;

                    for v_service in select tsr.id_sigep_service_request, tsr.status, tsr.id_service_request, tsr.exec_order
                                     from sigep.tsigep_service_request  tsr
                                     where tsr.id_service_request = v_parametros.id_service_request and tsr.exec_order > v_service.exec_order
                                     order by tsr.exec_order asc  loop

                        update 	sigep.tsigep_service_request set
                        status = ''canceled''
                        where id_sigep_service_request = v_service.id_sigep_service_request and
                              id_service_request = v_parametros.id_service_request;
                    end loop;
                end if;
                v_process = true;
            end if;


            --Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Service ready to roll back'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_service_request'',v_parametros.id_service_request::varchar);
            v_resp = pxp.f_agrega_clave(v_resp,''v_process'',v_process::varchar);

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

ALTER FUNCTION sigep.ft_service_request_c21_ime (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;