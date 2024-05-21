<?php
/**
*@package pXP
*@file gen-UserMapping.php
*@author  (admin)
*@date 08-04-2018 11:04:46
*@description Archivo con la interfaz de usuario que permite la ejecucion de todas las funcionalidades del sistema
*/

header("content-type: text/javascript; charset=UTF-8");
?>

<style type="text/css" rel="stylesheet">
    .x-selectable,
    .x-selectable * {
        -moz-user-select: text !important;
        -khtml-user-select: text !important;
        -webkit-user-select: text !important;
    }

    .x-grid-row td,
    .x-grid-summary-row td,
    .x-grid-cell-text,
    .x-grid-hd-text,
    .x-grid-hd,
    .x-grid-row,

    .x-grid-row,
    .x-grid-cell,
    .x-unselectable
    {
        -moz-user-select: text !important;
        -khtml-user-select: text !important;
        -webkit-user-select: text !important;
    }
</style>

<script>
Phx.vista.UserMapping=Ext.extend(Phx.gridInterfaz,{
    viewConfig: {
        stripeRows: false,
        getRowClass: function(record) {
            return "x-selectable";
        }
    },
	constructor:function(config){
		this.maestro=config.maestro;
    	//llama al constructor de la clase padre
		Phx.vista.UserMapping.superclass.constructor.call(this,config);
		this.init();
		this.load({params:{start:0, limit:this.tam_pag}})
	},
			
	Atributos:[
		{
			//configuracion del componente
			config:{
					labelSeparator:'',
					inputType:'hidden',
					name: 'id_user_mapping'
			},
			type:'Field',
			form:true 
		},
		{
			config:{
				name: 'pxp_user',
				fieldLabel: 'PXP User',
				allowBlank: false,
				anchor: '80%',
				gwidth: 120,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'usm.pxp_user',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		
		{
			config:{
				name: 'sigep_user',
				fieldLabel: 'Sigep User',
				allowBlank: false,
				anchor: '80%',
				gwidth: 120,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'usm.sigep_user',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		{
			config:{
				name: 'refresh_token',
				fieldLabel: 'Refresh Token',
				allowBlank: true,
				anchor: '80%',
				gwidth: 120,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'usm.refresh_token',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'access_token',
				fieldLabel: 'Access Token',
				allowBlank: true,
				anchor: '80%',
				gwidth: 120,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'usm.access_token',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
        {
            config:{
                name: 'authorization_code',
                fieldLabel: 'Authorization Code',
                allowBlank: true,
                anchor: '80%',
                gwidth: 120,
                maxLength:100
            },
            type:'TextField',
            filters:{pfiltro:'usm.authorization_code',type:'string'},
            id_grupo:1,
            grid:true,
            form:false
        },
		{
			config:{
				name: 'date_issued_at',
				fieldLabel: 'Date Issued at',
				allowBlank: true,
				anchor: '80%',
				gwidth: 120,
							format: 'd/m/Y', 
							renderer:function (value,p,record){return value?value.dateFormat('d/m/Y H:i:s'):''}
			},
				type:'DateField',
				filters:{pfiltro:'usm.date_issued_at',type:'date'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'expires_in',
				fieldLabel: 'Expires in',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:4
			},
				type:'NumberField',
				filters:{pfiltro:'usm.expires_in',type:'numeric'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'estado_reg',
				fieldLabel: 'Estado Reg.',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:10
			},
				type:'TextField',
				filters:{pfiltro:'usm.estado_reg',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
		
		
		{
			config:{
				name: 'id_usuario_ai',
				fieldLabel: '',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:4
			},
				type:'Field',
				filters:{pfiltro:'usm.id_usuario_ai',type:'numeric'},
				id_grupo:1,
				grid:false,
				form:false
		},
		{
			config:{
				name: 'usr_reg',
				fieldLabel: 'Creado por',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:4
			},
				type:'Field',
				filters:{pfiltro:'usu1.cuenta',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'fecha_reg',
				fieldLabel: 'Fecha creación',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
							format: 'd/m/Y', 
							renderer:function (value,p,record){return value?value.dateFormat('d/m/Y H:i:s'):''}
			},
				type:'DateField',
				filters:{pfiltro:'usm.fecha_reg',type:'date'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'usuario_ai',
				fieldLabel: 'Funcionaro AI',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:300
			},
				type:'TextField',
				filters:{pfiltro:'usm.usuario_ai',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'usr_mod',
				fieldLabel: 'Modificado por',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
				maxLength:4
			},
				type:'Field',
				filters:{pfiltro:'usu2.cuenta',type:'string'},
				id_grupo:1,
				grid:true,
				form:false
		},
		{
			config:{
				name: 'fecha_mod',
				fieldLabel: 'Fecha Modif.',
				allowBlank: true,
				anchor: '80%',
				gwidth: 100,
							format: 'd/m/Y', 
							renderer:function (value,p,record){return value?value.dateFormat('d/m/Y H:i:s'):''}
			},
				type:'DateField',
				filters:{pfiltro:'usm.fecha_mod',type:'date'},
				id_grupo:1,
				grid:true,
				form:false
		}
	],
	tam_pag:50,	
	title:'User Mapping',
	ActSave:'../../sis_sigep/control/UserMapping/insertarUserMapping',
	ActDel:'../../sis_sigep/control/UserMapping/eliminarUserMapping',
	ActList:'../../sis_sigep/control/UserMapping/listarUserMapping',
	id_store:'id_user_mapping',
	fields: [
		{name:'id_user_mapping', type: 'numeric'},
		{name:'refresh_token', type: 'string'},
		{name:'sigep_user', type: 'string'},
		{name:'access_token', type: 'string'},
		{name:'date_issued_at', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'expires_in', type: 'numeric'},
		{name:'estado_reg', type: 'string'},
		{name:'date issued_rt', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'pxp_user', type: 'string'},
		{name:'id_usuario_ai', type: 'numeric'},
		{name:'id_usuario_reg', type: 'numeric'},
		{name:'fecha_reg', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'usuario_ai', type: 'string'},
		{name:'id_usuario_mod', type: 'numeric'},
		{name:'fecha_mod', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
        {name:'authorization_code', type: 'string'},
		{name:'usr_reg', type: 'string'},
		{name:'usr_mod', type: 'string'},
		
	],
	sortInfo:{
		field: 'id_user_mapping',
		direction: 'ASC'
	},
	bdel:true,
	bsave:true,
    btest:false
	}
)
</script>
		
		