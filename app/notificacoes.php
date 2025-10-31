<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pdo = safekup_db();
$feedback = [
    'type' => null,
    'message' => null,
];

/**
 * Tenta executar uma closure capturando falhas de tabela inexistente.
 */
function safekup_try(callable $callback)
{
    try {
        return $callback();
    } catch (PDOException $exception) {
        if ($exception->getCode() === '42S02') {
            throw new RuntimeException(
                'A tabela `smtp_destinatarios` não foi encontrada. Execute o script em `docs/sql/20240914_create_smtp_destinatarios.sql` antes de usar esta tela.'
            );
        }
        throw $exception;
    }
}

function safekup_validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'create';

        if ($action === 'create') {
            $smtpId = (int) ($_POST['smtp_id'] ?? 0);
            $name = trim((string) ($_POST['nome'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $isActive = isset($_POST['ativo']) ? 1 : 0;

            if ($smtpId <= 0 || $name === '' || $email === '') {
                throw new InvalidArgumentException('Preencha todos os campos obrigatórios.');
            }

            if (!safekup_validate_email($email)) {
                throw new InvalidArgumentException('Informe um endereço de e-mail válido.');
            }

            safekup_try(function () use ($pdo, $smtpId, $name, $email, $isActive) {
                $stmt = $pdo->prepare("
                    INSERT INTO smtp_destinatarios (smtp_id, nome, email, ativo)
                    VALUES (:smtp_id, :nome, :email, :ativo)
                ");
                $stmt->execute([
                    'smtp_id' => $smtpId,
                    'nome'    => $name,
                    'email'   => $email,
                    'ativo'   => $isActive,
                ]);
            });

            $feedback = [
                'type' => 'success',
                'message' => 'Destinatário cadastrado com sucesso.',
            ];
        } elseif ($action === 'toggle') {
    $destinatarioId = (int) ($_POST['destinatario_id'] ?? 0);
    $newStatus = (int) ($_POST['novo_status'] ?? 0);

            if ($destinatarioId <= 0) {
                throw new InvalidArgumentException('Destinatário inválido.');
            }

            safekup_try(function () use ($pdo, $destinatarioId, $newStatus) {
                $stmt = $pdo->prepare("
                    UPDATE smtp_destinatarios
                    SET ativo = :ativo
                    WHERE destinatario_id = :id
                ");
                $stmt->execute([
                    'ativo' => $newStatus,
                    'id'    => $destinatarioId,
                ]);
            });

            $feedback = [
                'type' => 'success',
                'message' => 'Status atualizado com sucesso.',
            ];
        } elseif ($action === 'delete') {
            $destinatarioId = (int) ($_POST['destinatario_id'] ?? 0);

            if ($destinatarioId <= 0) {
                throw new InvalidArgumentException('Destinatário inválido.');
            }

            safekup_try(function () use ($pdo, $destinatarioId) {
                $stmt = $pdo->prepare("
                    DELETE FROM smtp_destinatarios
                    WHERE destinatario_id = :id
                ");
                $stmt->execute(['id' => $destinatarioId]);
            });

            $feedback = [
                'type' => 'success',
                'message' => 'Destinatário removido.',
            ];
        }
    }

    $smtpOptions = safekup_try(function () use ($pdo) {
        $stmt = $pdo->query("
            SELECT smtp_id, smtp_nome, smtp_email_admin
            FROM smtp
            ORDER BY smtp_nome
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });

    $destinatarios = safekup_try(function () use ($pdo) {
        $stmt = $pdo->query("
            SELECT d.destinatario_id,
                   d.nome,
                   d.email,
                   d.ativo,
                   d.criado_em,
                   d.atualizado_em,
                   s.smtp_nome,
                   s.smtp_email_admin
            FROM smtp_destinatarios d
            JOIN smtp s ON s.smtp_id = d.smtp_id
            ORDER BY d.ativo DESC, d.nome ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });
} catch (Throwable $exception) {
    $feedback = [
        'type' => 'error',
        'message' => $exception->getMessage(),
    ];
    $smtpOptions = [];
    $destinatarios = [];
}

safekup_render_header('Safekup — E-mails de Relatório', 'emails');
?>
    <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-white">Destinatários de relatórios</h1>
                <p class="text-sm text-slate-300">
                    Cadastre os responsáveis que devem receber o resumo diário da execução dos backups. A lista é usada
                    quando todos os processos de backup são finalizados.
                </p>
            </div>
        </div>

        <?php if ($feedback['type'] !== null): ?>
            <?php
            $feedbackClasses = [
                'success' => 'border-green-500/50 bg-green-500/10 text-green-200',
                'error'   => 'border-pink-500/50 bg-pink-500/10 text-pink-100',
            ];
            $class = $feedbackClasses[$feedback['type']] ?? 'border-white/10 bg-white/5 text-slate-100';
            ?>
            <div class="mt-6 rounded-xl border px-4 py-3 text-sm <?= $class; ?>">
                <?= safekup_escape($feedback['message'] ?? ''); ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="flex flex-col gap-6 lg:flex-row">
        <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-6 shadow-xl shadow-indigo-900/20 lg:w-80 xl:w-96 lg:shrink-0">
            <h2 class="text-lg font-semibold text-white">Novo destinatário</h2>
            <p class="mt-1 text-sm text-slate-300">Selecione o servidor SMTP e informe nome e e-mail para receber os alertas.</p>

            <form method="post" class="mt-5 space-y-4">
                <input type="hidden" name="action" value="create">

                <label class="block text-sm font-medium text-slate-200">
                    Servidor SMTP
                    <select
                        name="smtp_id"
                        class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        required
                        <?= empty($smtpOptions) ? 'disabled' : ''; ?>
                    >
                        <option value="">Selecione uma conta</option>
                        <?php foreach ($smtpOptions as $option): ?>
                            <option value="<?= (int) $option['smtp_id']; ?>">
                                <?= safekup_escape($option['smtp_nome']); ?> — <?= safekup_escape($option['smtp_email_admin']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block text-sm font-medium text-slate-200">
                    Nome
                    <input
                        type="text"
                        name="nome"
                        maxlength="120"
                        class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        placeholder="Responsável"
                        required
                    >
                </label>

                <label class="block text-sm font-medium text-slate-200">
                    E-mail
                    <input
                        type="email"
                        name="email"
                        maxlength="255"
                        class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        placeholder="responsavel@exemplo.com"
                        required
                    >
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-200">
                    <input
                        type="checkbox"
                        name="ativo"
                        value="1"
                        class="h-4 w-4 rounded border-white/10 bg-slate-900 text-indigo-500 focus:ring-indigo-500/60"
                        checked
                    >
                    Ativo para envio
                </label>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-indigo-500/90 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                    <?= empty($smtpOptions) ? 'disabled' : ''; ?>
                >
                    Adicionar destinatário
                </button>

                <?php if (empty($smtpOptions)): ?>
                    <p class="text-xs text-pink-200">
                        Cadastre primeiro um servidor SMTP em <strong>Serviço de E-mail</strong>.
                    </p>
                <?php endif; ?>
            </form>
        </article>

        <article class="rounded-2xl border border-white/10 bg-slate-900/75 p-6 shadow-xl shadow-indigo-900/20 lg:flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Destinatários cadastrados</h2>
                    <p class="text-sm text-slate-300">
                        Mantemos o histórico com a conta SMTP vinculada. Apenas contatos ativos recebem o relatório final.
                    </p>
                </div>
            </div>

            <?php if (empty($destinatarios)): ?>
                <p class="mt-6 text-sm text-slate-400">Nenhum destinatário cadastrado ainda.</p>
            <?php else: ?>
                <div class="mt-6 rounded-2xl border border-white/10">
                    <table class="w-full table-auto divide-y divide-white/10 text-sm">
                        <thead class="bg-slate-900/70 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold">E-mail</th>
                                <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">SMTP</th>
                                <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">Criado em</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-100">
                            <?php foreach ($destinatarios as $contato): ?>
                                <?php
                                    $fullName = trim((string) ($contato['nome'] ?? ''));
                                    if ($fullName === '') {
                                        $firstName = '—';
                                    } else {
                                        $parts = preg_split('/\s+/', $fullName);
                                        $firstName = $parts[0] ?? $fullName;
                                    }
                                ?>
                                <tr class="<?= $contato['ativo'] ? '' : 'bg-slate-900/30'; ?>">
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-white" title="<?= safekup_escape($fullName); ?>">
                                            <?= safekup_escape($firstName); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-slate-300 break-words"><?= safekup_escape($contato['email']); ?></span>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <span class="text-slate-300">
                                            <?= safekup_escape($contato['smtp_nome']); ?>
                                        </span>
                                        <span class="block text-xs text-slate-500">
                                            <?= safekup_escape($contato['smtp_email_admin']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300 hidden md:table-cell">
                                        <?= safekup_format_datetime($contato['criado_em']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?= safekup_badge($contato['ativo'] ? 'Ativo' : 'Inativo', $contato['ativo'] ? 'success' : 'danger'); ?>
                                        <div class="mt-2 space-y-1 text-xs text-slate-400 md:hidden">
                                            <div><strong>SMTP:</strong> <?= safekup_escape($contato['smtp_nome']); ?></div>
                                            <div><?= safekup_escape($contato['smtp_email_admin']); ?></div>
                                            <?php if (!empty($contato['criado_em'])): ?>
                                                <div><strong>Criado:</strong> <?= safekup_format_datetime($contato['criado_em']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-col items-stretch gap-2 text-sm">
                                            <form method="post">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="destinatario_id" value="<?= (int) $contato['destinatario_id']; ?>">
                                                <input type="hidden" name="novo_status" value="<?= $contato['ativo'] ? 0 : 1; ?>">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-white/10 px-3 py-2 font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white"
                                                >
                                                    <?= $contato['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Remover este destinatário?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="destinatario_id" value="<?= (int) $contato['destinatario_id']; ?>">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-pink-500/40 px-3 py-2 font-semibold text-pink-200 transition hover:bg-pink-500/10"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>
<?php
safekup_render_footer();
