CREATE OR REPLACE FUNCTION sigep.f_status_change_c31 (
  p_id_service_request integer,
  p_estado_erp varchar,
  p_estado_sigep varchar
)
RETURNS void LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
PARALLEL UNSAFE
COST 100
AS'
DECLARE

v_consulta    		varchar;
v_registros  		record;
v_nombre_funcion   	text;
v_resp				varchar;
v_status			varchar;

v_firmado			varchar;
BEGIN

    v_nombre_funcion = ''sigep.f_status_change_c31'';

    select tsr.status into v_status
    from sigep.tservice_request tsr
    where tsr.id_service_request = p_id_service_request;

    if p_estado_sigep = ''elaborado'' then

        if v_status = ''fatal_error'' then

            update sigep.tservice_request set
            	status = ''pending''
            where id_service_request = p_id_service_request;

            update sigep.tsigep_service_request tsr set
                status = ''success''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''egaDocumento'',''egaPartida'',''egaRespaldo'',''egaBeneficiario'',''egaCuentaContable'',''cuentaLibreta'')
            and tsr.id_service_request = p_id_service_request;

            update sigep.tsigep_service_request tsr set
                status = ''canceled''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''verificaDoc'',''apruebaDoc'',''firmaDoc'') and tsr.id_service_request = p_id_service_request;

        end if;

    elsif p_estado_sigep = ''verificado'' then

    	if v_status in (''fatal_error'', ''error'') then

            update sigep.tservice_request set
            	status = ''pending''
            where id_service_request = p_id_service_request;

            update sigep.tsigep_service_request tsr set
                status = ''success''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''egaDocumento'',''egaPartida'',''egaRespaldo'',''egaBeneficiario'',''egaCuentaContable'',''cuentaLibreta'')
            and tsr.id_service_request = p_id_service_request;

        	update sigep.tsigep_service_request tsr set
                status = ''success''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''verificaDoc'') and tsr.id_service_request = p_id_service_request;

            update sigep.tsigep_service_request tsr set
                status = ''canceled''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''apruebaDoc'',''firmaDoc'') and tsr.id_service_request = p_id_service_request;

        end if;

    elsif p_estado_sigep = ''aprobado'' then

    	if v_status in (''fatal_error'', ''error'') then

            update sigep.tservice_request set
            	status = ''pending''
            where id_service_request = p_id_service_request;

            update sigep.tsigep_service_request tsr set
                status = ''success''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''egaDocumento'',''egaPartida'',''egaRespaldo'',''egaBeneficiario'',''egaCuentaContable'',''cuentaLibreta'',''verificaDoc'')
            and tsr.id_service_request = p_id_service_request;

        	update sigep.tsigep_service_request tsr set
                status = ''success''
            from sigep.ttype_sigep_service_request tts
            where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
            and tts.sigep_service_name in (''apruebaDoc'') and tsr.id_service_request = p_id_service_request;

            select tsr.status into v_firmado
            from sigep.tsigep_service_request tsr
            inner join sigep.ttype_sigep_service_request tts on tts.id_type_sigep_service_request = tsr.id_type_sigep_service_request
            where tts.sigep_service_name in (''firmaDoc'') and tsr.id_service_request = p_id_service_request;

            if v_firmado is not null then
              update sigep.tsigep_service_request tsr set
                  status = ''canceled''
              from sigep.ttype_sigep_service_request tts
              where tsr.id_type_sigep_service_request =  tts.id_type_sigep_service_request
              and tts.sigep_service_name in (''firmaDoc'')
              and tsr.id_service_request = p_id_service_request;
            else
                update sigep.tservice_request set
            	    status = ''success''
                where id_service_request = p_id_service_request;
            end if;
        end if;

    elsif p_estado_sigep = ''firmado'' then

    	select tsr.status into v_status
        from sigep.tservice_request tsr
        where tsr.id_service_request = p_id_service_request;

        if v_status in (''fatal_error'', ''error'') then
        	 update sigep.tservice_request set
            	status = ''success''
            where id_service_request = p_id_service_request;

            update sigep.tsigep_service_request set
              status = ''success''
            where id_service_request = p_id_service_request;
        end if;

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

ALTER FUNCTION sigep.f_status_change_c31 (p_id_service_request integer, p_estado_erp varchar, p_estado_sigep varchar)
  OWNER TO postgres;