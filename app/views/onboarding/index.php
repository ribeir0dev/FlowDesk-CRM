<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../app/Models/WorkspaceModel.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../../config/db.php';
}

$workspaceModel = new WorkspaceModel($pdo);
$workspace = $workspaceModel->buscarAtual();

if (!$workspace) {
    header('Location: ' . ($base ?? '') . '/dashboard');
    exit;
}

$mensagemErro = isset($_GET['erro']) ? 'Revise os campos obrigatorios para concluir a configuracao inicial da conta.' : null;

$segmentos = [
    'freelancer' => 'Freelancer',
    'studio' => 'Studio criativo',
    'agencia' => 'Agencia',
    'consultoria' => 'Consultoria',
];

$objetivos = [
    'vender_mais' => 'Organizar vendas e pipeline',
    'entregar_melhor' => 'Organizar projetos e entregas',
    'controlar_financas' => 'Controlar financeiro e cobrancas',
];

$tamanhosEquipe = [
    'solo' => 'So eu por enquanto',
    '2_5' => 'De 2 a 5 pessoas',
    '6_10' => 'De 6 a 10 pessoas',
    '11_plus' => 'Mais de 10 pessoas',
];

$volumesClientes = [
    'ate_10' => 'Ate 10 clientes ativos',
    '11_25' => 'Entre 11 e 25 clientes',
    '26_50' => 'Entre 26 e 50 clientes',
    '50_plus' => 'Mais de 50 clientes',
];

$modulosIniciais = [
    'crm' => 'Clientes e CRM',
    'pipeline' => 'Pipeline comercial',
    'projetos' => 'Projetos e entregas',
    'financeiro' => 'Financeiro',
];
?>

<section class="fd-onboarding-lovable" data-onboarding-stage>
    <div class="fd-onboarding-lovable-inner">
        <div class="fd-onboarding-lovable-head" data-onboarding-intro>
            <span class="fd-onboarding-lovable-kicker" data-stagger-item>Configuracao inicial</span>
            <h1 data-stagger-item>Vamos preparar o Workspace para o primeiro acesso real</h1>
        </div>

        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger fd-onboarding-lovable-alert" role="alert" data-stagger-item>
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= ($base ?? '') ?>/onboarding/salvar" class="fd-onboarding-lovable-form" data-onboarding-form>
            <div class="fd-onboarding-lovable-card">
                <div class="fd-onboarding-lovable-step-mobile" data-step-progress-mobile data-stagger-item>Etapa 1 de 3</div>
                <div class="fd-onboarding-panel is-active" data-step-panel="1">
                    <div class="fd-onboarding-lovable-card-head" data-stagger-group>
                        <span class="fd-onboarding-lovable-icon">
                            <i class="ri-id-card-line"></i>
                        </span>
                        <div>
                            <h2>Contexto principal do seu workspace</h2>
                            <p>Esses dois campos sao a base do seu workspace: como ele se chama e qual tipo de operacao ele representa.</p>
                        </div>
                    </div>

                    <div class="fd-onboarding-lovable-grid fd-onboarding-lovable-grid-2" data-stagger-group>
                        <div class="fd-settings-field" data-stagger-item>
                            <label class="form-label small">Nome do workspace</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-shapes-line"></i></span>
                                <input type="text" name="workspace_nome" class="form-control fd-onboarding-control" value="<?= htmlspecialchars((string) ($workspace['nome'] ?? '')) ?>" placeholder="Meu Workspace" required>
                            </div>
                        </div>

                        <div class="fd-settings-field" data-stagger-item>
                            <label class="form-label small">Segmento principal</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-home-5-line"></i></span>
                                <select name="segmento" class="form-select fd-onboarding-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($segmentos as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (($workspace['segmento'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fd-onboarding-panel" data-step-panel="2" hidden>
                    <div class="fd-onboarding-lovable-card-head" data-stagger-group>
                        <span class="fd-onboarding-lovable-icon">
                            <i class="ri-route-line"></i>
                        </span>
                        <div>
                            <h2>Momento atual da operacao</h2>
                            <p>Agora damos mais contexto ao produto: o que mais importa hoje, tamanho do time e volume atual da base.</p>
                        </div>
                    </div>

                    <div class="fd-onboarding-lovable-grid fd-onboarding-lovable-grid-3" data-stagger-group>
                        <div class="fd-settings-field fd-settings-field-span-2 fd-onboarding-span-2" data-stagger-item>
                            <label class="form-label small">Objetivo principal</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-focus-3-line"></i></span>
                                <select name="objetivo_principal" class="form-select fd-onboarding-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($objetivos as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (($workspace['objetivo_principal'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="fd-settings-field" data-stagger-item>
                            <label class="form-label small">Tamanho da Equipe</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-team-line"></i></span>
                                <select name="tamanho_equipe" class="form-select fd-onboarding-control">
                                    <option value="">Opcional</option>
                                    <?php foreach ($tamanhosEquipe as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (($workspace['onboarding_tamanho_equipe'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="fd-settings-field" data-stagger-item>
                            <label class="form-label small">Volume de Clientes Ativos</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-group-line"></i></span>
                                <select name="volume_clientes" class="form-select fd-onboarding-control">
                                    <option value="">Opcional</option>
                                    <?php foreach ($volumesClientes as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (($workspace['onboarding_volume_clientes'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fd-onboarding-panel" data-step-panel="3" hidden>
                    <div class="fd-onboarding-lovable-card-head" data-stagger-group>
                        <span class="fd-onboarding-lovable-icon">
                            <i class="ri-settings-4-line"></i>
                        </span>
                        <div>
                            <h2>Setup inicial opcional</h2>
                            <p>Essa etapa ajuda a deixar o primeiro dashboard mais inteligente.</p>
                        </div>
                    </div>

                    <div class="fd-onboarding-lovable-grid fd-onboarding-lovable-grid-1" data-stagger-group>
                        <div class="fd-settings-field" data-stagger-item>
                            <label class="form-label small">Modulo inicial mais importante</label>
                            <div class="fd-onboarding-input-wrap">
                                <span class="fd-onboarding-input-icon"><i class="ri-layout-grid-line"></i></span>
                                <select name="modulo_inicial" class="form-select fd-onboarding-control">
                                    <option value="">Opcional</option>
                                    <?php foreach ($modulosIniciais as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (($workspace['onboarding_modulo_inicial'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fd-onboarding-lovable-footer" data-stagger-group>
                    <div class="fd-onboarding-lovable-footer-left">
                        <button type="button" class="fd-btn-secondary fd-onboarding-lovable-back fd-btn-spring" data-step-back hidden>
                            <i class="ri-arrow-left-line"></i>
                            <span>Voltar</span>
                        </button>
                        <span class="fd-onboarding-lovable-step" data-step-progress>Etapa 1 de 3</span>
                    </div>

                    <div class="fd-onboarding-lovable-footer-right">
                        <a href="<?= ($base ?? '') ?>/logout" class="fd-btn-ghost fd-btn-spring">Sair Agora</a>
                        <button type="button" class="fd-btn-primary fd-onboarding-lovable-next fd-btn-spring" data-step-next>
                            <span>Continuar</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                        <button type="submit" class="fd-btn-primary fd-onboarding-lovable-submit fd-btn-spring" data-step-submit hidden>
                            <span>Entrar no Workspace</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-onboarding-form]');
    if (!form) {
        return;
    }

    const stage = document.querySelector('[data-onboarding-stage]');
    const card = form.querySelector('.fd-onboarding-lovable-card');
    const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
    const backButton = form.querySelector('[data-step-back]');
    const nextButton = form.querySelector('[data-step-next]');
    const submitButton = form.querySelector('[data-step-submit]');
    const progress = document.querySelector('[data-step-progress]');
    const progressMobile = document.querySelector('[data-step-progress-mobile]');
    let currentStep = 1;
    let isTransitioning = false;
    let lastStep = 1;

    const requiredByStep = {
        1: ['input[name="workspace_nome"]', 'select[name="segmento"]'],
        2: ['select[name="objetivo_principal"]'],
        3: []
    };

    function applyStep(step) {
        lastStep = currentStep;
        currentStep = step;

        panels.forEach((panel) => {
            const panelStep = Number(panel.getAttribute('data-step-panel'));
            const isActive = panelStep === step;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);

            if (isActive) {
                panel.classList.remove('is-entering-forward', 'is-entering-backward');
                panel.classList.add(step > lastStep ? 'is-entering-forward' : 'is-entering-backward');

                window.setTimeout(() => {
                    panel.classList.remove('is-entering-forward', 'is-entering-backward');
                }, 520);

                panel.querySelectorAll('[data-stagger-item]').forEach((item, index) => {
                    item.style.setProperty('--fd-stagger-delay', (index * 70) + 'ms');
                });
            }
        });

        if (progress) {
            progress.textContent = 'Etapa ' + step + ' de 3';
        }

        if (progressMobile) {
            progressMobile.textContent = 'Etapa ' + step + ' de 3';
        }

        if (backButton) {
            backButton.hidden = step === 1;
        }

        if (nextButton) {
            nextButton.hidden = step === 3;
        }

        if (submitButton) {
            submitButton.hidden = step !== 3;
        }
    }

    function showStep(step, immediate = false) {
        if (step === currentStep && !immediate) {
            applyStep(step);
            return;
        }

        if (!card) {
            applyStep(step);
            return;
        }

        if (immediate) {
            applyStep(step);
            return;
        }

        if (isTransitioning) {
            return;
        }

        isTransitioning = true;
        card.classList.remove('is-switching-in');
        card.classList.add('is-switching-out');

        window.setTimeout(() => {
            applyStep(step);
            card.classList.remove('is-switching-out');
            card.classList.add('is-switching-in');

            window.setTimeout(() => {
                card.classList.remove('is-switching-in');
                isTransitioning = false;
            }, 420);
        }, 170);
    }

    function validateCurrentStep() {
        const selectors = requiredByStep[currentStep] || [];
        for (const selector of selectors) {
            const field = form.querySelector(selector);
            if (field && !field.reportValidity()) {
                return false;
            }
        }
        return true;
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            if (!validateCurrentStep()) {
                return;
            }

            if (currentStep < 3) {
                showStep(currentStep + 1);
            }
        });
    }

    if (backButton) {
        backButton.addEventListener('click', function () {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
    }

    requestAnimationFrame(() => {
        if (stage) {
            stage.classList.add('is-mounted');
        }
    });

    showStep(1, true);
});
</script>
