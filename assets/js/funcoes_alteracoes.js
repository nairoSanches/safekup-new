function buscar_computador() {
	$('#bd_id').val("");
	$('#alert2').html(bd_id);
	$('#modal2').modal('show');
}

function altera_computador() {
	if ($('#bd_nome_usuario').val() == "" || $('#bd_login').val() == "" || $('#bd_email').val() == "" || $('#bd_senha').val() == "" || $('#bd_ip').val() == "" || $('#bd_porta').val() == "" || $('#tipo_id').val() == "" || $('#bd_hora_backup').val() == "" || $('#servidor_id').val() == "" || $('#bd_liga_computador').val() == "" || $('#bd_desliga_computador').val() == "" || $('#bd_app').val() == "" || $('#documento_id').val() == "" || $('#bd_usuario_adm').val() == "" || $('#bd_backup_ativo').val() == "" || $('#extensao_arquivo_id').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;
	} else if ($('#dia0').is(':checked') == false && $('#dia1').is(':checked') == false && $('#dia2').is(':checked') == false && $('#dia3').is(':checked') == false
		&& $('#dia4').is(':checked') == false && $('#dia5').is(':checked') == false && $('#dia6').is(':checked') == false) {
		alertify.error('Informe pelo menos um dia para ser feito backup deste computador');
		return;
	} else {
		$.post('alterar_bd.php', {
			bd_id: $('#bd_id').val(),
			bd_nome_usuario: $('#bd_nome_usuario').val(),
			bd_login: $('#bd_login').val(),
			bd_email: $('#bd_email').val(),
			bd_senha: $('#bd_senha').val(),
			bd_ip: $('#bd_ip').val(),
			bd_porta: $('#bd_porta').val(),
			tipo_id: $('#tipo_id').val(),
			dia0: $('#dia0').is(':checked') == true ? '1' : '0',
			dia1: $('#dia1').is(':checked') == true ? '1' : '0',
			dia2: $('#dia2').is(':checked') == true ? '1' : '0',
			dia3: $('#dia3').is(':checked') == true ? '1' : '0',
			dia4: $('#dia4').is(':checked') == true ? '1' : '0',
			dia5: $('#dia5').is(':checked') == true ? '1' : '0',
			dia6: $('#dia6').is(':checked') == true ? '1' : '0',
			bd_hora_backup: $('#bd_hora_backup').val(),
			bd_servidor_backup: $('#servidor_id').val(),
			bd_liga_computador: $('#bd_liga_computador').val(),
			bd_desliga_computador: $('#bd_desliga_computador').val(),
			bd_app: $('#bd_app').val(),
			documento_id: $('#documento_id').val(),
			extensao_arquivo_id: $('#extensao_arquivo_id').val(),
			bd_usuario_adm: $('#bd_usuario_adm').val(),
			bd_backup_ativo: $('#bd_backup_ativo').val(),
			bd_ssh: $('#bd_ssh').val(),
			bd_recorrencia: $('#bd_recorrencia').val(),
			bd_container: $('#bd_container').val(),
			bd_id_restore: $('#restore_id').val()



		}, function (data) {
			if (data == 'Cadastro alterado com sucesso') {
				alertify.success('Cadastro alterado com sucesso!');
				$('.form-control').val("");
				$('.chosen-select').trigger("chosen:updated");
				$('.checkbox').prop('checked', false);
				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/database-management/db_management.php';
					return;
				}, 1000);
			} else {
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});



	}
}

function busca_usuario() {
	$.post('busca_usuario.php').done(function (data) {
		$('#usuario_id').html(data);
		$('#usuario_id').selectpicker('refresh');
	});

}

function alterar_usuario() {
	if ($('#nome_usuario').val() == "" || $('#login').val() == "" || $('#setor').val() == "" || $('#usuario_email').val() == "" || $('#senha').val() == ""
		|| $('#confirma_senha').val() == "" || $('#status').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;

	} else if ($('#senha').val() != $('#confirma_senha').val()) {

		alertify.warning('As senhas não correspondem! Tente novamente.');
		return;

	} else {
		$('#alterar_usuario').attr('disabled', true);
		//Enviando os dados para o servidor
		$.post('altera_usuario.php', { usuario_id: $('#usuario_id').val(), usuario_nome: $('#nome_usuario').val(), usuario_id_app: $('#app_id').val(), usuario_email: $('#usuario_email').val(), usuario_status: $('#status').val() }, function (data) {

			if (data == 'ja_existe_login') {
				alertify.error('Já existe um usuário com este login!');
				return;

			} else if (data == 'cadastro_alterado_com_sucesso') {
				$('#alterar_usuario').attr('disabled', false);
				alertify.success('Cadastro alterado com sucesso!');
				$('#nome_usuario').val("");
				$('#login').val("");
				$('#app_id').val("");
				$('#app_id').trigger("chosen:updated");
				$('#status').val("");
				$('#status').trigger("chosen:updated");
				$('#usuario_email').val("");

				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/ssh/ssh.php';
					return;
				}, 1000);

			} else {
				$('#alterar_usuario').attr('disabled', false);
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}
}

function alterar_ssh() {
	if ($('#ssh_ip').val() == "" || $('#ssh_user').val() == "" || $('#ssh_status').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;
	} else {
		$('#alterar_ssh').attr('disabled', true);
		$.post('altera_ssh.php', { ssh_ip: $('#ssh_ip').val(), ssh_user: $('#ssh_user').val(), ssh_status: $('#ssh_status').val(), ssh_id: $('#ssh_id').val() }, function (data) {

			data = JSON.parse(data);
			if (data.success === 'ja_existe_login') {
				alertify.error('Já existe um ip/SSh com esse IP!');
				return;

			} else if (data.success === 'Cadastro alterado com sucesso.') {
				$('#alterar_ssh').attr('disabled', false);
				alertify.success('Cadastro alterado com sucesso!');
				$('#ssh_ip').val("");
				$('#ssh_user').val("");
				$('#ssh_status').val("");
				$('#ssh_status').trigger("chosen:updated");

				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/ssh/ssh.php';
					return;
				}, 1000);

			} else {
				$('#alterar_usuario').attr('disabled', false);
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}

}

function limpar_usu() {

	$('#usuario_id').val("");
	$('#usuario_id').selectpicker('refresh');
	$('#usuario_nome').val("");
	$('#usuario_login').val("");
	$('#usuario_status').val("");
	$('#usuario_status').selectpicker('refresh');
	$('#usuario_id_app').val("");
	$('#usuario_id_app').selectpicker('refresh');
	$('#usuario_email').val("");

}

function altera_senha() {


	if ($('#usuario_id').val() == "") {
		alertify.warning('Selecione um cadastro para alterar a senha!');
		return;
	} else if ($('#usuario_senha').val() == "" || $('#usuario_confirma_senha').val() == "") {
		alertify.warning('Preencha a senha e confirme!');
		return;
	} else if ($('#usuario_confirma_senha').val() != $('#usuario_senha').val()) {
		alertify.error('As senhas não conferem! Tente novamente');
		return;

	} else {

		$.post('altera_senha.php', { usuario_id: $('#usuario_id').val(), usuario_senha: $('#usuario_senha').val() }, function (data) {

			if (data == 'cadastro_alterado_com_sucesso') {

				alertify.success('Senha alterada com sucesso!');
				$('#usuario_id').val("");
				$('#usuario_id').trigger('chosen:updated');
				$('#usuario_senha').val("");
				$('#usuario_confirma_senha').val("");
				return;


			}
		});
	}
}

function limpar_alt_senha() {

	$('#usuario_id').val("");
	$('#usuario_id').trigger('chosen:updated');
	$('#usuario_senha').val("");
	$('#usuario_confirma_senha').val("");


}

function busca_app() {

	$.post('busca_app.php').done(function (data) {

		$('#app_id').html(data);
		$('#app_id').selectpicker('refresh');

	});

}


function alterar_app() {

	if ($('#app_nome').val() == "" || $('#descricao_app').val() == "") {

		alertify.warning('Preencha o nome do setor e uma descrição!');
		return;

	} else {

		$.post('altera_app.php', { app_id: $('#app_id').val(), app_nome: $('#app_nome').val(), descricao_app: $('#descricao_app').val() }).done(function (data) {

			if (data == 'cadastro_alterado_com_sucesso') {

				alertify.success('Cadastro alterado com sucesso!');
				$('#app_nome').val("");
				$('#descricao_app').val("");
				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/database-app/database-app.php';
					return;
				}, 1000);
			} else {
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}
}

function limpar_app() {

	$('#app_id').val("");
	$('#app_id').selectpicker('refresh');
	$('#app_nome').val("");
	$('#descricao_app').val("");
}


function recarregar_alt_app() {

	window.location.reload();
}

function busca_so() {

	$.post('busca_types.php').done(function (data) {
		$('#tipo_id').html(data);
		$('#tipo_id').selectpicker('refresh');
	});

}


function alterar_so() {
	if ($('#nome_so').val() == "" || $('#plataforma').val() == "") {
		alertify.error('Preencha todos os campos');
		return;

	} else {

		$.post('altera_types.php', { tipo_id: $('#tipo_id').val(), tipo_nome: $('#nome_so').val(), tipo_plataforma: $('#plataforma').val() }).done(function (data) {

			if (data == 'cadastro_alterado_com_sucesso') {

				alertify.success('Cadastro alterado com sucesso!');
				$('#nome_so').val("");
				$('#plataforma').val("");
				$('#plataforma').trigger("chosen:updated");
				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/database-types/cadastro_types.php';
					return;
				}, 1000);
				return;

			} else if (data == 'ja_existe_so') {

				alertify.warning('Já existe um Tipo de Banco de Dados cadastrado com este nome!');
				return;

			} else {

				alertify.error('Erro ao alterar o cadastro!');
				$('#modal').modal('show');
				return;
			}
		});
	}
}

function limpar_so() {

	$('#tipo_id').val("");
	$('#tipo_id').selectpicker('refresh');
	$('#tipo_nome').val("");
	$('#tipo_plataforma').val("");
	$('#tipo_plataforma').selectpicker('refresh');

}

function alterar_smtp() {

	if ($('#smtp_nome').val() == "" || $('#smtp_email_admin').val() == "" || $('#smtp_porta').val() == "" || $('#smtp_endereco').val() == "" || $('#smtp_senha').val() == ""
		|| $('#smtp_confirma_senha').val() == "") {

		alertify.error('Preencha todos os campos!');
		return;

	} else if ($('#smtp_confirma_senha').val() != $('#smtp_senha').val()) {
		alertify.warning('As senhas não conferem!');
		return;

	} else {

		$.post('altera_smtp.php', { smtp_id: $('#smtp_id').val(), smtp_nome: $('#smtp_nome').val(), smtp_email_admin: $('#smtp_email_admin').val(), smtp_porta: $('#smtp_porta').val(), smtp_endereco: $('#smtp_endereco').val(), smtp_senha: $('#smtp_senha').val() }).done(function (data) {

			if (data == "true") {

				alertify.success('Cadastro alterado com sucesso!');
				$('#smtp_nome').val("");
				$('#smtp_email_admin').val("");
				$('#smtp_porta').val("");
				$('#smtp_porta').trigger("chosen:updated");
				$('#smtp_endereco').val("");
				$('#smtp_senha').val("");
				$('#smtp_confirma_senha').val("");



			} else {
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}
}

function alterar_servidor() {

	if ($('#servidor_nome').val() == "" || $('#servidor_ip').val() == "" || $('#servidor_plataforma').val() == "" || $('#servidor_user_privilegio').val() == "" || $('#servidor_senha_acesso').val() == ""
		|| $('#servidor_nome_compartilhamento').val() == "" || $('#servidor_plataforma').val() == "") {

		alertify.error('Preencha todos os campos!');
		return;

	} else {

		$('#alt_servidor').attr('disabled', true);
		$('#cancelar').attr('disabled', true);


		$.post('altera_servidor.php', { servidor_id: $('#servidor_id').val(), servidor_nome: $('#servidor_nome').val(), servidor_ip: $('#servidor_ip').val(), servidor_plataforma: $('#servidor_plataforma').val(), servidor_user_privilegio: $('#servidor_user_privilegio').val(), servidor_senha_acesso: $('#servidor_senha_acesso').val(), servidor_nome_compartilhamento: $('#servidor_nome_compartilhamento').val(), servidor_plataforma: $('#servidor_plataforma').val() }).done(function (data) {


			if (data == "ja_existe") {
				alertify.error('Já existe este servidor cadastrado em nossa Base de Dados');
				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				return;

			} else if (data == "true") {

				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.success('Cadastro alterado com sucesso!');
				$('#servidor_nome').val("");
				$('#servidor_ip').val("");
				$('#servidor_plataforma').val("");
				$('#servidor_plataforma').trigger("chosen:updated");
				$('#servidor_user_privilegio').val("");
				$('#servidor_senha_acesso').val("");
				$('#servidor_nome_compartilhamento').val("");
				$('#servidor_nome_plataforma').val("");


				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/servidores/servidores.php';
					return;
				}, 1000);

			} else {
				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}
}

function alterar_restore() {

	if ($('#restore_nome').val() == "" || $('#restore_ip').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;
	} else {
		$('#alt_servidor').attr('disabled', true);
		$('#cancelar').attr('disabled', true);

		$.post('altera_restore.php', {
			restore_id: $('#restore_id').val(),
			restore_nome: $('#restore_nome').val(),
			restore_ip: $('#restore_ip').val(),
			restore_user: $('#restore_user').val(),
			restore_senha_acesso: $('#restore_senha_acesso').val()
		}
		).done(function (data) {

			if (data == "ja_existe") {
				alertify.error('Já existe este servidor cadastrado em nossa Base de Dados');
				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				return;

			} else if (data == "true") {
				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.success('Cadastro alterado com sucesso!');
				$('#restore_nome').val("");
				$('#restore_ip').val("");

				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/restore/restore.php';
					return;
				}, 1000);
			} else {
				$('#alt_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.error('Erro ao alterar o cadastro!');
				return;
			}
		});
	}
}

function recarrega_servidor() {

	window.location.href = "servidores.php";
}
