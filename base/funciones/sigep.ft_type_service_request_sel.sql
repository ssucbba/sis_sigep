CREATE OR REPLACE FUNCTION sigep.ft_type_service_request_sel (
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
 FUNCION: 		sigep.ft_type_service_request_sel
 DESCRIPCION:   Funcion que devuelve conjuntos de registros de las consultas relacionadas con la tabla ''sigep.ttype_service_request''
 AUTOR: 		 (admin)
 FECHA:	        29-11-2018 04:31:24
 COMENTARIOS:
***************************************************************************
 HISTORIAL DE MODIFICACIONES:
#ISSUE				FECHA				AUTOR				DESCRIPCION
 #0				29-11-2018 04:31:24								Funcion que devuelve conjuntos de registros de las consultas relacionadas con la tabla ''sigep.ttype_service_request''
 #
 ***************************************************************************/

DECLARE

	v_consulta    		varchar;
	v_parametros  		record;
	v_nombre_funcion   	text;
	v_resp				varchar;

BEGIN

	v_nombre_funcion = ''sigep.ft_type_service_request_sel'';
    v_parametros = pxp.f_get_record(p_tabla);

	/*********************************
 	#TRANSACCION:  ''SIG_TSR_SEL''
 	#DESCRIPCION:	Consulta de datos
 	#AUTOR:		admin
 	#FECHA:		29-11-2018 04:31:24
	***********************************/

	if(p_transaccion=''SIG_TSR_SEL'')then

    	begin
    		--Sentencia de la consulta
			v_consulta:=''select
						tsr.id_type_service_request,
						tsr.estado_reg,
						tsr.description,
						tsr.service_code,
						tsr.id_usuario_reg,
						tsr.fecha_reg,
						tsr.usuario_ai,
						tsr.id_usuario_ai,
						tsr.id_usuario_mod,
						tsr.fecha_mod,
						usu1.cuenta as usr_reg,
						usu2.cuenta as usr_mod
						from sigep.ttype_service_request tsr
						inner join segu.tusuario usu1 on usu1.id_usuario = tsr.id_usuario_reg
						left join segu.tusuario usu2 on usu2.id_usuario = tsr.id_usuario_mod
				        where  '';

			--Definicion de la respuesta
			v_consulta:=v_consulta||v_parametros.filtro;
			v_consulta:=v_consulta||'' order by '' ||v_parametros.ordenacion|| '' '' || v_parametros.dir_ordenacion || '' limit '' || v_parametros.cantidad || '' offset '' || v_parametros.puntero;

			--Devuelve la respuesta
			return v_consulta;

		end;

	/*********************************
 	#TRANSACCION:  ''SIG_TSR_CONT''
 	#DESCRIPCION:	Conteo de registros
 	#AUTOR:		admin
 	#FECHA:		29-11-2018 04:31:24
	***********************************/

	elsif(p_transaccion=''SIG_TSR_CONT'')then

		begin
			--Sentencia de la consulta de conteo de registros
			v_consulta:=''select count(id_type_service_request)
					    from sigep.ttype_service_request tsr
					    inner join segu.tusuario usu1 on usu1.id_usuario = tsr.id_usuario_reg
						left join segu.tusuario usu2 on usu2.id_usuario = tsr.id_usuario_mod
					    where '';

			--Definicion de la respuesta
			v_consulta:=v_consulta||v_parametros.filtro;

			--Devuelve la respuesta
			return v_consulta;

		end;

	else

		raise exception ''Transaccion inexistente'';

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

ALTER FUNCTION sigep.ft_type_service_request_sel (p_administrador integer, p_id_usuario integer, p_tabla varchar, p_transaccion varchar)
  OWNER TO postgres;