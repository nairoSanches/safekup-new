function cadastrar_so() {

	if ($('#nome_so').val() == "" || $('#plataforma').val() == "") {

		alertify.error('Preencha todos os campos');
		return;

	} else {
		$('#cad_so').attr('disabled', true);
		$.post('cadastrar_types.php', { nome_so: $('#nome_so').val(), plataforma: $('#plataforma').val() }, function (data) {
			if (data == 'ja_existe_so') {
				alertify.warning('Já existe um Tipo de Banco de Dados cadastrado com este nome!');
				return;
			} else if (data == 'erro_ao_cadastrar') {
				alertify.error('Erro ao cadastrar o Tipo de Banco de Dados!');
				return;
			} else if (data == 'cadastro_realizado_com_sucesso') {

				alertify.success('Cadastro realizado com sucesso!');
				$('#cad_so').attr('disabled', false);
				$('#nome_so').val("");
				$('#plataforma').val("");
				$('#plataforma').trigger("chosen:updated");

				setTimeout(function () {
					window.location.href = '/safekup/php/database-types/cadastro_types.php';
					return;
				}, 1000);
			}
		});
	}
}

function limpar_so() {

	$('#nome_so').val("");
	$('#plataforma').val("");
	$('#plataforma').selectpicker('refresh');
}

function limpar() {
	$('#nome_documento').val("");
	$('#sos').val("");
}

function cadastrar_usuario() {

	if ($('#nome_usuario').val() == "" || $('#login').val() == "" || $('#setor').val() == "" || $('#usuario_email').val() == "" || $('#senha').val() == ""
		|| $('#confirma_senha').val() == "" || $('#status').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;

	} else if ($('#senha').val() != $('#confirma_senha').val()) {

		alertify.warning('As senhas não correspondem! Tente novamente.');
		return;

	} else {
		$('#cadastrar_usuario').attr('disabled', true);

		$.post('cadastrar_usuario.php', { nome_usuario: $('#nome_usuario').val(), login: $('#login').val(), setor: $('#setor').val(), senha: $('#senha').val(), usuario_email: $('#usuario_email').val(), status: $('#status').val() }, function (data) {

			if (data == 'ja_existe_login') {
				$('#alert').html(ja_existe_login);
				$('#modal').modal('show');
				return;

			} else if (data == 'cadastro_realizado_com_sucesso') {
				$('#cadastrar_usuario').attr('disabled', false);
				alertify.success('Cadastro realizado com sucesso!');
				$('#nome_usuario').val("");
				$('#login').val("");
				$('#setor').val("");
				$('#setor').trigger("chosen:updated");
				$('#senha').val("");
				$('#confirma_senha').val("");
				$('#status').val("");
				$('#status').trigger("chosen:updated");

				$('#usuario_email').val("");
				setTimeout(function () {
					window.location.href = '/safekup/php/ssh/ssh.php';
					return;
				}, 1000);

			} else {
				$('#alterar_usuario').attr('disabled', false);
				alertify.error('Erro ao realizar o cadastro!');
				return;
			}
		});
	}
}

function limpar_usu() {

	$('#nome_usuario').val("");
	$('#login').val("");
	$('#setor').val("");
	$('#setor').selectpicker('refresh');
	$('#senha').val("");
	$('#confirma_senha').val("");
	$('#usuario_email').val("");
	$('#status').val("");
	$('#status').selectpicker('refresh');


}

function cadastrar_ssh() {

	if ($('#ssh_ip').val() == "" || $('#ssh_user').val() == "" || $('#ssh_pass').val() == ""
		|| $('#confirm_ssh').val() == "" || $('#ssh_status').val() == "") {
		alertify.error('Preencha todos os campos!');
		return;

	} else if ($('#ssh_pass').val() != $('#confirm_ssh').val()) {

		alertify.warning('As senhas não correspondem! Tente novamente.');
		return;

	} else {
		$('#cadastrar_ssh').attr('disabled', true);

		$.post('cadastrar_ssh.php', { ssh_ip: $('#ssh_ip').val(), ssh_user: $('#ssh_user').val(), ssh_pass: $('#ssh_pass').val(), ssh_status: $('#ssh_status').val() }, function (data) {

			if (data.sucesso == 'ja_existe_login') {
				$('#alert').html(ja_existe_login);
				$('#modal').modal('show');
				return;

			} else if (data.sucesso === 'cadastro_realizado_com_sucesso') {
				$('#cadastrar_ssh').attr('disabled', false);
				alertify.success('Cadastro realizado com sucesso!');
				$('#ssh_ip').val("");
				$('#ssh_user').val("");
				$('#ssh_pass').val("");
				$('#confirm_ssh').val("");
				$('#ssh_status').val("");
				$('#ssh_status').trigger("chosen:updated");
				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/ssh/ssh.php';
					return;
				}, 1000);

			} else {
				$('#cadastrar_ssh').attr('disabled', false);
				alertify.error('Erro ao realizar o cadastro!');
				return;
			}
		});
	}
}

function limpar_usu() {

	$('#nome_usuario').val("");
	$('#login').val("");
	$('#setor').val("");
	$('#setor').selectpicker('refresh');
	$('#senha').val("");
	$('#confirma_senha').val("");
	$('#usuario_email').val("");
	$('#status').val("");
	$('#status').selectpicker('refresh');


}

function limpar_app() {
	$('#nome_app').val("");
	$('#descricao_app').val("")
}


function cadastrar_app() {

	if ($('#nome_app').val() == "" || $('#descricao_app').val() == "") {

		alertify.warning('Informe o nome do setor e uma descrição');
		return;

	} else {

		$.post('cadastrar_app.php', { nome_app: $('#app_nome').val(), descricao_app: $('#descricao_app').val() }, function (data) {

			if (data == 'ja_existe_app') {

				alertify.warning('Já existe um setor cadastrado com este nome!');
				return;

			} else if (data == 'cadastro_realizado_com_sucesso') {

				alertify.success('Cadastro realizado com sucesso!');
				$('#app_nome').val("");
				$('#descricao_app').val("");
				setTimeout(function () {
					window.location.href = '/safekup/php/database-app/database-app.php';
					return;
				}, 1000);

			} else {

				alertify.error('Erro ao realizar o cadastro!');
				return;

			}

		})
	}
}

function cadastrar_bd() {

	if ($('#dia0').is(':checked') == false && $('#dia1').is(':checked') == false && $('#dia2').is(':checked') == false && $('#dia3').is(':checked') == false &&
		$('#dia4').is(':checked') == false && $('#dia5').is(':checked') == false && $('#dia6').is(':checked') == false) {

		alertify.error('Informe pelo menos um dia para ser feito backup deste computador');
		return;
	}

	$.post('cadastrar_bd.php', {
		bd_nome_usuario: $('#bd_nome_usuario').val(),
		bd_login: $('#bd_login').val(),
		bd_email: $('#bd_email').val(),
		bd_senha: $('#bd_senha').val(),
		bd_ip: $('#bd_ip').val(),
		bd_porta: $('#bd_porta').val(),
		tipo_id: $('#tipo_id').val(),
		dia0: $('#dia0').is(':checked') ? '1' : '0',
		dia1: $('#dia1').is(':checked') ? '1' : '0',
		dia2: $('#dia2').is(':checked') ? '1' : '0',
		dia3: $('#dia3').is(':checked') ? '1' : '0',
		dia4: $('#dia4').is(':checked') ? '1' : '0',
		dia5: $('#dia5').is(':checked') ? '1' : '0',
		dia6: $('#dia6').is(':checked') ? '1' : '0',
		bd_hora_backup: $('#bd_hora_backup').val() || '0',
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
		bd_container: $('#bd_container').val()

	}, function (response) {
		try {
			var data = JSON.parse(response);

			if (data.sucesso === 'cadastro_realizado_com_sucesso') {
				alertify.success('Cadastro realizado com sucesso!');
				$('.form-control').val("");
				$('.chosen-select').trigger("chosen:updated");
				$('.checkbox').prop('checked', false);
				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/database-management/db_management.php';
					return;
				}, 1000);
			} else if (data.erro) {
				alertify.error('Erro: ' + data.erro);
			} else {
				alertify.error('Erro desconhecido ao realizar o cadastro.');
			}
		} catch (e) {
			console.error('Erro ao processar a resposta JSON:', e);
			alertify.error('Erro ao processar a resposta do servidor.');
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		console.error('Erro na solicitação:', textStatus, errorThrown);
		alertify.error('Erro na solicitação: ' + textStatus);
	});
}


function limpar_comp() {

	$('#bd_id').val("");
	$('#bd_nome_usuario').val("");
	$('#bd_login').val("");
	$('#bd_senha').val("");
	$('#bd_usuario_adm').val("");
	$('#bd_ip').val("");
	$('#bd_porta').val("");
	$('#tipo_id').val("");
	$('#tipo_id').selectpicker('refresh');
	$('#bd_hora_backup').val("");
	$('#bd_hora_backup').selectpicker('refresh');
	$('#bd_servidor_backup').val("");
	$('#bd_servidor_backup').selectpicker('refresh');
	$('#bd_liga_computador').val("");
	$('#bd_liga_computador').selectpicker('refresh');
	$('#bd_desliga_computador').val("");
	$('#bd_desliga_computador').selectpicker('refresh');
	$('#bd_app').val("");
	$('#bd_app').selectpicker('refresh');
	$('#todos_os_dias').prop("checked", false);
	$('#dia0').prop("checked", false);
	$('#dia1').prop("checked", false);
	$('#dia2').prop("checked", false);
	$('#dia3').prop("checked", false);
	$('#dia4').prop("checked", false);
	$('#dia5').prop("checked", false);
	$('#dia6').prop("checked", false);
	$('#documento_id').val("");
	$('#documento_id').selectpicker('refresh');
	$('#extensao_arquivo_id').val("");
	$('#extensao_arquivo_id').selectpicker('refresh');
	$('#bd_backup_ativo').val("");
	$('#bd_backup_ativo').selectpicker('refresh');
	$('#servidor_id').val("");
	$('#servidor_id').selectpicker('refresh');


}

function cadastrar_smtp() {

	if ($('#smtp_nome').val() == "" || $('#smtp_email_admin').val() == "" || $('#smtp_porta').val() == "" || $('#smtp_endereco').val() == "" || $('#smtp_senha').val() == ""
		|| $('#smtp_confirma_senha').val() == "") {

		alertify.error('Preencha todos os campos!');
		return;

	} else if ($('#smtp_confirma_senha').val() != $('#smtp_senha').val()) {
		alertify.warning('As senhas não conferem!');
		return;

	} else {
		$('#cad_smtp').attr('disabled', true);
		$.post('cadastrar_smtp.php', { smtp_nome: $('#smtp_nome').val(), smtp_email_admin: $('#smtp_email_admin').val(), smtp_porta: $('#smtp_porta').val(), smtp_endereco: $('#smtp_endereco').val(), smtp_senha: $('#smtp_senha').val() }).done(function (data) {

			if (data == "true") {

				alertify.success('Cadastro realizado com sucesso!');
				$('#smtp_nome').val("");
				$('#smtp_email_admin').val("");
				$('#smtp_porta').val("");
				$('#smtp_porta').trigger("chosen:updated");
				$('#smtp_endereco').val("");
				$('#smtp_senha').val("");
				$('#smtp_confirma_senha').val("");

				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/servico_email/servidor_smtp.php';
					return;
				}, 1000);

			} else {
				alertify.error('Erro ao realizar o cadastro!');
				return;
			}
		});
	}
}


function cadastrar_servidor() {

	if ($('#servidor_nome').val() == "" || $('#servidor_ip').val() == "" || $('#servidor_plataforma').val() == "" || $('#servidor_user_privilegio').val() == "" || $('#servidor_senha_acesso').val() == ""
		|| $('#servidor_nome_compartilhamento').val() == "" || $('#servidor_plataforma').val() == "") {

		alertify.error('Preencha todos os campos!');
		return;

	} else {

		$('#cad_servidor').attr('disabled', true);
		$('#cancelar').attr('disabled', true);


		$.post('cadastrar_servidor.php', { servidor_nome: $('#servidor_nome').val(), servidor_ip: $('#servidor_ip').val(), servidor_plataforma: $('#servidor_plataforma').val(), servidor_user_privilegio: $('#servidor_user_privilegio').val(), servidor_senha_acesso: $('#servidor_senha_acesso').val(), servidor_nome_compartilhamento: $('#servidor_nome_compartilhamento').val(), servidor_plataforma: $('#servidor_plataforma').val() }).done(function (data) {

			if (data == "ja_existe") {
				alertify.error('Já existe este servidor cadastrado em nossa Base de Dados');
				$('#cad_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				return;

			} else if (data == "true") {

				$('#cad_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.success('Cadastro realizado com sucesso!');
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
				alertify.error('Erro ao realizar o cadastro!');
				return;
			}
		});
	}
}

function cadastrar_restore() {

	if ($('#servidor_nome').val() == "" || $('#servidor_ip').val() == "") {

		alertify.error('Preencha todos os campos!');
		return;

	} else {

		$('#cad_servidor').attr('disabled', true);
		$('#cancelar').attr('disabled', true);

		$.post('cadastrar_restore.php', { restore_nome: $('#restore_nome').val(), restore_ip: $('#restore_ip').val() }).done(function (data) {

			if (data == "ja_existe") {
				alertify.error('Já existe este servidor cadastrado em nossa Base de Dados');
				$('#cad_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				return;

			} else if (data == "true") {

				$('#cad_servidor').attr('disabled', false);
				$('#cancelar').attr('disabled', false);
				alertify.success('Cadastro realizado com sucesso!');
				$('#restore_nome').val("");
				$('#restore_ip').val("");

				$('.gif').prop('hidden', false);
				setTimeout(function () {
					window.location.href = '/safekup/php/restore/restore.php';
					return;
				}, 1000);
			} else {
				alertify.error('Erro ao realizar o cadastro!');
				return;
			}
		});
	}
}

function recarrega_servidor() {

	window.location.href = "servidores.php";
}
