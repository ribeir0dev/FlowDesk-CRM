<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($base ?? '') . '/dashboard');
    exit;
}

$pageTitle = 'FlowDesk | CRM para operacao, clientes e financeiro';
include __DIR__ . '/../../../app/views/layouts/partials/header-login.php';
?>

<div class="fd-public-shell">
    <header class="fd-public-topbar">
        <a href="<?= ($base ?? '') ?>/" class="fd-public-brand">
            <img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt="FlowDesk" class="fd-public-brand-logo">
            <span class="fd-public-brand-copy">
                <strong>FlowDesk</strong>
                <small>CRM | SaaS Workspace</small>
            </span>
        </a>

        <nav class="fd-public-nav">
            <a href="#recursos">Recursos</a>
            <a href="#modulos">Modulos</a>
            <a href="#onboarding">Onboarding</a>
        </nav>

        <div class="fd-public-topbar-actions">
            <button type="button" class="fd-btn-secondary fd-public-anchor-btn" data-auth-target="login">
                <i class="ri-login-box-line"></i>
                <span>Entrar</span>
            </button>

            <button type="button" class="fd-btn-primary fd-public-anchor-btn" data-auth-target="signup">
                <i class="ri-rocket-2-line"></i>
                <span>Criar conta</span>
            </button>
        </div>
    </header>

    <main class="fd-public-main">
        <section class="fd-landing-hero">
            <div class="fd-landing-hero-copy">
                <div class="fd-landing-chip">
                    <i class="ri-star-smile-line"></i>
                    <span>CRM operacional pronto para virar SaaS</span>
                </div>

                <h1>Clientes, pipeline, projetos e financeiro em um unico workspace.</h1>

                <p>
                    O FlowDesk centraliza a operacao de freelancers, studios e equipes pequenas com uma experiencia mais limpa,
                    comercial e preparada para escalar como produto.
                </p>

                <div class="fd-landing-cta-group">
                    <button type="button" class="fd-btn-primary fd-public-anchor-btn" data-auth-target="signup">
                        <i class="ri-flashlight-line"></i>
                        <span>Comecar agora</span>
                    </button>

                    <button type="button" class="fd-btn-secondary fd-public-anchor-btn" data-auth-target="login">
                        <i class="ri-play-circle-line"></i>
                        <span>Acessar minha conta</span>
                    </button>
                </div>

                <div class="fd-landing-kpis">
                    <article class="fd-landing-kpi">
                        <strong>CRM</strong>
                        <span>Clientes, status e relacoes em uma so tela.</span>
                    </article>
                    <article class="fd-landing-kpi">
                        <strong>Ops</strong>
                        <span>Projetos, tarefas e financeiro com contexto operacional.</span>
                    </article>
                    <article class="fd-landing-kpi">
                        <strong>SaaS</strong>
                        <span>Base visual e tecnica pronta para crescer como produto.</span>
                    </article>
                </div>
            </div>

            <div class="fd-auth-shell" id="auth-shell" data-auth-shell>
                <div class="fd-auth-card">
                    <div class="fd-auth-card-head">
                        <div>
                            <p class="fd-page-eyebrow">Workspace</p>
                            <h2 id="auth-title">Acesse sua conta</h2>
                            <p id="auth-subtitle" class="fd-card-subtitle">Entre para continuar sua operacao no FlowDesk.</p>
                        </div>

                        <div class="fd-auth-toggle">
                            <button type="button" id="btn-login" class="is-active">Entrar</button>
                            <button type="button" id="btn-criar">Criar conta</button>
                        </div>
                    </div>

                    <form id="form-login" method="POST" action="<?= ($base ?? '') ?>/login" class="fd-auth-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                        <label class="fd-auth-field">
                            <span>Usuario ou e-mail</span>
                            <div class="fd-auth-input-wrap">
                                <i class="ri-user-line"></i>
                                <input id="user_or_email" type="text" class="login-input" placeholder="voce@empresa.com" name="user_or_email" required>
                            </div>
                        </label>

                        <label class="fd-auth-field">
                            <span>Senha</span>
                            <div class="fd-auth-input-wrap">
                                <i class="ri-lock-password-line"></i>
                                <input id="password" type="password" class="login-input" placeholder="Sua senha" name="password" required>
                            </div>
                        </label>

                        <div class="fd-auth-meta">
                            <label for="rememberMe" class="fd-auth-check">
                                <input type="checkbox" id="rememberMe" name="remember" checked>
                                <span>Lembrar este dispositivo</span>
                            </label>

                            <a href="#" class="fd-auth-link">Esqueci minha senha</a>
                        </div>

                        <button type="submit" class="fd-btn-primary fd-auth-submit">
                            <i class="ri-login-box-line"></i>
                            <span>Entrar no FlowDesk</span>
                        </button>
                    </form>

                    <form id="form-criar" method="POST" action="<?= ($base ?? '') ?>/register" class="fd-auth-form hidden" data-step="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                        <div class="fd-signup-steps">
                            <button type="button" class="fd-signup-step is-active" data-step-indicator="1">1. Perfil</button>
                            <button type="button" class="fd-signup-step" data-step-indicator="2">2. Conta</button>
                            <button type="button" class="fd-signup-step" data-step-indicator="3">3. Seguranca</button>
                        </div>

                        <section class="fd-signup-panel" data-step-panel="1">
                            <label class="fd-auth-field">
                                <span>Seu nome</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-user-smile-line"></i>
                                    <input id="register_nome" type="text" class="login-input" placeholder="Como devemos te chamar?" name="nome" required>
                                </div>
                            </label>

                            <label class="fd-auth-field">
                                <span>Nome da empresa ou studio</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-building-line"></i>
                                    <input id="register_workspace" type="text" class="login-input" placeholder="Ex: Studio Aurora" name="workspace_nome">
                                </div>
                            </label>

                            <div class="fd-signup-actions">
                                <button type="button" class="fd-btn-primary fd-auth-submit" data-next-step="2">
                                    <span>Continuar</span>
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </section>

                        <section class="fd-signup-panel hidden" data-step-panel="2">
                            <label class="fd-auth-field">
                                <span>E-mail de acesso</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-mail-line"></i>
                                    <input id="register_email" type="email" class="login-input" placeholder="voce@exemplo.com" name="email" required>
                                </div>
                            </label>

                            <label class="fd-auth-field">
                                <span>Segmento principal</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-briefcase-4-line"></i>
                                    <select id="register_segmento" class="login-input" name="segmento">
                                        <option value="">Selecione</option>
                                        <option value="freelancer">Freelancer</option>
                                        <option value="studio">Studio criativo</option>
                                        <option value="agencia">Agencia</option>
                                        <option value="consultoria">Consultoria</option>
                                    </select>
                                </div>
                            </label>

                            <div class="fd-signup-actions">
                                <button type="button" class="fd-btn-secondary" data-prev-step="1">
                                    <i class="ri-arrow-left-line"></i>
                                    <span>Voltar</span>
                                </button>

                                <button type="button" class="fd-btn-primary fd-auth-submit" data-next-step="3">
                                    <span>Continuar</span>
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </section>

                        <section class="fd-signup-panel hidden" data-step-panel="3">
                            <label class="fd-auth-field">
                                <span>Senha</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-shield-keyhole-line"></i>
                                    <input id="register_senha" type="password" class="login-input" placeholder="Minimo de 8 caracteres" name="senha" required>
                                </div>
                            </label>

                            <label class="fd-auth-field">
                                <span>Confirmar senha</span>
                                <div class="fd-auth-input-wrap">
                                    <i class="ri-shield-check-line"></i>
                                    <input id="register_conf_senha" type="password" class="login-input" placeholder="Repita sua senha" name="conf_senha" required>
                                </div>
                            </label>

                            <label class="fd-auth-check">
                                <input type="checkbox" id="register_terms" required>
                                <span>Concordo com os termos de uso e politica de privacidade.</span>
                            </label>

                            <div class="fd-signup-actions">
                                <button type="button" class="fd-btn-secondary" data-prev-step="2">
                                    <i class="ri-arrow-left-line"></i>
                                    <span>Voltar</span>
                                </button>

                                <button type="submit" class="fd-btn-primary fd-auth-submit">
                                    <i class="ri-rocket-2-line"></i>
                                    <span>Criar minha conta</span>
                                </button>
                            </div>
                        </section>
                    </form>

                    <div id="msg-login" class="fd-auth-feedback"></div>
                    <div id="msg-criar-conta" class="fd-auth-feedback"></div>
                </div>
            </div>
        </section>

        <section class="fd-landing-section" id="recursos">
            <div class="fd-landing-section-head">
                <p class="fd-page-eyebrow">Recursos</p>
                <h2>Uma operacao inteira fluindo no mesmo produto.</h2>
                <p class="fd-page-subtitle">A ideia e reduzir troca de contexto e deixar a execucao mais clara para quem vende, entrega e acompanha caixa.</p>
            </div>

            <div class="fd-landing-grid fd-landing-grid-3">
                <article class="fd-card fd-landing-feature">
                    <span class="fd-section-icon"><i class="ri-group-line"></i></span>
                    <h3>Clientes com contexto</h3>
                    <p>Centralize dados, status, receitas, historico e acessos em uma visao mais operacional.</p>
                </article>

                <article class="fd-card fd-landing-feature">
                    <span class="fd-section-icon"><i class="ri-flow-chart"></i></span>
                    <h3>Pipeline visual</h3>
                    <p>Acompanhe oportunidades por etapa, ganho, perda e transicao para projeto sem perder contexto.</p>
                </article>

                <article class="fd-card fd-landing-feature">
                    <span class="fd-section-icon"><i class="ri-wallet-3-line"></i></span>
                    <h3>Financeiro integrado</h3>
                    <p>Entradas, saidas e gastos fixos ligados ao mesmo fluxo real da operacao.</p>
                </article>
            </div>
        </section>

        <section class="fd-landing-section" id="modulos">
            <div class="fd-landing-section-head">
                <p class="fd-page-eyebrow">Modulos</p>
                <h2>Desenhado para quem vive entre comercial, entrega e financeiro.</h2>
            </div>

            <div class="fd-landing-grid fd-landing-grid-2">
                <article class="fd-card fd-landing-showcase">
                    <div>
                        <p class="fd-card-title">Workspace operacional</p>
                        <p class="fd-card-subtitle">Dashboard, clientes, pipeline, projetos, orcamentos e hospedagens conectados.</p>
                    </div>
                    <ul class="fd-landing-list">
                        <li><i class="ri-check-line"></i><span>Resumo mensal com foco em acao</span></li>
                        <li><i class="ri-check-line"></i><span>Views mais densas para uso continuo</span></li>
                        <li><i class="ri-check-line"></i><span>Camada visual pronta para evoluir como SaaS</span></li>
                    </ul>
                </article>

                <article class="fd-card fd-landing-showcase">
                    <div>
                        <p class="fd-card-title">Base pronta para escalar</p>
                        <p class="fd-card-subtitle">Roadmap orientado a multi-tenant, membros, billing e operacao de produto.</p>
                    </div>
                    <ul class="fd-landing-list">
                        <li><i class="ri-check-line"></i><span>UX mais consistente no dark e light theme</span></li>
                        <li><i class="ri-check-line"></i><span>Modais, date pickers e filtros padronizados</span></li>
                        <li><i class="ri-check-line"></i><span>Estrutura mais segura para a Fase 2</span></li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="fd-landing-section" id="onboarding">
            <div class="fd-card fd-landing-onboarding">
                <div>
                    <p class="fd-page-eyebrow">Onboarding</p>
                    <h2>Cadastro em etapas para entrar mais rapido e com menos friccao.</h2>
                    <p class="fd-page-subtitle">Voce configura seu perfil, define sua conta e conclui a seguranca sem cair em um formulario pesado logo de cara.</p>
                </div>

                <div class="fd-landing-onboarding-steps">
                    <article>
                        <strong>1</strong>
                        <span>Perfil inicial</span>
                    </article>
                    <article>
                        <strong>2</strong>
                        <span>Conta e segmento</span>
                    </article>
                    <article>
                        <strong>3</strong>
                        <span>Senha e acesso</span>
                    </article>
                </div>
            </div>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
