/******************************************************************************************************************************************************/

// Funcao para retornar cadastro de computador

function retorna_computador(bd_id){

		$('#todos_os_dias').prop('checked',false);
		$('#dia0').prop('checked',false);
		$('#dia1').prop('checked',false);
		$('#dia2').prop('checked',false);
		$('#dia3').prop('checked',false);
		$('#dia4').prop('checked',false);
		$('#dia5').prop('checked',false);
		$('#dia6').prop('checked',false);



	$.post('retorna_bd.php',{bd_id}).done(function(data){data = JSON.parse(data);

		$('#bd_nome_usuario').val(data.bd_nome_usuario);
		$('#bd_login').val(data.bd_login);
		$('#bd_email').val(data.bd_email);
		$('#bd_senha').val(data.bd_senha);
		$('#bd_ip').val(data.bd_ip);
		$('#bd_porta').val(data.bd_porta);
		$('#tipo_id').val(data.bd_tipo); 
		$('#bd_hora_backup').val(data.bd_hora_backup);
		$('#dia0').val(data.dia0);
		$('#dia1').val(data.dia1);
		$('#dia2').val(data.dia2);
		$('#dia3').val(data.dia3);
		$('#dia4').val(data.dia4);
		$('#dia5').val(data.dia5);
		$('#dia6').val(data.dia6);
		$('#bd_hora_backup').val(data.bd_hora_backup);
		$('#servidor_id').val(data.bd_servidor_backup);		
		$('#bd_app').val(data.bd_app);	
		$('#bd_usuario_adm').val(data.bd_usuario_adm);
		$('#bd_backup_ativo').val(data.bd_backup_ativo);
		$('#bd_ssh').val(data.bd_ssh);
		$('#bd_recorrencia').val(data.bd_recorrencia);
		$('#bd_container').val(data.bd_container);

		$('.chosen-select').trigger("chosen:updated");		


		if(data.dia0 == 1 && data.dia1 == 1 && data.dia2 == 1 && data.dia3 == 1 && data.dia4 == 1 && data.dia5 == 1 && data.dia6 == 1){

			$('#todos_os_dias').prop('checked',true);
		}


		if(data.dia0 == 1){
			
			$('#dia0').prop('checked',true);

		} if (data.dia1 == 1){

			$('#dia1').prop('checked',true);

		} if (data.dia2 == 1){

			$('#dia2').prop('checked',true);

		} if (data.dia3 == 1){

			$('#dia3').prop('checked',true);

		} if (data.dia4 == 1){

			$('#dia4').prop('checked',true); 

		} if (data.dia5 == 1){

			$('#dia5').prop('checked',true); 

		} if (data.dia6 == 1){

			$('#dia6').prop('checked',true); 

		}

		
	});

}



// Funcao para retornar cadastro de computador

function retorna_computador2(){


	
		$('#todos_os_dias').prop('checked',false);
		$('#dia0').prop('checked',false);
		$('#dia1').prop('checked',false);
		$('#dia2').prop('checked',false);
		$('#dia3').prop('checked',false);
		$('#dia4').prop('checked',false);
		$('#dia5').prop('checked',false);
		$('#dia6').prop('checked',false);



	$.post('retorna_bd.php',{bd_id: $('#bd_id').val()}).done(function(data){data = JSON.parse(data);

		
		$('#bd_email').val(data.bd_email);
		$('#bd_senha').val(data.bd_senha);
		$('#bd_ip').val(data.bd_ip);
		$('#bd_porta').val(data.bd_porta);
		$('#tipo_id').val(data.bd_tipo);
		$('#tipo_id').selectpicker('refresh');
		$('#dia0').val(data.dia0);
		$('#dia1').val(data.dia1);
		$('#dia2').val(data.dia2);
		$('#dia3').val(data.dia3);
		$('#dia4').val(data.dia4);
		$('#dia5').val(data.dia5);
		$('#dia6').val(data.dia6);
		$('#bd_hora_backup').val(data.bd_hora_backup);
		$('#servidor_id').val(data.bd_servidor_backup);
		$('#servidor_id').selectpicker('refresh');
		$('#bd_liga_computador').val(data.bd_liga_computador);
		$('#bd_desliga_computador').val(data.bd_desliga_computador);
		$('#bd_app').val(data.bd_app);
		$('#documento_id').val(data.documento_id);
		$('#extensao_arquivo_id').val(data.extensao_arquivo_id);
		$('#bd_usuario_adm').val(data.bd_usuario_adm);
		$('#bd_backup_ativo').val(data.bd_backup_ativo);
		$('.chosen-select').trigger("chosen:updated");


		if(data.dia0 == 1 && data.dia1 == 1 && data.dia2 == 1 && data.dia3 == 1 && data.dia4 == 1 && data.dia5 == 1 && data.dia6 == 1){


			$('#todos_os_dias').prop('checked',true);
		}

		if(data.dia0 == 1){
			
			$('#dia0').prop('checked',true);

		} if (data.dia1 == 1){

			$('#dia1').prop('checked',true);

		} if (data.dia2 == 1){

			$('#dia2').prop('checked',true);

		} if (data.dia3 == 1){

			$('#dia3').prop('checked',true);

		} if (data.dia4 == 1){

			$('#dia4').prop('checked',true); 

		} if (data.dia5 == 1){

			$('#dia5').prop('checked',true); 

		} if (data.dia6 == 1){

			$('#dia6').prop('checked',true); 

		}

		$('#pesquisar_cadastro_comp').prop('hidden',true);
	});

}
/*********************************************************************************************************************************************/
/************************************* Funcao para retornar o cadastro de usuarios na tela manutencao de usuarios *****************************/

// Funcao para retornar os usuarios

function retorna_usuario(){
	$.post('retorna_usuario.php',{usuario_id: $('#usuario_id').val()}).done (function (data){

		data = JSON.parse(data);

		$('#usuario_nome').val(data.usuario_nome);
		$('#usuario_login').val(data.usuario_login);
		$('#usuario_status').val(data.usuario_status);
		$('#usuario_status').selectpicker('refresh');
		$('#usuario_id_app').val(data.usuario_id_app);
		$('#usuario_id_app').selectpicker('refresh');
		$('#usuario_email').val(data.usuario_email);
	})
}

/********************************************************************************************************************************************/
/************************************************ Funcao para retornar cadastro de setor na tela Manutencao de setores **********************/


// Funcao para retornar os setores cadastrados

function retorna_app() {

	$.post('retorna_app.php', {app_id: $('#app_id').val()}).done(function(data) {

		data = JSON.parse(data);
		$('#app_nome').val(data.app_nome);
		$('#descricao_app').val(data.app_descricao);

	});
}

/*********************************************************************************************************************************************/
/****************** *********************** Funcao para retornar cadastro de Tipo de Banco de Dados *******************************************/

// Funcao para retornar cadastro de Tipo de Banco de Dados

function retorna_so(){

	$.post('retorna_types.php',{tipo_id:$('#tipo_id').val()}).done(function(data){

		data = JSON.parse(data);

		$('#tipo_nome').val(data.tipo_nome);
		$('#tipo_plataforma').val(data.tipo_plataforma);
		$('#tipo_plataforma').selectpicker('refresh');

	})
}

/********************************************************************************************************************************************/
/***************************************** Funcao para retornar cadastro de Documentos ******************************************************/

function retorna_documento(){

	$('#documento_nome').val("");
	$('.so').val("");

	$.post('retorna_documentos.php',{documento_id:$('#documento_id').val()}).done(function(data){

		data = JSON.parse(data);
		

		$('#documento_nome').val(data.documento_nome);

		$('.diretorio_documentos').each(function(chave,valor){	

		//console.log(valor);		

		for(i=0; i< data.so.length; i++){

			if($(this).find('input').attr('data-id') == data.so[i].diretorio_id_sistema_operacional){

				console.log(data.so[i]);

				$(this).find('input').val(data.so[i].diretorio_documentos);



			}
		}	
	})
	})
}

/************************************************** Funcao da Tela Restaurar Backup de Usuarios *****************************************/

function retorna_docs_restaurar(){

$.post('retorna_docs_restaurar.php',{bd_id:$('#bd_id').val()}).done(function(data){

data = JSON.parse(data);

$('#documento_id').val(data.documento_id);

});

}

/****************************************************************************************************************************************/7
