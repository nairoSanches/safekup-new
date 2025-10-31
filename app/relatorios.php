<?php
require_once __DIR__ . '/bootstrap.php';

$db = safekup_db();

$type = $_GET['type'] ?? 'dumps';
$datasets = [
    'dumps' => [
        'table' => 'historico_dumps',
        'title' => 'Dumps realizados',
        'toggle_label' => 'Ver restores',
        'toggle_type' => 'restores',
        'empty_message' => 'Nenhum dump registrado para os filtros informados.'
    ],
    'restores' => [
        'table' => 'historico_restores',
        'title' => 'Restores realizados',
        'toggle_label' => 'Ver dumps',
        'toggle_type' => 'dumps',
        'empty_message' => 'Nenhum restore registrado para os filtros informados.'
    ]
];

if (!isset($datasets[$type])) {
    $type = 'dumps';
}

$config = $datasets[$type];
$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));

$bancoFilter = trim($_GET['banco'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$statusStmt = $db->query("SELECT DISTINCT status FROM {$config['table']} ORDER BY status");
$statusOptions = array_values(array_filter($statusStmt->fetchAll(PDO::FETCH_COLUMN) ?? [], static function ($value) {
    return $value !== null && $value !== '';
}));

$conditions = [];
$params = [];

if ($bancoFilter !== '') {
    $conditions[] = 'bd_nome_usuario LIKE :banco';
    $params[':banco'] = '%' . $bancoFilter . '%';
}

if ($statusFilter !== '') {
    $conditions[] = 'status = :status';
    $params[':status'] = $statusFilter;
}

$whereClause = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';
$baseSql = "FROM {$config['table']}";

$countStmt = $db->prepare("SELECT COUNT(*) {$baseSql}{$whereClause}");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$dataSql = "
    SELECT id, bd_nome_usuario, bd_ip, data_execucao, status, tamanho_arquivo, tempo_decorrido
    {$baseSql}{$whereClause}
    ORDER BY data_execucao DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($dataSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$rowCount = count($rows);

$displayStart = $totalRecords > 0 ? ($offset + 1) : 0;
$displayEnd = $totalRecords > 0 ? ($offset + $rowCount) : 0;

$queryBase = ['type' => $type];
if ($bancoFilter !== '') { $queryBase['banco'] = $bancoFilter; }
if ($statusFilter !== '') { $queryBase['status'] = $statusFilter; }

$buildPageUrl = static function (int $targetPage) use ($queryBase): string {
    $params = $queryBase;
    $params['page'] = max(1, $targetPage);
    return '?' . http_build_query($params);
};

$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);
$prevDisabled = $page <= 1;
$nextDisabled = $page >= $totalPages;

safekup_render_header('Safekup — Relatórios', 'relatorios');
?>
    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-indigo-900/20">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Relatórios</h2>
                <p class="text-sm text-slate-300">
                    Consulte os registros recentes de <?= safekup_escape(strtolower($config['title'])); ?> com filtros por banco e status.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($datasets as $datasetKey => $dataset): ?>
                    <?php
                        $isActive = $datasetKey === $type;
                        $toggleClasses = $isActive
                            ? 'bg-indigo-500/80 text-white border-indigo-400/70'
                            : 'text-slate-200 border-white/10 hover:border-indigo-400/60 hover:text-white';
                    ?>
                    <a href="?type=<?= safekup_escape($datasetKey); ?>"
                       class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition <?= $toggleClasses; ?>">
                        <?= safekup_escape(ucfirst($datasetKey)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="get" class="grid gap-4 rounded-xl border border-white/10 bg-slate-900/60 p-4 md:grid-cols-4">
            <input type="hidden" name="type" value="<?= safekup_escape($type); ?>" />

            <label class="flex flex-col gap-2 text-sm text-slate-200 md:col-span-2">
                <span>Banco</span>
                <input type="text"
                       name="banco"
                       value="<?= safekup_escape($bancoFilter); ?>"
                       placeholder="nome do banco..."
                       class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
            </label>

            <label class="flex flex-col gap-2 text-sm text-slate-200">
                <span>Status</span>
                <select name="status"
                        class="rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $statusOption): ?>
                        <option value="<?= safekup_escape($statusOption); ?>" <?= $statusFilter === $statusOption ? 'selected' : ''; ?>>
                            <?= safekup_escape($statusOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="flex items-end gap-3">
                <button type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    <i class="fa fa-filter"></i>
                    <span>Aplicar filtros</span>
                </button>
                <a href="?type=<?= safekup_escape($type); ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-indigo-400/60 hover:text-white">
                    <i class="fa fa-undo"></i>
                    <span>Limpar</span>
                </a>
            </div>
        </form>
    </section>

    <section class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-xl shadow-indigo-900/20">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white"><?= safekup_escape($config['title']); ?></h3>
                <p class="text-xs text-slate-400">
                    <?php if ($totalRecords > 0): ?>
                        Exibindo <?= safekup_escape((string)$displayStart); ?>-<?= safekup_escape((string)$displayEnd); ?> de <?= safekup_escape((string)$totalRecords); ?> registros (página <?= safekup_escape((string)$page); ?> de <?= safekup_escape((string)$totalPages); ?>).
                    <?php else: ?>
                        Nenhum registro encontrado para os filtros selecionados.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center gap-2 text-sm">
                <a href="<?= safekup_escape($buildPageUrl($prevPage)); ?>"
                   class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 <?= $prevDisabled ? 'pointer-events-none opacity-40 text-slate-400' : 'text-slate-200 hover:border-indigo-400/60 hover:text-white'; ?>">
                    <i class="fa fa-chevron-left text-xs"></i>
                    <span>Anterior</span>
                </a>
                <span class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-200">
                    Página <?= safekup_escape((string)$page); ?> de <?= safekup_escape((string)$totalPages); ?>
                </span>
                <a href="<?= safekup_escape($buildPageUrl($nextPage)); ?>"
                   class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 <?= $nextDisabled ? 'pointer-events-none opacity-40 text-slate-400' : 'text-slate-200 hover:border-indigo-400/60 hover:text-white'; ?>">
                    <span>Próxima</span>
                    <i class="fa fa-chevron-right text-xs"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto rounded-xl border border-white/5">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Banco</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Execução</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tempo</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Tamanho</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                <?= safekup_escape($config['empty_message']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $statusRaw = (string) ($row['status'] ?? '');
                                $isOk = in_array(strtoupper($statusRaw), ['SUCESSO', 'OK'], true);
                                $statusLabel = $statusRaw !== '' ? $statusRaw : 'Indefinido';
                            ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-medium"><?= safekup_escape($row['bd_nome_usuario']); ?></span>
                                        <span class="text-xs text-slate-400 font-mono"><?= safekup_escape($row['bd_ip']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><?= safekup_escape(safekup_format_datetime($row['data_execucao'])); ?></td>
                                <td class="px-4 py-3">
                                    <?= safekup_badge(
                                        $statusLabel,
                                        $isOk ? 'success' : 'danger'
                                    ); ?>
                                </td>
                                <td class="px-4 py-3"><?= safekup_escape($row['tempo_decorrido'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= safekup_escape(safekup_format_size($row['tamanho_arquivo'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php
safekup_render_footer();
