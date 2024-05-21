CREATE OR REPLACE FUNCTION sigep.ft_middleware_ime (
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
 FUNCION: 		sigep.ft_middleware_ime
 DESCRIPCION:   Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tservice_request''
 AUTOR: 		(FRANKLIN ESPINOZA ALVAREZ)
 FECHA:	        10-01-2024 12:35:55
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				10-01-2024 12:35:55								Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tservice_request''
 #
 ***************************************************************************/

DECLARE

	v_parametros           	    record;
	v_resp		                varchar;
	v_nombre_funcion            text;
    v_id_service_request        integer;
    v_id_type_service_request   integer;
    v_status_group 			    varchar;
    v_id_sigep_service_request  integer;
    v_service                   record;
    v_document                  jsonb;
    v_status                    varchar;
    v_user                      varchar;
    v_params                    record;
    v_numero				    integer;
    v_gestion 				    integer;
    v_id_correlativo		    integer;
    egaDocumento                jsonb;
    egaRespaldo                 jsonb;
    egaBeneficiario             jsonb;
    egaPartida                  jsonb;
    egaBoleta                   jsonb;
    egaCuentaContable           jsonb;
    cuentaLibreta               jsonb;
    v_value                     text;
    v_count                     integer;
    v_index                     integer;
BEGIN

    v_nombre_funcion = ''sigep.ft_middleware_ime'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:   ''SIG_SERVICE_LOAD''
 	#DESCRIPCION:	Cargado de los datos para envio SIGEP
 	#AUTOR:		    FRANKLIN ESPINOZA ALVAREZ
 	#FECHA:		    10-01-2024 12:35:55
	***********************************/

	if(p_transaccion=''SIG_SERVICE_LOAD'')then

        begin
            v_document = v_parametros.document;
            egaDocumento = v_document->>''egaDocumento'';
            egaRespaldo = v_document->>''egaRespaldo'';
            egaBeneficiario = v_document->>''egaBeneficiario'';
            egaPartida = v_document->>''egaPartida'';
            egaBoleta = v_document->>''egaBoleta'';
            egaCuentaContable = v_document->>''egaCuentaContable'';
            cuentaLibreta = v_document->>''cuentaLibreta'';
            --raise ''PURISKIRI: %, %'',egaRespaldo->>''nroPreventivo'', egaRespaldo->>''nroPreventivo'' is null;
            --Sentencia de la insercion
            select id_type_service_request into v_id_type_service_request
            from  sigep.ttype_service_request
            where service_code = ''DOC_CREATE_C31'';

        	insert into sigep.tservice_request(
			    id_type_service_request,status,sys_origin,ip_origin,
        	    estado_reg,fecha_reg,id_usuario_reg
			) values(
                v_id_type_service_request,''pending'',''ERP'',''192.168.7.7'',
			    ''activo'',now(),p_id_usuario
			)RETURNING id_service_request into v_id_service_request;

            for v_service in SELECT ssr.id_type_sigep_service_request,ssr.exec_order,ssr.json_main_container,ssr.user_param,ssr.status_group,ssr.sigep_service_name service_name
                            FROM sigep.ttype_sigep_service_request ssr
                            JOIN sigep.ttype_service_request sr ON sr.id_type_service_request = ssr.id_type_service_request
                            WHERE ssr.estado_reg = ''activo'' AND sr.service_code = ''DOC_CREATE_C31''
                            ORDER BY exec_order loop

                if v_service.service_name = ''egaDocumento'' and egaDocumento = ''{}'' then
                    continue;
                elsif v_service.service_name = ''egaRespaldo'' and egaRespaldo = ''{}'' then
                    continue;
                elsif v_service.service_name = ''egaBeneficiario'' and jsonb_array_length( egaBeneficiario ) = 0/*egaBeneficiario = ''{}''*/ then
                    continue;
                elsif v_service.service_name = ''egaPartida'' and jsonb_array_length( egaPartida ) = 0/*egaPartida = ''{}''*/ then
                    continue;
                elsif v_service.service_name = ''egaBoleta'' and egaBoleta = ''{}'' then
                    continue;
                elsif v_service.service_name = ''egaCuentaContable'' and jsonb_array_length( egaCuentaContable ) = 0/*egaCuentaContable = ''{}''*/ then
                    continue;
                elsif v_service.service_name = ''cuentaLibreta'' and cuentaLibreta = ''{}'' then
                    continue;
                end if;

                v_status = case when v_service.exec_order = 1 then ''next_to_execute'' else ''pending'' end;
                if v_service.user_param = ''usuario'' then
                    v_user = v_document->>''user_generate'';
                elsif v_service.user_param = ''user_apro'' then
                    v_user = v_document->>''user_approval'';
                elsif v_service.user_param = ''user_firm'' then
                    v_user = v_document->>''user_signature'';
                end if;

                if v_service.service_name in (''egaBeneficiario'',''egaPartida'',''egaCuentaContable'') then
                    if v_service.service_name in (''egaBeneficiario'') then
                        v_count = jsonb_array_length(egaBeneficiario);
                    elsif v_service.service_name in (''egaPartida'') then
                        v_count = jsonb_array_length(egaPartida);
                    elsif v_service.service_name in (''egaCuentaContable'') then
                        v_count = jsonb_array_length(egaCuentaContable);
                    end if;

                    for v_index in 0..v_count-1 loop
                        --Sentencia de la insercion
                        insert into sigep.tsigep_service_request(
                            id_service_request,id_type_sigep_service_request,
                            status,user_name,exec_order,status_group,
                            estado_reg,id_usuario_reg
                        ) values(
                            v_id_service_request,v_service.id_type_sigep_service_request,
                            v_status,v_user,v_service.exec_order,v_service.status_group,
                            ''activo'',p_id_usuario
                        )RETURNING id_sigep_service_request into v_id_sigep_service_request;

                        for v_params in select id_type_sigep_service_request,sigep_name,erp_name,erp_json_container,ctype,input_output,def_value
                                        from sigep.tparam par
                                        where estado_reg = ''activo'' and par.input_output in (''input'',''revert'')
                                        and par.id_type_sigep_service_request = v_service.id_type_sigep_service_request
                                        and (erp_name is not null or def_value is not null)
                                        order by id_param loop

                            if v_params.sigep_name  = ''gestion'' then
                                update pxp.variable_global set
                                    valor = egaDocumento->>v_params.sigep_name
                                where variable = ''gestion'';
                            end if;

                            if v_params.sigep_name = ''idSolicitud'' then

                                select glo.valor into v_gestion
                                from pxp.variable_global glo
                                where glo.variable = ''gestion'';

                                select cor.numero into v_numero
                                from sigep.tcorrelativo cor
                                where cor.gestion = v_gestion;

                                if v_numero is null then
                                    insert into sigep.tcorrelativo(id_usuario_reg, numero, gestion)
                                    values (1, 1, v_gestion) returning id_correlativo into v_id_correlativo;

                                    select cor.numero into v_numero
                                    from sigep.tcorrelativo cor
                                    where cor.gestion = v_gestion;
                                end if;
                            end if;

                            if v_service.service_name = ''egaBeneficiario'' and jsonb_array_length(egaBeneficiario) > 0 then
                                if ((egaBeneficiario->>v_index)::jsonb)->>v_params.sigep_name is not null then
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else ((egaBeneficiario->>v_index)::jsonb)->>v_params.sigep_name end;
                                else
                                    if v_params.sigep_name = ''gestion'' then
                                        v_value = egaDocumento->>v_params.sigep_name;
                                    else
                                        v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                    end if;
                                end if;
                            elsif v_service.service_name = ''egaPartida'' and jsonb_array_length(egaPartida) > 0 then
                                if ((egaPartida->>v_index)::jsonb)->>v_params.sigep_name is not null then
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else ((egaPartida->>v_index)::jsonb)->>v_params.sigep_name end;
                                else
                                    if v_params.sigep_name = ''gestion'' then
                                        v_value = egaDocumento->>v_params.sigep_name;
                                    else
                                        v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                    end if;
                                end if;
                            elsif v_service.service_name = ''egaCuentaContable'' and jsonb_array_length(egaCuentaContable) > 0 then
                                if ((egaCuentaContable->>v_index)::jsonb)->>v_params.sigep_name is not null then
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else ((egaCuentaContable->>v_index)::jsonb)->>v_params.sigep_name end;
                                else
                                    if v_params.sigep_name = ''gestion'' then
                                        v_value = egaDocumento->>v_params.sigep_name;
                                    else
                                        v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                    end if;
                                end if;
                            end if;

                            --Sentencia de la insercion
                            insert into sigep.trequest_param(
                                id_sigep_service_request,
                                value,ctype,name,input_output,
                                estado_reg,id_usuario_reg
                            ) values(
                                v_id_sigep_service_request,
                                coalesce(v_value,''''),v_params.ctype,v_params.sigep_name,v_params.input_output,
                                ''activo'',p_id_usuario
                            );

                            if  v_params.sigep_name  = ''idSolicitud'' then
                              update sigep.tcorrelativo set
                                  numero = numero + 1
                              where gestion = v_gestion;
                            end if;
                        end loop;
                    end loop;
                else
                    --Sentencia de la insercion
                    insert into sigep.tsigep_service_request(
                        id_service_request,id_type_sigep_service_request,
                        status,user_name,exec_order,status_group,
                        estado_reg,id_usuario_reg
                    ) values(
                        v_id_service_request,v_service.id_type_sigep_service_request,
                        v_status,v_user,v_service.exec_order,v_service.status_group,
                        ''activo'',p_id_usuario
                    )RETURNING id_sigep_service_request into v_id_sigep_service_request;

                    for v_params in select id_type_sigep_service_request,sigep_name,erp_name,erp_json_container,ctype,input_output,def_value
                                    from sigep.tparam par
                                    where estado_reg = ''activo'' and par.input_output in (''input'',''revert'')
                                    and par.id_type_sigep_service_request = v_service.id_type_sigep_service_request
                                    and (erp_name is not null or def_value is not null)
                                    order by id_param loop

                        if v_params.sigep_name  = ''gestion'' then
                            update pxp.variable_global set
                                valor = egaDocumento->>v_params.sigep_name
                            where variable = ''gestion'';
                        end if;

                        if v_params.sigep_name = ''idSolicitud'' then

                            select glo.valor into v_gestion
                            from pxp.variable_global glo
                            where glo.variable = ''gestion'';

                            select cor.numero into v_numero
                            from sigep.tcorrelativo cor
                            where cor.gestion = v_gestion;

                            if v_numero is null then
                                insert into sigep.tcorrelativo(id_usuario_reg, numero, gestion)
                                values (1, 1, v_gestion) returning id_correlativo into v_id_correlativo;

                                select cor.numero into v_numero
                                from sigep.tcorrelativo cor
                                where cor.gestion = v_gestion;
                            end if;
                        end if;

                        if v_service.service_name = ''egaDocumento'' and egaDocumento != ''{}'' then
                            if egaDocumento->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaDocumento->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''egaRespaldo'' and egaRespaldo != ''{}'' then
                            if egaRespaldo->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaRespaldo->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''egaBeneficiario'' and egaBeneficiario != ''{}'' then
                            if egaBeneficiario->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaBeneficiario->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''egaPartida'' and egaPartida != ''{}'' then
                            if egaPartida->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaPartida->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''egaBoleta'' and egaBoleta != ''{}'' then
                            if egaBoleta->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaBoleta->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''egaCuentaContable'' and egaCuentaContable != ''{}'' then
                            if egaCuentaContable->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else egaCuentaContable->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        elsif v_service.service_name = ''cuentaLibreta'' and cuentaLibreta != ''{}'' then
                            if cuentaLibreta->>v_params.sigep_name is not null then
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else cuentaLibreta->>v_params.sigep_name end;
                            else
                                if v_params.sigep_name = ''gestion'' then
                                    v_value = egaDocumento->>v_params.sigep_name;
                                else
                                    v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                                end if;
                            end if;
                        else
                            if v_params.sigep_name = ''gestion'' then
                                v_value = egaDocumento->>v_params.sigep_name;
                            else
                                v_value = case when v_params.sigep_name  = ''idSolicitud'' then v_numero::text else v_params.def_value end;
                            end if;
                        end if;

                        /*if v_value is null then
                            raise ''service_name: %; id_type_sigep_service_request: %; sigep_name: %; middle_name: %;v_value: %; v_id_sigep_service_request: %'',v_service.service_name,v_service.id_type_sigep_service_request,cuentaLibreta->>v_params.sigep_name,v_params.sigep_name,v_value, v_id_sigep_service_request;
                        end if;*/
                        --Sentencia de la insercion
                        insert into sigep.trequest_param(
                            id_sigep_service_request,
                            value,ctype,name,input_output,
                            estado_reg,id_usuario_reg
                        ) values(
                            v_id_sigep_service_request,
                            coalesce(v_value,''''),v_params.ctype,v_params.sigep_name,v_params.input_output,
                            ''activo'',p_id_usuario
                        );

                        if  v_params.sigep_name  = ''idSolicitud'' then
                          update sigep.tcorrelativo set
                              numero = numero + 1
                          where gestion = v_gestion;
                        end if;

                    end loop;
                end if;

        	end loop;
			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Service request almacenado(a) con exito (id_service_request ''||v_id_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_service_request'',v_id_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:   ''SIG_SERVICE_RUN''
 	#DESCRIPCION:	Ejecución del envio de datos SIGEP
 	#AUTOR:		    FRANKLIN ESPINOZA ALVAREZ
 	#FECHA:		    10-01-2024 12:35:55
	***********************************/

	elsif(p_transaccion=''SIG_SERVICE_RUN'')then

		begin


            --Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Param eliminado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_param'',v_parametros.id_param::varchar);

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

ALTER FUNCTION sigep.ft_middleware_ime (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;