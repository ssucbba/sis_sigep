<?php
/**
*@package pXP
*@file gen-ServiceRequest.php
*@author  (admin)
*@date 27-12-2018 13:10:13
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
Phx.vista.ServiceRequest=Ext.extend(Phx.gridInterfaz,{

    viewConfig: {
        stripeRows: false,
        getRowClass: function(record) {
            return "x-selectable";
        }
    },

	constructor:function(config){
		this.maestro=config.maestro;
    	//llama al constructor de la clase padre
		Phx.vista.ServiceRequest.superclass.constructor.call(this,config);

        this.addButton('btnreenviar', {
            grupo: [0],
            text: 'Eliminar C31',
            iconCls: 'breload2',
            disabled: false,
            handler:this.onRevertirC31,
            tooltip: '<b>Eliminar C31</b><br/>Envia solicitud para eliminar Documento 31 Sigep'
        });

        this.addButton('btnAnular', {
            grupo: [1],
            text: 'Eliminar C21',
            iconCls: 'breload2',
            disabled: false,
            handler:this.onRevertirC21,
            tooltip: '<b>Eliminar C21</b><br/>Envia solicitud para eliminar Documento 21 Sigep'
        });


        this.init();
		this.load({params:{start:0, limit:this.tam_pag}})
	},

    gruposBarraTareas:[
        {name:'c31',title:'<h1 style="text-align: center; color: #00B167;" align="center">DOCUMENTOS C31</h1>',grupo:0,height:0},
        {name:'c21',title:'<h1 style="text-align: center; color: #B066BB;" align="center">DOCUMENTOS C21</h1>',grupo:1,height:0}
    ],

    beditGroups:[0,1],
    bdelGroups:[0,1],
    bactGroups:[0,1],
    bexcelGroups:[0,1],

    actualizarSegunTab: function(name, indice){
        this.store.baseParams.tipo_documento = name;
        this.load({params: {start: 0, limit: 50}});
    },

    onRevertirC31: function (){

        Ext.Msg.show({
            title: 'Eliminar Documento C31',
            msg: '<b style="color: red;">Esta seguro de eliminar el Documento C31 SIGEP.</b>',
            fn: function (btn){
                if(btn == 'ok'){
                    var record = this.getSelectedData();
                    Phx.CP.loadingShow();

                    Ext.Ajax.request({
                        url:'../../sis_sigep/control/ServiceRequest/revertirProcesoSigep',
                        params:{
                            id_service_request : record.id_service_request
                        },
                        success: this.procesarEstadoRevertidoC31,
                        failure: this.conexionFailure,
                        timeout: this.timeout,
                        scope:this
                    });
                }
            },
            buttons: Ext.Msg.OKCANCEL,
            width: 350,
            maxWidth:500,
            icon: Ext.Msg.WARNING,
            scope:this
        });
    },

    onRevertirC21: function (){

        Ext.Msg.show({
            title: 'Eliminar Documento C21',
            msg: '<b style="color: red;">Esta seguro de eliminar el Documento C21 SIGEP.</b>',
            fn: function (btn){
                if(btn == 'ok'){
                    var record = this.getSelectedData();
                    Phx.CP.loadingShow();

                    Ext.Ajax.request({
                        url:'../../sis_sigep/control/ServiceRequest/revertirProcesoSigepC21',
                        params:{
                            id_service_request : record.id_service_request
                        },
                        success: this.procesarEstadoRevertidoC21,
                        failure: this.conexionFailure,
                        timeout: this.timeout,
                        scope:this
                    });
                }
            },
            buttons: Ext.Msg.OKCANCEL,
            width: 350,
            maxWidth:500,
            icon: Ext.Msg.WARNING,
            scope:this
        });
    },

    procesarEstadoRevertidoC31: function(resp, opt){

        var record = this.getSelectedData();
        var reg =  Ext.decode(Ext.util.Format.trim(resp.responseText));
        console.log('ETAPA 1 => reg.ROOT, opt', reg.ROOT, opt);
        var datos = reg.ROOT.datos;

        if(!reg.ROOT.error){

            Ext.Ajax.request({
                url:'../../sis_sigep/control/ServiceRequest/procesarEstadoRevertidoC31',
                params:{
                    id_service_request : record.id_service_request
                },
                success: function (response) {
                    var rec =  Ext.decode(Ext.util.Format.trim(response.responseText));
                    var datos = rec.ROOT.datos;
                    console.log('ETAPA 2 => rec, datos', rec, rec.ROOT);
                    if(!reg.ROOT.error){
                        Phx.CP.loadingHide();
                        Ext.Msg.show({
                            title: 'Eliminar C31 SIGEP',
                            msg: '<b>Estimado Funcionario: \n Se elimino satisfactoriamente el Documento C31 Sigep.</b>',
                            buttons: Ext.Msg.OK,
                            width: 512,
                            icon: Ext.Msg.INFO
                        });
                    }else{
                        Phx.CP.loadingHide();

                        Ext.Msg.show({
                            title: 'Eliminar C31 SIGEP',
                            msg: '<b>Estimado Funcionario: \n Se tuvo algunos inconvenientes al eliminar el Documento C31 Sigep.</b>',
                            buttons: Ext.Msg.OK,
                            width: 512,
                            icon: Ext.Msg.INFO
                        });
                    }

                },
                failure: this.conexionFailure,
                timeout: this.timeout,
                scope:this
            });

        }else{
            Phx.CP.loadingHide();

            Ext.Msg.show({
                title: 'Eliminar C31 SIGEP',
                msg: '<b>Estimado Funcionario: \n Se tuvo algunos inconvenientes al eliminar el Documento C31 Sigep.</b>',
                buttons: Ext.Msg.OK,
                width: 512,
                icon: Ext.Msg.INFO
            });
        }
    },

    procesarEstadoRevertidoC21: function(resp, opt){

        var record = this.getSelectedData();
        var reg =  Ext.decode(Ext.util.Format.trim(resp.responseText));

        var datos = reg.ROOT.datos;

        if(!reg.ROOT.error){

            Ext.Ajax.request({
                url:'../../sis_sigep/control/ServiceRequest/procesarEstadoRevertidoC21',
                params:{
                    id_service_request : record.id_service_request
                },
                success: function (response) {
                    var rec =  Ext.decode(Ext.util.Format.trim(response.responseText));
                    var datos = rec.ROOT.datos;
                    if(!reg.ROOT.error){
                        Phx.CP.loadingHide();
                        Ext.Msg.show({
                            title: 'Eliminar C21 SIGEP',
                            msg: '<b>Estimado Funcionario: \n Se elimino satisfactoriamente el Documento C21 Sigep.</b>',
                            buttons: Ext.Msg.OK,
                            width: 512,
                            icon: Ext.Msg.INFO
                        });
                    }else{
                        Phx.CP.loadingHide();

                        Ext.Msg.show({
                            title: 'Eliminar C21 SIGEP',
                            msg: '<b>Estimado Funcionario: \n Se tuvo algunos inconvenientes al eliminar el Documento C21 Sigep.</b>',
                            buttons: Ext.Msg.OK,
                            width: 512,
                            icon: Ext.Msg.INFO
                        });
                    }

                },
                failure: this.conexionFailure,
                timeout: this.timeout,
                scope:this
            });

        }else{
            Phx.CP.loadingHide();

            Ext.Msg.show({
                title: 'Eliminar C21 SIGEP',
                msg: '<b>Estimado Funcionario: \n Se tuvo algunos inconvenientes al eliminar el Documento C21 Sigep.</b>',
                buttons: Ext.Msg.OK,
                width: 512,
                icon: Ext.Msg.INFO
            });
        }
    },
			
	Atributos:[
		{
			//configuracion del componente
			config:{
                    fieldLabel: 'Id. Service',
					labelSeparator:'',
					inputType:'hidden',
					name: 'id_service_request'
			},
			type:'Field',
            bottom_filter : true,
            filters:{pfiltro:'sere.id_service_request',type:'string'},
			form:true,
            grid: true
		},
		{
			config:{
				name: 'service_code',
				fieldLabel: 'Service Code',
				allowBlank: false,
				anchor: '80%',
				gwidth: 120,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'tsr.service_code',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		
		{
			config:{
				name: 'description',
				fieldLabel: 'Service Desc',
				allowBlank: false,
				anchor: '80%',
				gwidth: 150,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'tsr.description',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		
		{
			config:{
				name: 'sys_origin',
				fieldLabel: 'Origin',
				allowBlank: false,
				anchor: '80%',
				gwidth: 100,
				maxLength:100
			},
				type:'TextField',
				filters:{pfiltro:'sere.sys_origin',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		{
			config:{
				name: 'ip_origin',
				fieldLabel: 'IP Origin',
				allowBlank: false,
				anchor: '80%',
				gwidth: 100,
				maxLength:50
			},
				type:'TextField',
				filters:{pfiltro:'sere.ip_origin',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		{
			config:{
				name: 'status',
				fieldLabel: 'Status',
				allowBlank: false,
				anchor: '80%',
				gwidth: 100,
				maxLength:50
			},
				type:'TextField',
				filters:{pfiltro:'sere.status',type:'string'},
				id_grupo:1,
				grid:true,
				form:true,
                egrid:true
		},
        {
            config:{
                name: 'documento_c31',
                fieldLabel: 'Documento C31',
                allowBlank: true,
                anchor: '80%',
                gwidth: 130,
                maxLength:-5
            },
            type:'TextField',
            filters:{pfiltro:'nrodoc.nro_documento',type:'string'},
            id_grupo:1,
            bottom_filter : true,
            grid:true,
            form:true
        },
		{
			config:{
				name: 'fecha_reg',
				fieldLabel: 'Fecha Creación',
				allowBlank: true,
				anchor: '80%',
				gwidth: 110,
							format: 'd/m/Y', 
							renderer:function (value,p,record){return value?value.dateFormat('d/m/Y H:i:s'):''}
			},
				type:'DateField',
				filters:{pfiltro:'sere.fecha_reg',type:'date'},
				id_grupo:1,
				grid:true,
				form:false
		},
		
		{
			config:{
				name: 'date_finished',
				fieldLabel: 'Fecha Finalizacion',
				allowBlank: true,
				anchor: '80%',
				gwidth: 120,
							format: 'd/m/Y', 
							renderer:function (value,p,record){return value?value.dateFormat('d/m/Y H:i:s'):''}
			},
				type:'DateField',
				filters:{pfiltro:'sere.date_finished',type:'date'},
				id_grupo:1,
				grid:true,
				form:true
		},
		
		
		{
			config:{
				name: 'last_message',
				fieldLabel: 'Ult. Mensaje',
				allowBlank: true,
				anchor: '80%',
				gwidth: 130,
				maxLength:-5
			},
				type:'TextField',
				filters:{pfiltro:'sere.last_message',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		},
		{
			config:{
				name: 'last_message_revert',
				fieldLabel: 'Ult. Mensaje Rev',
				allowBlank: true,
				anchor: '80%',
				gwidth: 130,
				maxLength:-5
			},
				type:'TextField',
				filters:{pfiltro:'sere.last_message_revert',type:'string'},
				id_grupo:1,
				grid:true,
				form:true
		}
		
		
		
	],
	tam_pag:50,	
	title:'Service Request',
	ActSave:'../../sis_sigep/control/ServiceRequest/insertarServiceRequest',
	ActDel:'../../sis_sigep/control/ServiceRequest/eliminarServiceRequest',
	ActList:'../../sis_sigep/control/ServiceRequest/listarServiceRequest',
	id_store:'id_service_request',
	fields: [
		{name:'id_service_request', type: 'numeric'},
		{name:'id_type_service_request', type: 'numeric'},
		{name:'estado_reg', type: 'string'},
		{name:'date_finished', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'status', type: 'string'},
		{name:'sys_origin', type: 'string'},
		{name:'description', type: 'string'},
		{name:'service_code', type: 'string'},
		{name:'ip_origin', type: 'string'},
		{name:'last_message', type: 'string'},
		{name:'last_message_revert', type: 'string'},
		{name:'usuario_ai', type: 'string'},
		{name:'fecha_reg', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'id_usuario_reg', type: 'numeric'},
		{name:'id_usuario_ai', type: 'numeric'},
		{name:'id_usuario_mod', type: 'numeric'},
		{name:'fecha_mod', type: 'date',dateFormat:'Y-m-d H:i:s.u'},
		{name:'usr_reg', type: 'string'},
		{name:'usr_mod', type: 'string'},
		{name:'documento_c31', type: 'string'},

	],
	sortInfo:{
		field: 'id_service_request',
		direction: 'DESC'
	},
	south:{
		  url:'../../../sis_sigep/vista/sigep_service_request/SigepServiceRequest.php',
		  title:'Sigep Service Request', 
		  height:'50%',
		  cls:'SigepServiceRequest'
	},
	bdel:true,
	bsave:true,
	bnew:false,
	bedit:false,
    btest:false
	}
)
</script>
		
		