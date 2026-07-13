<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base . '/');
    exit;
}

$mod = $_GET['mod'] ?? 'dashboard';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$allowedModules = [
    'dashboard',
    'clientes',
    'cliente',
    'projetos',
    'projeto',
    'pipeline',
    'orcamentos',
    'orcamento',
    'orcamento-form',
    'financeiro',
    'hospedagens',
    'codigos',
    'codigo',
    'configuracoes',
    'onboarding',
];

if (!in_array($mod, $allowedModules, true)) {
    $mod = 'dashboard';
}

$pageTitles = [
    'dashboard' => 'Dashboard',
    'clientes' => 'Clientes',
    'cliente' => 'Cliente',
    'projetos' => 'Projetos',
    'projeto' => 'Projeto',
    'pipeline' => 'Pipeline',
    'orcamentos' => 'Propostas',
    'orcamento' => 'Orcamento',
    'orcamento-form' => 'Nova proposta',
    'financeiro' => 'Financeiro',
    'hospedagens' => 'Hospedagens',
    'codigos' => 'Codigos',
    'codigo' => 'Codigo',
    'configuracoes' => 'Configuracoes',
    'onboarding' => 'Onboarding',
];

$pageTitle = $pageTitles[$mod] ?? 'Painel';

$viewMap = [
    'dashboard' => __DIR__ . '/../dashboard/index.php',
    'clientes' => __DIR__ . '/../clientes/index.php',
    'cliente' => __DIR__ . '/../clientes/partials/show.php',
    'projetos' => __DIR__ . '/../projetos/index.php',
    'projeto' => __DIR__ . '/../projetos/detalhe.php',
    'pipeline' => __DIR__ . '/../pipeline/index.php',
    'orcamentos' => __DIR__ . '/../orcamentos/index.php',
    'orcamento' => __DIR__ . '/../orcamentos/detalhe.php',
    'orcamento-form' => __DIR__ . '/../orcamentos/form.php',
    'financeiro' => __DIR__ . '/../financeiro/index.php',
    'hospedagens' => __DIR__ . '/../hospedagens/index.php',
    'codigos' => __DIR__ . '/../codigos/index.php',
    'codigo' => __DIR__ . '/../codigos/detalhe.php',
    'configuracoes' => __DIR__ . '/../configuracoes/index.php',
    'onboarding' => __DIR__ . '/../onboarding/index.php',
];

$viewFile = $viewMap[$mod] ?? $viewMap['dashboard'];

$GLOBALS['flowdesk_mod'] = $mod;
$GLOBALS['flowdesk_id'] = $id;

$moduleData = [
    'mod' => $mod,
    'id' => $id,
];

if ($mod === 'onboarding') {
    include __DIR__ . '/partials/header-onboarding.php';
} else {
    include __DIR__ . '/partials/header-painel.php';
}
?>

<?php if (file_exists($viewFile)): ?>
    <?php include $viewFile; ?>
<?php else: ?>
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <i class="ri-error-warning-line text-2xl"></i>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    Modulo nao encontrado
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    A view do modulo <strong><?= htmlspecialchars($mod) ?></strong> nao foi localizada.
                </p>

                <a href="<?= $base ?>/dashboard"
                    class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-violet-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-violet-700">
                    <i class="ri-arrow-left-line"></i>
                    <span>Voltar ao dashboard</span>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
if ($mod === 'onboarding') {
    include __DIR__ . '/partials/footer-onboarding.php';
} else {
    include __DIR__ . '/partials/footer.php';
}
?>
