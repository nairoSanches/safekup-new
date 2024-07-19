/**************************************Funções da tela de Alteracao de Setor **********************************************************/
function busca_app(){

$.post('busca_app.php').done(function(data){

$('#app_id').html(data);
$('#app_id').selectpicker('refresh');

});

}



// Função para excluir um setor

function excluir_app(app_id){


alertify.confirm('EXCLUSÃO','DESEJA REALMENTE EXCLUIR ESTE CADASTRO?',function(){
	$.post('excluir_app.php',{app_id}).done(function(data){

		if(data == 'bd_associado'){
			alertify.error('Não é possível excluir este setor pois existe computadores e/ou usuários associados à ele. Antes de excluir, altere estes cadastros.');
			return;

		} else if (data == 'app_excluido_com_sucesso'){
			alertify.success('Cadastro excluído com sucesso!');
			setTimeout(function () {
				window.location.reload();
			}, 1500);
			$('#app_nome').val("");
			$('#descricao_app').val("");

		} else {
			alertify.error('Erro ao excluir o cadastro!');
			return;

		}
	})
},function(){
	alertify.warning('Operação cancelada pelo usuário!');
});

}


/********************************************************************************************************************************************/
/********************************** Funcoes da Tela de Alteracao de Tipo de Banco de Dados *****************************************************/

function busca_so(){

$.post('busca_types.php').done(function(data){

$('#tipo_id').html(data);
$('#tipo_id').selectpicker('refresh');

});

}

function excluir_so(tipo_id){
	alertify.confirm('EXCLUSÃO','DESEJA REALMENTE EXCLUIR ESTE CADASTRO?',function(){

		$.post('excluir_types.php',{tipo_id}).done(function(data){

			if(data == 'bd_associado'){

				alertify.error('Não é possível excluir este cadastro pois existem computadores com este Tipo de Banco de Dados cadastrado no Sistema!');
				return;

			} else if (data == 'so_excluido_com_sucesso'){

				alertify.success('Cadastro excluído com sucesso!');
				setTimeout(function () {
					window.location.reload();
				}, 1500);
				return;

			} else {
				alertify.error('Erro ao excluir o cadastro!');
				return;

			}
		})



	},function(){
		alertify.warning('Operação cancelada pelo usuário!');
		return;


	})

}

/*******************************************************************************************************************************************/
/**************************************************** Funcao da tela de Alteração de Documentos ********************************************/
function busca_diretorio(){

$.post('busca_diretorio.php').done(function(data){

$('#documento_id').html(data);
$('#documento_id').selectpicker('refresh');

});

}


function excluir_doc(documento_id){

alertify.confirm('EXCLUSÃO!','DESEJA REALMENTE EXCLUIR ESTE CADASTRO?',function(){

	$.post('excluir_documento.php',{documento_id}).done(function(data){

		if(data == 'bd_associado'){

			alertify.error('Existem cadastros associados a este Documento. Antes de excluir, altere estes cadastros. Acesse * Documentos > Documentos/Usuários *');
			return;

		} else if (data == 'erro_ao_excluir'){

			alertify.error('Erro ao excluir o cadastro!');
			return;

		} else if (data == "doc_excluido_com_sucesso"){

			alertify.success('Cadastro excluído com sucesso!');
			setTimeout(function () {
				window.location.reload();
			}, 1500);
			return;
		}
	})


},function(){
	alertify.warning('OPeração cancelada pelo usuário!');
	return;
})
}

/********************************************************************************************************************************************/
/************************************************* Funcoes da Tela de Manutenção de Computadores ********************************************/

function busca_computador(){

$.post('busca_bd.php').done(function(data){

$('#bd_id').html(data);

});

}

function excluir_computador(bd_id){

	alertify.confirm('EXCLUSÃO','DESEJA REALMENTE EXCLUIR ESTE CADASTRO?',function(){
		$.post('excluir_bd.php',{bd_id}).done(function(data){

		if (data == 'true'){
				alertify.success('Cadastro excluído com sucesso!');
				setTimeout(function () {
					window.location.reload();
				}, 1500);
				$('#app_nome').val("");
				$('#descricao_app').val("");

			} else {
				alertify.error('Erro ao excluir o cadastro!');
				return;

			}
		})
	},function(){
		alertify.warning('Operação cancelada pelo usuário!');
	});

}

/*******************************************************************************************************************************************/

function excluir_smtp(smtp_id){

	alertify.confirm('EXCLUSÃO','DESEJA REALMENTE EXCLUIR ESTE CADASTRO ?', function(){
		$.post('excluir_smtp.php',{smtp_id}).done(function(data){

			if (data == "false"){
				alertify.error('Erro ao excluir o cadastro!')
				return;

			} else if (data == "true"){
				alertify.success('Cadastro excluído com sucesso!');
				setTimeout(function () {
					window.location.reload();
				}, 1500);
				return;
			}
		})
	},function(){
		alertify.warning('Operação cancelada pelo usuário');
	})

}

/***************************************************** Funcao para excluir servidor de Backup ************************************************************/
function excluir_servidor(servidor_id){

alertify.confirm('EXCLUSÃO','DESEJA REALMENTE EXCLUIR ESTE CADASTRO?',function(){
	$.post('excluir_servidor.php',{servidor_id}).done(function(data){


		if(data == 'bd_associado'){
				alertify.error('Existe cadastro de computadores que fazem backup neste servidor. Ajuste estes cadastros antes de excluir o servidor');
				return;

		} else if (data == "false"){

			alertify.error('Erro ao excluir o cadastro!');
			return;

		} else if (data == "true"){

			alertify.success('Cadastro excluído com sucesso!');
			setTimeout(function () {
				window.location.reload();
			}, 1500);
			return;
		}
	})

},function(){
	alertify.warning('Operação cancelada pelo Usuário');
})

}

function recarregar_servidor(){

	window.location.reload();

}
