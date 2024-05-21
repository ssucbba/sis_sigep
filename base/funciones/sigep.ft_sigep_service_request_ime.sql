CREATE OR REPLACE FUNCTION sigep.ft_sigep_service_request_ime (
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
 FUNCION: 		sigep.ft_sigep_service_request_ime
 DESCRIPCION:   Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tsigep_service_request''
 AUTOR: 		 (admin)
 FECHA:	        27-12-2018 12:23:23
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				27-12-2018 12:23:23								Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tsigep_service_request''
 #
 ***************************************************************************/

DECLARE

	v_nro_requerimiento    	integer;
	v_parametros           	record;
	v_id_requerimiento     	integer;
	v_resp		            varchar;
	v_nombre_funcion        text;
	v_mensaje_error         text;
	v_id_sigep_service_request	integer;
	v_service		    	record;
	v_registros		    	record;
	v_exec_order			integer;
	v_cancelar_servicio     varchar;
	v_names_output      	varchar[];
	v_values_output     	varchar[];
    v_method_type			varchar;

    --franklin.espinoza 20/08/20202
    v_sigep_service_name    varchar;
    v_total_services		integer;
    v_end_process			boolean=true;
    v_status_group 			varchar;

    v_orden_sigep			integer;
BEGIN

    v_nombre_funcion = ''sigep.ft_sigep_service_request_ime'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:  ''SIG_SSR_INS''
 	#DESCRIPCION:	Insercion de registros
 	#AUTOR:		admin
 	#FECHA:		27-12-2018 12:23:23
	***********************************/

	if(p_transaccion=''SIG_SISERE_INS'')then

        select tssr.status_group
        into v_status_group
        from sigep.ttype_sigep_service_request tssr
        where tssr.id_type_sigep_service_request = v_parametros.id_type_sigep_service_request;

        begin
        	--Sentencia de la insercion
        	insert into sigep.tsigep_service_request(
			id_service_request,
			id_type_sigep_service_request,
			estado_reg,
			status,
			user_name,
			exec_order,
			id_usuario_reg,
            status_group
          	) values(
			v_parametros.id_service_request,
			v_parametros.id_type_sigep_service_request,
			''activo'',
			v_parametros.status,
			v_parametros.user_name,
			v_parametros.exec_order,
			p_id_usuario,
			v_status_group
			)RETURNING id_sigep_service_request into v_id_sigep_service_request;

			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request almacenado(a) con exito (id_sigep_service_request''||v_id_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_sigep_service_request'',v_id_sigep_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

    /*********************************
 	#TRANSACCION:  ''SIG_SISERE_UPD''
 	#DESCRIPCION:	Modificacion de registros
 	#AUTOR:		admin
 	#FECHA:		27-12-2018 13:10:13
	***********************************/

	elsif(p_transaccion=''SIG_SISERE_UPD'')then

		begin
			--Sentencia de la modificacion
			update sigep.tsigep_service_request set
              	/*id_type_sigep_service_request = v_parametros.id_type_sigep_service_request,
              	date_queue_sent = v_parametros.date_queue_sent,
              	date_request_sent = v_parametros.date_request_sent,
              	last_message = v_parametros.last_message,
            	sigep_service_name = v_parametros.sigep_service_name,
                last_message_revert = v_parametros.last_message_revert,
                user_name = v_parametros.user_name,
                queue_id = v_parametros.queue_id,
                queue_revert_id = v_parametros.queue_revert_id,*/

                status = v_parametros.status,

              	id_usuario_mod = p_id_usuario/*,
              	fecha_mod = now(),
              	id_usuario_ai = v_parametros._id_usuario_ai,
              	usuario_ai = v_parametros._nombre_usuario_ai*/
			where id_sigep_service_request=v_parametros.id_sigep_service_request;

			--Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request modificado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_sigep_service_request'',v_parametros.id_sigep_service_request::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_SISERROR_UPD''
 	#DESCRIPCION:	Registro de error
 	#AUTOR:		admin
 	#FECHA:		27-12-2018 12:23:23
	***********************************/
	elsif (p_transaccion=''SIG_SISERROR_UPD'')then
    	begin
        	select * into v_service
        	from sigep.tsigep_service_request
        	where id_sigep_service_request = v_parametros.id_sigep_service_request;

            select tssr.exec_order, tssr.status_group
            into v_orden_sigep, v_status_group
            from sigep.tsigep_service_request tss
            inner join sigep.ttype_sigep_service_request tssr on tssr.id_type_sigep_service_request = tss.id_type_sigep_service_request
        	where id_sigep_service_request = v_parametros.id_sigep_service_request;

        	v_cancelar_servicio = ''no'';
        	--cancelar request posteriores y revertir previos para el caso de cola de procesamiento normal
        	if (v_service.status IN (''next_to_execute'',''pending_queue'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue'' )) then
        			v_cancelar_servicio = ''si'';
        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending'',''next_to_execute'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;
        			--revertir anteriores exitosos
        			for v_registros in (	select id_type_sigep_service_request,ssr.exec_order
        									from sigep.tsigep_service_request ssr
        									where ssr.id_service_request = v_service.id_service_request and ssr.estado_reg = ''activo''
        										and status = ''success''
        									group by id_type_sigep_service_request,ssr.exec_order
        									order by ssr.exec_order DESC )loop
                    	if /*v_orden_sigep < 6 and*/ v_status_group != ''pass'' then

                        	update 	sigep.tsigep_service_request
                        	set status = (case when v_exec_order = 1 then ''next_to_revert'' else ''pending_revert'' end),
                          	exec_order = v_exec_order
                          	where 	v_registros.id_type_sigep_service_request = id_type_sigep_service_request and
	        				v_service.id_service_request = id_service_request and status = ''success'';

                        end if;

	        			v_exec_order = v_exec_order + 1;
	        		end loop;
        		end if;

        		--actualziar status service
        		update sigep.tservice_request
        		set status = (case when v_parametros.fatal = ''si'' then ''fatal_error'' else ''error'' end),
        		last_message = v_parametros.error
        		where id_service_request = v_service.id_service_request;

        		--actualizar status sigep service
    			update 	sigep.tsigep_service_request
    			set status = (case when v_parametros.fatal = ''si'' then ''fatal_error'' else ''error'' end),
        		last_message = v_parametros.error,
        		date_request_sent = now(),
                type_error = v_parametros.type_error
    			where id_sigep_service_request = v_service.id_sigep_service_request;
        	end if;

        	--cancelar request posteriores para el caso de cola de procesamiento reversion
        	if (v_service.status IN (''next_to_revert'',''pending_queue_revert'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue_revert'' )) then

        			v_cancelar_servicio = ''si'';
        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending_revert'',''next_to_revert'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;

                    --begin franklin.espinoza verificar si sucede un error fatal en  servicio de reversion
                	v_end_process = false;
        		end if;

        		--actualziar status service
        		update sigep.tservice_request
        		set status = (case when v_parametros.fatal = ''si'' then ''fatal_error_revert'' else ''error_revert'' end),
        		last_message = v_parametros.error
        		where id_service_request = v_service.id_service_request;

        		--actualizar status sigep service
    			update 	sigep.tsigep_service_request
    			set status = (case when v_parametros.fatal = ''si'' then ''fatal_error_revert'' else ''error_revert'' end),
        		last_message_revert = v_parametros.error,
        		date_request_sent = now(),
                type_error = v_parametros.type_error
    			where id_sigep_service_request = v_service.id_sigep_service_request;

        	end if;



			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request almacenado(a) con exito (id_sigep_service_request''||v_id_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''cancelar_servicio'',v_cancelar_servicio::varchar);
            --franklin.espinoza
            v_resp = pxp.f_agrega_clave(v_resp,''end_process'',v_end_process::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_SISSUCC_UPD''
 	#DESCRIPCION:	Registro de exito
 	#AUTOR:		admin
 	#FECHA:		27-12-2018 12:23:23
	***********************************/
	elsif (p_transaccion=''SIG_SISSUCC_UPD'')then
    	begin
        	select sr.status sr_status,sr.id_type_service_request,ssr.* into v_service
        	from sigep.tsigep_service_request ssr
        	inner join sigep.tservice_request sr on sr.id_service_request = ssr.id_service_request
        	where ssr.id_sigep_service_request = v_parametros.id_sigep_service_request;

        	--cancelar request posteriores y revertir previos para el caso de cola de procesamiento normal
        	if (v_service.status IN (''next_to_execute'',''pending_queue'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue'' ) and v_service.sr_status in (''error'',''fatal_error'',''fatal_error_revert'',''error_revert'')) then

        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending'',''next_to_execute'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;
        			--revertir anteriores exitosos
        			for v_registros in (	select tssr.*
        									from sigep.tservice_request sr
        									inner join sigep.ttype_sigep_service_request tssr
        											on sr.id_type_service_request = tssr.id_type_service_request
        									where sr.id_service_request = v_service.id_service_request and tssr.estado_reg = ''activo''
        									order by tssr.exec_order DESC )loop
        				update 	sigep.tsigep_service_request
	        			set status = (case when v_exec_order = 1 then ''next_to_revert'' else ''pending_revert'' end),
	        			exec_order = v_exec_order
	        			where 	v_registros.id_type_sigep_service_request = id_sigep_service_request and --observado
	        					v_service.id_service_request = id_service_request;

	        			v_exec_order = v_exec_order + 1;
	        		end loop;
        		end if;
        	end if;
        ------------rzabala---------------
            /*select tssr.method_type
            into v_method_type
            from sigep.tservice_request sr
            inner join sigep.ttype_sigep_service_request tssr on sr.id_type_service_request = tssr.id_type_service_request
			where sr.id_service_request = v_service.id_service_request and tssr.estado_reg = ''activo'';*/

            select tssr.method_type, tssr.sigep_service_name
            into v_method_type, v_sigep_service_name
            from sigep.tsigep_service_request sr
            inner join sigep.ttype_sigep_service_request tssr on sr.id_type_sigep_service_request = tssr.id_type_sigep_service_request
            where sr.id_sigep_service_request = v_parametros.id_sigep_service_request and tssr.estado_reg = ''activo'';
        -----------------------------------
        	--actualizar status sigep service
            ----descomentar para probar el GET en next_to_execute
            if v_service.status IN (''pending_queue'') then
              update 	sigep.tsigep_service_request
              set status = (case  when v_service.status IN (''next_to_execute'') and v_method_type IN (''GET'') then ''success''
                                  when v_service.status IN (''next_to_execute'') then ''pending_queue''
                                  when v_service.status IN (''pending_queue'') then ''success''
                                  when v_service.status IN (''next_to_revert'') and v_sigep_service_name = ''egaDocumento'' then ''pending_queue_revert''
                                  else ''success_revert'' end),
                  --date_queue_sent = now(),
                  date_queue_finalized = now()
              where id_sigep_service_request = v_service.id_sigep_service_request;
        	else

              update 	sigep.tsigep_service_request
              set status = (case  when v_service.status IN (''next_to_execute'') and v_method_type IN (''GET'') then ''success''
                                  when v_service.status IN (''next_to_execute'') then ''pending_queue''
                                  when v_service.status IN (''pending_queue'') then ''success''
                                  when v_service.status IN (''next_to_revert'') and v_sigep_service_name = ''egaDocumento'' then ''pending_queue_revert''
                                  else ''success_revert'' end),
                  date_request_sent = now()
              where id_sigep_service_request = v_service.id_sigep_service_request;

            end if;


            --begin franklin.espinoza verificar si es el ultimo servicio con status pending_queue o
            select count(tssr.id_sigep_service_request)
            into v_total_services
            from sigep.tsigep_service_request tssr
            where tssr.id_service_request = v_service.id_service_request;

            if v_service.exec_order = v_total_services and v_service.status = ''pending_queue'' then
            	v_end_process = false;
            end if;

            if 1 = v_total_services and v_service.status = ''next_to_execute'' then
            	v_end_process = false;
            end if;

            --end franklin.espinoza

			--actualizar status de procesos siguientes si existen y si corresponde
            --if v_sigep_service_name = ''''
			if (not exists(select 1
    						from sigep.tsigep_service_request
    						where id_service_request = v_service.id_service_request and
    						status not in (''success'',''success_revert'') and exec_order = v_service.exec_order )) then
    			update sigep.tsigep_service_request
    			set status = (case when v_service.status = ''pending_queue'' then ''next_to_execute'' else ''next_to_revert'' end)
    			where id_service_request = v_service.id_service_request and exec_order = v_service.exec_order + 1
                ---añadido
                and status in (''pending'',''pending_revert'')
                --frannklin.espinoza 21/09/2020
                and status_group = ''new'';
    		end if;

        	--actualizar service request si corresponde
        	if (not exists( select 1
    						from sigep.tsigep_service_request
    						where id_service_request = v_service.id_service_request and
    						status != ''success'' )) then
    			update 	sigep.tservice_request
				set status = ''success'',
				date_finished = now()
				where id_service_request = v_service.id_service_request;

    		end if;

    		v_names_output = string_to_array(v_parametros.names_output, ''||'');
			v_values_output = string_to_array(v_parametros.values_output, ''||'');

    		--actualizar id colas
            --aqui se puede incluir la condicion de get
    		if (v_method_type != ''GET'' and v_service.status IN (''next_to_execute'',''next_to_revert'')) then
    			if (v_service.status in (''next_to_revert'')) then
    				update sigep.tsigep_service_request
    				--set queue_revert_id = split_part(v_values_output[1],''/'',5)
                    set queue_revert_id = v_values_output[1]
    				where id_sigep_service_request = v_service.id_sigep_service_request;
    			else
    				update sigep.tsigep_service_request
    				--set queue_id = split_part(v_values_output[1],''/'',5) //modificaciones sept/2019
                    set queue_id = v_values_output[1]
    				where id_sigep_service_request = v_service.id_sigep_service_request;
    			end if;

    		else
	    		--procesar parametros output


				FOR i IN 1.. array_upper(v_names_output, 1)
				LOOP
					IF (exists (select 1
								from sigep.tparam
								where input_output = ''output'' and
									id_type_sigep_service_request = v_service.id_type_sigep_service_request and
									sigep_name = v_names_output[i])) then
						--insert
						--Sentencia de la insercion
                        --raise notice ''llega hasta el for:'';
                        --raise exception ''valor : %'', ''=>''||v_names_output[i];
			        	insert into sigep.trequest_param(
						id_sigep_service_request,
						value,
						ctype,
						name,
						estado_reg,
						id_usuario_reg,
						input_output
			          	) values(
						v_service.id_sigep_service_request,
						v_values_output[i],
						''VARCHAR'',
						v_names_output[i],
						''activo'',
						p_id_usuario,
						''output''

						);
					end if;

					for v_registros in (	select tssr.*,p.ctype,p.input_output,ssr.id_sigep_service_request
	    									from sigep.tsigep_service_request ssr
                                            inner join sigep.ttype_sigep_service_request tssr on ssr.id_type_sigep_service_request=tssr.id_type_sigep_service_request
	    									inner join sigep.tparam p
	    											on p.id_type_sigep_service_request = tssr.id_type_sigep_service_request
	    									where 	tssr.id_type_service_request = v_service.id_type_service_request and ssr.id_service_request = v_service.id_service_request and
	    											p.estado_reg = ''activo'' and p.input_output in(''input'',''revert'') and
	    											p.sigep_name = v_names_output[i] and (p.def_value is null or p.def_value = '''') and (p.erp_name is null or p.erp_name = ''''))loop

	        			/*if (not exists (select 1
										from sigep.trequest_param rp
										inner join sigep.tsigep_service_request ssr  on rp.id_sigep_service_request = ssr. id_sigep_service_request
										where input_output = v_registros.input_output and
												ssr.id_type_sigep_service_request = v_registros.id_type_sigep_service_request and
												rp.name = v_names_output[i]
                                                and ssr.id_service_request = v_service.id_service_request)) then
	        				*/

	        				--insert
	        				--Sentencia de la insercion
				        	insert into sigep.trequest_param(
								id_sigep_service_request,
								value,
								ctype,
								name,
								estado_reg,
								id_usuario_reg,
								input_output
				          	) values(
								v_registros.id_sigep_service_request,
								v_values_output[i],
								v_registros.ctype,
								v_names_output[i],
								''activo'',
								p_id_usuario,
								v_registros.input_output

							);
	        			--end if;

	        		end loop;
				END LOOP;
			end if;


			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request almacenado(a) con exito (id_sigep_service_request''||v_id_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_sigep_service_request'',v_service.id_sigep_service_request::varchar);
            --franklin.espinoza
            v_resp = pxp.f_agrega_clave(v_resp,''end_process'',v_end_process::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;
    /*******************************************************************************************/
    /*********************************
 	#TRANSACCION:  ''SIG_SISERROR_VB_UPD''
 	#DESCRIPCION:	Registro de error
 	#AUTOR:		franklin.espinoza
 	#FECHA:		01-12-2020 08:07:00
	***********************************/
	elsif (p_transaccion=''SIG_SISERROR_VB_UPD'')then
    	begin
        	select * into v_service
        	from sigep.tsigep_service_request
        	where id_sigep_service_request = v_parametros.id_sigep_service_request;

        	v_cancelar_servicio = ''no'';
        	--cancelar request posteriores y revertir previos para el caso de cola de procesamiento normal
        	if (v_service.status IN (''next_to_execute'',''pending_queue'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue'' )) then
        			v_cancelar_servicio = ''si'';
        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending'',''next_to_execute'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;
        			--revertir anteriores exitosos
        			for v_registros in (	select id_type_sigep_service_request,ssr.exec_order
        									from sigep.tsigep_service_request ssr
        									where ssr.id_service_request = v_service.id_service_request and ssr.estado_reg = ''activo''
        										and status = ''success''
        									group by id_type_sigep_service_request,ssr.exec_order
        									order by ssr.exec_order DESC )loop
        				update 	sigep.tsigep_service_request
	        			set status = (case when v_exec_order = 1 then ''next_to_revert'' else ''pending_revert'' end),
	        			exec_order = v_exec_order
	        			where 	v_registros.id_type_sigep_service_request = id_type_sigep_service_request and
	        					v_service.id_service_request = id_service_request and status = ''success'';

	        			v_exec_order = v_exec_order + 1;
	        		end loop;
        		end if;

        		--actualziar status service
        		update sigep.tservice_request
        		set status = (case when v_parametros.fatal = ''si'' then ''fatal_error'' else ''error'' end),
        		last_message = v_parametros.error
        		where id_service_request = v_service.id_service_request;

        		--actualizar status sigep service
    			update 	sigep.tsigep_service_request
    			set status = (case when v_parametros.fatal = ''si'' then ''fatal_error'' else ''error'' end),
        		last_message = v_parametros.error,
        		date_request_sent = now(),
                type_error = v_parametros.type_error
    			where id_sigep_service_request = v_service.id_sigep_service_request;
        	end if;

        	--cancelar request posteriores para el caso de cola de procesamiento reversion
        	if (v_service.status IN (''next_to_revert'',''pending_queue_revert'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue_revert'' )) then

        			v_cancelar_servicio = ''si'';
        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending_revert'',''next_to_revert'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;

                    --begin franklin.espinoza verificar si sucede un error fatal en  servicio de reversion
                	v_end_process = false;
        		end if;

        		--actualziar status service
        		update sigep.tservice_request
        		set status = (case when v_parametros.fatal = ''si'' then ''fatal_error_revert'' else ''error_revert'' end),
        		last_message = v_parametros.error
        		where id_service_request = v_service.id_service_request;

        		--actualizar status sigep service
    			update 	sigep.tsigep_service_request
    			set status = (case when v_parametros.fatal = ''si'' then ''fatal_error_revert'' else ''error_revert'' end),
        		last_message_revert = v_parametros.error,
        		date_request_sent = now(),
                type_error = v_parametros.type_error
    			where id_sigep_service_request = v_service.id_sigep_service_request;

        	end if;



			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request almacenado(a) con exito (id_sigep_service_request''||v_id_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''cancelar_servicio'',v_cancelar_servicio::varchar);
            --franklin.espinoza
            v_resp = pxp.f_agrega_clave(v_resp,''end_process'',v_end_process::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_SISSUCC_VB_UPD''
 	#DESCRIPCION:	Registro de exito
 	#AUTOR:		franklin.espinoza
 	#FECHA:		01-12-2020 08:07:00
	***********************************/
	elsif (p_transaccion=''SIG_SISSUCC_VB_UPD'')then
    	begin
        	select sr.status sr_status,sr.id_type_service_request,ssr.* into v_service
        	from sigep.tsigep_service_request ssr
        	inner join sigep.tservice_request sr on sr.id_service_request = ssr.id_service_request
        	where ssr.id_sigep_service_request = v_parametros.id_sigep_service_request;

        	--cancelar request posteriores y revertir previos para el caso de cola de procesamiento normal
        	if (v_service.status IN (''next_to_execute'',''pending_queue'')) then
        		if (not exists( select 1
        						from sigep.tsigep_service_request
        						where id_service_request = v_service.id_service_request and
        						id_sigep_service_request != v_service.id_sigep_service_request and
        						status = ''pending_queue'' ) and v_service.sr_status in (''error'',''fatal_error'',''fatal_error_revert'',''error_revert'')) then

        			--cancelar posteriores
        			update 	sigep.tsigep_service_request
        			set status = ''canceled''
        			where id_service_request = v_service.id_service_request and status in (''pending'',''next_to_execute'') AND
        					id_sigep_service_request != v_service.id_sigep_service_request;
        			v_exec_order = 1;
        			--revertir anteriores exitosos
        			for v_registros in (	select tssr.*
        									from sigep.tservice_request sr
        									inner join sigep.ttype_sigep_service_request tssr
        											on sr.id_type_service_request = tssr.id_type_service_request
        									where sr.id_service_request = v_service.id_service_request and tssr.estado_reg = ''activo''
        									order by tssr.exec_order DESC )loop
        				update 	sigep.tsigep_service_request
	        			set status = (case when v_exec_order = 1 then ''next_to_revert'' else ''pending_revert'' end),
	        			exec_order = v_exec_order
	        			where 	v_registros.id_type_sigep_service_request = id_sigep_service_request and --observado
	        					v_service.id_service_request = id_service_request;

	        			v_exec_order = v_exec_order + 1;
	        		end loop;
        		end if;
        	end if;
        ------------rzabala---------------
            /*select tssr.method_type
            into v_method_type
            from sigep.tservice_request sr
            inner join sigep.ttype_sigep_service_request tssr on sr.id_type_service_request = tssr.id_type_service_request
			where sr.id_service_request = v_service.id_service_request and tssr.estado_reg = ''activo'';*/

            select tssr.method_type, tssr.sigep_service_name
            into v_method_type, v_sigep_service_name
            from sigep.tsigep_service_request sr
            inner join sigep.ttype_sigep_service_request tssr on sr.id_type_sigep_service_request = tssr.id_type_sigep_service_request
            where sr.id_sigep_service_request = v_parametros.id_sigep_service_request and tssr.estado_reg = ''activo'';
        -----------------------------------
        	--actualizar status sigep service
            ----descomentar para probar el GET en next_to_execute
            if v_service.status IN (''pending_queue'') then
              update 	sigep.tsigep_service_request
              set status = (case  when v_service.status IN (''next_to_execute'') and v_method_type IN (''GET'') then ''success''
                                  when v_service.status IN (''next_to_execute'') then ''pending_queue''
                                  when v_service.status IN (''pending_queue'') then ''success''
                                  when v_service.status IN (''next_to_revert'') and v_sigep_service_name = ''egaDocumento'' then ''pending_queue_revert''
                                  else ''success_revert'' end),
                  date_queue_sent = now()
              where id_sigep_service_request = v_service.id_sigep_service_request;
        	else

              update 	sigep.tsigep_service_request
              set status = (case  when v_service.status IN (''next_to_execute'') and v_method_type IN (''GET'') then ''success''
                                  when v_service.status IN (''next_to_execute'') then ''pending_queue''
                                  when v_service.status IN (''pending_queue'') then ''success''
                                  when v_service.status IN (''next_to_revert'') and v_sigep_service_name = ''egaDocumento'' then ''pending_queue_revert''
                                  else ''success_revert'' end),
                  date_request_sent = now()
              where id_sigep_service_request = v_service.id_sigep_service_request;

            end if;


            --begin franklin.espinoza verificar si es el ultimo servicio con status pending_queue o
            select count(tssr.id_sigep_service_request)
            into v_total_services
            from sigep.tsigep_service_request tssr
            where tssr.id_service_request = v_service.id_service_request;

            if v_service.exec_order = v_total_services and v_service.status = ''pending_queue'' then
            	v_end_process = false;
            end if;

            if 1 = v_total_services and v_service.status = ''next_to_execute'' then
            	v_end_process = false;
            end if;

            --end franklin.espinoza

			--actualizar status de procesos siguientes si existen y si corresponde
            --if v_sigep_service_name = ''''
			if (not exists(select 1
    						from sigep.tsigep_service_request
    						where id_service_request = v_service.id_service_request and
    						status not in (''success'',''success_revert'') and exec_order = v_service.exec_order )) then
    			update sigep.tsigep_service_request
    			set status = (case when v_service.status = ''pending_queue'' then ''next_to_execute'' else ''next_to_revert'' end)
    			where id_service_request = v_service.id_service_request and exec_order = v_service.exec_order + 1
                ---añadido
                and status in (''pending'',''pending_revert'')
                --frannklin.espinoza 21/09/2020
                and status_group = ''new'';
    		end if;

        	--actualizar service request si corresponde
        	if (not exists( select 1
    						from sigep.tsigep_service_request
    						where id_service_request = v_service.id_service_request and
    						status != ''success'' )) then
    			update 	sigep.tservice_request
				set status = ''success'',
				date_finished = now()
				where id_service_request = v_service.id_service_request;

    		end if;

    		v_names_output = string_to_array(v_parametros.names_output, ''||'');
			v_values_output = string_to_array(v_parametros.values_output, ''||'');

    		--actualizar id colas
            --aqui se puede incluir la condicion de get
    		if (v_method_type != ''GET'' and v_service.status IN (''next_to_execute'',''next_to_revert'')) then
    			if (v_service.status in (''next_to_revert'')) then
    				update sigep.tsigep_service_request
    				--set queue_revert_id = split_part(v_values_output[1],''/'',5)
                    set queue_revert_id = v_values_output[1]
    				where id_sigep_service_request = v_service.id_sigep_service_request;
    			else
    				update sigep.tsigep_service_request
    				--set queue_id = split_part(v_values_output[1],''/'',5) //modificaciones sept/2019
                    set queue_id = v_values_output[1]
    				where id_sigep_service_request = v_service.id_sigep_service_request;
    			end if;

    		else
	    		--procesar parametros output


				FOR i IN 1.. array_upper(v_names_output, 1)
				LOOP
					IF (exists (select 1
								from sigep.tparam
								where input_output = ''output'' and
									id_type_sigep_service_request = v_service.id_type_sigep_service_request and
									sigep_name = v_names_output[i])) then
						--insert
						--Sentencia de la insercion
                        --raise notice ''llega hasta el for:'';
                        --raise exception ''valor : %'', ''=>''||v_names_output[i];
			        	insert into sigep.trequest_param(
						id_sigep_service_request,
						value,
						ctype,
						name,
						estado_reg,
						id_usuario_reg,
						input_output
			          	) values(
						v_service.id_sigep_service_request,
						v_values_output[i],
						''VARCHAR'',
						v_names_output[i],
						''activo'',
						p_id_usuario,
						''output''

						);
					end if;

					for v_registros in (	select tssr.*,p.ctype,p.input_output,ssr.id_sigep_service_request
	    									from sigep.tsigep_service_request ssr
                                            inner join sigep.ttype_sigep_service_request tssr on ssr.id_type_sigep_service_request=tssr.id_type_sigep_service_request
	    									inner join sigep.tparam p
	    											on p.id_type_sigep_service_request = tssr.id_type_sigep_service_request
	    									where 	tssr.id_type_service_request = v_service.id_type_service_request and ssr.id_service_request = v_service.id_service_request and
	    											p.estado_reg = ''activo'' and p.input_output in(''input'',''revert'') and
	    											p.sigep_name = v_names_output[i] and (p.def_value is null or p.def_value = '''') and (p.erp_name is null or p.erp_name = ''''))loop

	        			/*if (not exists (select 1
										from sigep.trequest_param rp
										inner join sigep.tsigep_service_request ssr  on rp.id_sigep_service_request = ssr. id_sigep_service_request
										where input_output = v_registros.input_output and
												ssr.id_type_sigep_service_request = v_registros.id_type_sigep_service_request and
												rp.name = v_names_output[i]
                                                and ssr.id_service_request = v_service.id_service_request)) then
	        				*/

	        				--insert
	        				--Sentencia de la insercion
				        	insert into sigep.trequest_param(
								id_sigep_service_request,
								value,
								ctype,
								name,
								estado_reg,
								id_usuario_reg,
								input_output
				          	) values(
								v_registros.id_sigep_service_request,
								v_values_output[i],
								v_registros.ctype,
								v_names_output[i],
								''activo'',
								p_id_usuario,
								v_registros.input_output

							);
	        			--end if;

	        		end loop;
				END LOOP;
			end if;


			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''Sigep Service Request almacenado(a) con exito (id_sigep_service_request''||v_id_sigep_service_request||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_sigep_service_request'',v_service.id_sigep_service_request::varchar);
            --franklin.espinoza
            v_resp = pxp.f_agrega_clave(v_resp,''end_process'',v_end_process::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;
    /*******************************************************************************************/

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

ALTER FUNCTION sigep.ft_sigep_service_request_ime (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;