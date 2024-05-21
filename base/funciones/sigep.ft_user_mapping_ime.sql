CREATE OR REPLACE FUNCTION sigep.ft_user_mapping_ime (
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
 FUNCION: 		sigep.ft_user_mapping_ime
 DESCRIPCION:   Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tuser_mapping''
 AUTOR: 		 (admin)
 FECHA:	        08-04-2018 11:04:46
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				08-04-2018 11:04:46								Funcion que gestiona las operaciones basicas (inserciones, modificaciones, eliminaciones de la tabla ''sigep.tuser_mapping''
 #
 ***************************************************************************/

DECLARE

	v_nro_requerimiento    	integer;
	v_parametros           	record;
	v_id_requerimiento     	integer;
	v_resp		            varchar;
	v_nombre_funcion        text;
	v_mensaje_error         text;
	v_id_user_mapping	integer;
    v_sigep_user			varchar;

BEGIN

    v_nombre_funcion = ''sigep.ft_user_mapping_ime'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:  ''SIG_USM_INS''
 	#DESCRIPCION:	Insercion de registros
 	#AUTOR:		admin
 	#FECHA:		08-04-2018 11:04:46
	***********************************/

	if(p_transaccion=''SIG_USM_INS'')then

        begin
        	--Sentencia de la insercion
        	insert into sigep.tuser_mapping(
			sigep_user,
			estado_reg,
			pxp_user,
			id_usuario_ai,
			id_usuario_reg,
			fecha_reg,
			usuario_ai,
			id_usuario_mod,
			fecha_mod
          	) values(
			v_parametros.sigep_user,
			''activo'',
			v_parametros.pxp_user,
			v_parametros._id_usuario_ai,
			p_id_usuario,
			now(),
			v_parametros._nombre_usuario_ai,
			null,
			null



			)RETURNING id_user_mapping into v_id_user_mapping;

			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''User Mapping almacenado(a) con exito (id_user_mapping''||v_id_user_mapping||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_user_mapping'',v_id_user_mapping::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_USM_MOD''
 	#DESCRIPCION:	Modificacion de registros
 	#AUTOR:		admin
 	#FECHA:		08-04-2018 11:04:46
	***********************************/

	elsif(p_transaccion=''SIG_USM_MOD'')then

		begin
			--Sentencia de la modificacion
			update sigep.tuser_mapping set
			sigep_user = v_parametros.sigep_user,
			pxp_user = v_parametros.pxp_user,
			id_usuario_mod = p_id_usuario,
			fecha_mod = now(),
			id_usuario_ai = v_parametros._id_usuario_ai,
			usuario_ai = v_parametros._nombre_usuario_ai
			where id_user_mapping=v_parametros.id_user_mapping;

			--Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''User Mapping modificado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_user_mapping'',v_parametros.id_user_mapping::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_USM_ELI''
 	#DESCRIPCION:	Eliminacion de registros
 	#AUTOR:		admin
 	#FECHA:		08-04-2018 11:04:46
	***********************************/

	elsif(p_transaccion=''SIG_USM_ELI'')then

		begin
			--Sentencia de la eliminacion
			delete from sigep.tuser_mapping
            where id_user_mapping=v_parametros.id_user_mapping;

            --Definicion de la respuesta
            v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''User Mapping eliminado(a)'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_user_mapping'',v_parametros.id_user_mapping::varchar);

            --Devuelve la respuesta
            return v_resp;

		end;
    /*********************************
 	#TRANSACCION:  ''SIG_INITOK_MOD''
 	#DESCRIPCION:	Refresh token
 	#AUTOR:		admin
 	#FECHA:		08-04-2018 11:04:46
	***********************************/

	elsif(p_transaccion=''SIG_INITOK_MOD'')then

		begin
        	v_sigep_user =  split_part(v_parametros.refresh_token, '':'', 1);

            if exists (
            		select 1
                    from sigep.tuser_mapping
                    where sigep_user = v_sigep_user) THEN
        		update sigep.tuser_mapping
                set access_token = v_parametros.access_token,
                	refresh_token = v_parametros.refresh_token,
                    expires_in = v_parametros.expires_in,
                    authorization_code = v_parametros.authorization_code,
                    date_issued_rt = now(),
                    date_issued_at = now(),
                    id_usuario_mod = p_id_usuario,
                    fecha_mod = now()
                where sigep_user = v_sigep_user;
            else
                insert into sigep.tuser_mapping(
                estado_reg,
                id_usuario_ai,
                id_usuario_reg,
                fecha_reg,
                usuario_ai,
                id_usuario_mod,
                fecha_mod,
                date_issued_at,
                date_issued_rt,
                refresh_token,
                access_token,
                authorization_code,
                expires_in,
                sigep_user
                ) values(
                ''activo'',
                v_parametros._id_usuario_ai,
                p_id_usuario,
                now(),
                v_parametros._nombre_usuario_ai,
                null,
                null,
                now(),
                now(),
                v_parametros.refresh_token,
                v_parametros.access_token,
                v_parametros.authorization_code,
                v_parametros.expires_in,
                split_part(v_parametros.refresh_token, '':'', 1)
                )RETURNING id_user_mapping into v_id_user_mapping;
			end if;
			--Definicion de la respuesta
			v_resp = pxp.f_agrega_clave(v_resp,''mensaje'',''User Mapping almacenado(a) con exito (id_user_mapping''||v_id_user_mapping||'')'');
            v_resp = pxp.f_agrega_clave(v_resp,''id_user_mapping'',v_id_user_mapping::varchar);

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

ALTER FUNCTION sigep.ft_user_mapping_ime (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;