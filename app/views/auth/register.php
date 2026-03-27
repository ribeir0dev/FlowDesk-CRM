<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';

$pageTitle = 'Criar conta | FlowDesk';
include __DIR__ . '/../../../app/views/layouts/partials/header-login.php';
?>

<div class="fd-auth-page fd-auth-page-register">
    <div class="fd-auth-page-shell">
        <section class="fd-auth-page-brand">
            <a href="<?= ($base ?? '') ?>/" class="fd-public-brand">
                <img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt="FlowDesk" class="fd-public-brand-logo">
                <span class="fd-public-brand-copy">
                    <strong>FlowDesk</strong>
                    <small>CRM | SaaS Workspace</small>
                </span>
            </a>

            <div class="fd-auth-page-copy">
                <p class="fd-page-eyebrow">Get Started</p>
                <h1>Crie sua conta grátis e comece a desfrutar do seu Workspace.</h1>
            </div>

            <div class="fd-auth-register-kpis">
                <article>
                    <strong>01</strong>
                    <span>Perfil inicial e nome da sua agência</span>
                </article>
                <article>
                    <strong>02</strong>
                    <span>Email de acesso e segmento principal</span>
                </article>
                <article>
                    <strong>03</strong>
                    <span>Senha e seguranca</span>
                </article>
            </div>
        </section>

        <section class="fd-auth-page-card-wrap">
            <div class="fd-auth-card fd-auth-card-standalone">
                <div class="fd-auth-card-head fd-auth-card-head-standalone">
                    <div>
                        <p class="fd-page-eyebrow">Cadastro</p>
                        <h2>Criar minha conta</h2>
                        <p class="fd-card-subtitle">Configure seu acesso ao FlowDesk sem depender do painel inicial.</p>
                    </div>
                    <a href="<?= ($base ?? '') ?>/login" class="fd-auth-inline-link">Ja tenho conta</a>
                </div>

                <form id="form-criar" method="POST" action="<?= ($base ?? '') ?>/register" class="fd-auth-form fd-auth-form-standalone" data-step="1">
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
                            <div class="fd-password-strength" aria-live="polite">
                                <div class="fd-password-strength-bar">
                                    <span id="password-strength-fill" class="fd-password-strength-fill strength-0"></span>
                                </div>
                                <strong id="password-strength-label" class="fd-password-strength-label">Senha fraca</strong>
                            </div>
                            <ul class="fd-password-checklist">
                                <li id="password-rule-length" class="is-pending">
                                    <i class="ri-checkbox-circle-line"></i>
                                    <span>Pelo menos 8 caracteres</span>
                                </li>
                                <li id="password-rule-number" class="is-pending">
                                    <i class="ri-checkbox-circle-line"></i>
                                    <span>Pelo menos 1 numero</span>
                                </li>
                                <li id="password-rule-special" class="is-pending">
                                    <i class="ri-checkbox-circle-line"></i>
                                    <span>Pelo menos 1 caractere especial</span>
                                </li>
                            </ul>
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

                <div id="msg-criar-conta" class="fd-auth-feedback"></div>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
