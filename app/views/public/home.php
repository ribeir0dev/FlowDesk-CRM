<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'FlowDesk | CRM operacional para freelancers e agencias';
include __DIR__ . '/../../../app/views/layouts/partials/header-login.php';
?>

<div class="fd-site-landing">
    <header class="fd-site-landing-topbar" data-reveal="up">
        <div class="fd-site-landing-topbar-inner">
            <a href="<?= ($base ?? '') ?>/" class="fd-site-landing-brand">FlowDesk</a>

            <nav class="fd-site-landing-nav" aria-label="Navegacao principal">
                <a href="#produto">Produto</a>
                <a href="#funcionalidades">Funcionalidades</a>
                <a href="#para-quem">Para quem e</a>
                <a href="#planos">Planos</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="fd-site-landing-topbar-actions">
                <a href="<?= ($base ?? '') ?>/login" class="fd-site-landing-btn fd-site-landing-btn-ghost">Entrar</a>
                <a href="<?= ($base ?? '') ?>/cadastro" class="fd-site-landing-btn fd-site-landing-btn-primary">Criar Conta</a>
            </div>
        </div>
    </header>

    <main class="fd-site-landing-main">
        <section class="fd-site-landing-hero" id="produto">
            <div class="fd-site-landing-hero-orb fd-site-landing-hero-orb-left" aria-hidden="true"></div>
            <div class="fd-site-landing-hero-orb fd-site-landing-hero-orb-right" aria-hidden="true"></div>

            <div class="fd-site-landing-hero-copy" data-reveal="up">
                <span class="fd-site-landing-kicker">CRM para freelancers, agencias e equipes pequenas</span>
                <h1>CRM completo para freelancers e agencias com <span>projetos, financeiro</span> e <span>clientes</span> em um so lugar</h1>
                <p class="fd-site-landing-lead">
                    Organize leads, feche projetos, gerencie entregas e controle seu financeiro dentro de um unico sistema sem depender de planilhas ou ferramentas soltas.
                </p>

                <div class="fd-site-landing-cta-row">
                    <a href="<?= ($base ?? '') ?>/cadastro" class="fd-site-landing-btn fd-site-landing-btn-primary fd-site-landing-btn-flow">
                        <span>Comece Gratis</span>
                        <small class="fd-site-landing-price-tag">Planos para alavancar por apenas <b>R$39,90</b></small>
                    </a>
                    <a href="<?= ($base ?? '') ?>/login" class="fd-site-landing-btn fd-site-landing-btn-secondary fd-site-landing-btn-shine">
                        <span>Entrar no meu workspace</span>
                        <span class="fd-site-landing-btn-shine-ray" aria-hidden="true"></span>
                    </a>
                </div>

                <ul class="fd-site-landing-proof">
                    <li><i class="ri-speed-up-line"></i><span>Setup em menos de 2 minutos</span></li>
                    <li><i class="ri-group-line"></i><span>Gestao de clientes com historico completo e contexto operacional</span></li>
                    <li><i class="ri-layout-grid-line"></i><span>Projetos, tarefas e financeiro integrados no mesmo CRM</span></li>
                </ul>
            </div>
        </section>

        <section class="fd-site-landing-preview" data-reveal="scale">
            <div class="fd-site-landing-preview-frame">
                <img src="<?= ($base ?? '') ?>/assets/img/Preview-Dados-1024x508.png" alt="Preview do painel FlowDesk" class="fd-site-landing-preview-image">
            </div>
        </section>

        <section class="fd-site-landing-section" id="para-quem">
            <div class="fd-site-landing-section-head" data-reveal="up">
                <p class="fd-site-landing-section-kicker">O que e?</p>
                <h2>O que e um CRM para freelancers e agencias?</h2>
            </div>

            <div class="fd-site-landing-about-grid">
                <article class="fd-site-landing-panel fd-site-landing-panel-tall" data-reveal="up">
                    <p>Um CRM (Customer Relationship Management) e um sistema para organizar clientes, leads, negociacoes e relacionamento comercial.</p>
                </article>

                <div class="fd-site-landing-about-stack">
                    <article class="fd-site-landing-panel" data-reveal="up">
                        <p>Para freelancers e agencias, um CRM vai alem do comercial: ele precisa conectar vendas, projetos e financeiro no mesmo fluxo de trabalho.</p>
                    </article>

                    <article class="fd-site-landing-panel" data-reveal="up">
                        <p>O FlowDesk foi criado exatamente para isso: centralizar toda a operacao, do primeiro contato ate a entrega e cobranca, em um unico workspace.</p>
                    </article>
                </div>
            </div>

            <article class="fd-site-landing-panel fd-site-landing-panel-list" data-reveal="up">
                <div class="fd-site-landing-list-head">
                    <h3>Por que nao usar planilhas ou varias ferramentas?</h3>
                </div>

                <ul class="fd-site-landing-bullet-list">
                    <li><i class="ri-close-line"></i><span>Falta de contexto entre cliente, projeto e financeiro</span></li>
                    <li><i class="ri-close-line"></i><span>Perda de informacoes importantes</span></li>
                    <li><i class="ri-close-line"></i><span>Retrabalho e desorganizacao</span></li>
                    <li><i class="ri-close-line"></i><span>Baixa previsibilidade de receita</span></li>
                    <li class="is-positive"><i class="ri-check-line"></i><span>Com o FlowDesk, tudo fica conectado.</span></li>
                </ul>
            </article>
        </section>

        <section class="fd-site-landing-section" id="funcionalidades">
            <div class="fd-site-landing-section-head" data-reveal="up">
                <p class="fd-site-landing-section-kicker">Tudo que voce precisa</p>
                <h2>Funcionalidades de um CRM completo para freelancers e agencias</h2>
            </div>

            <div class="fd-site-landing-feature-grid">
                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-user-star-line"></i></span>
                    <h3>Gestao de clientes com historico completo</h3>
                    <p>Ficha do cliente com dados, projetos, receitas, arquivos e timeline de interacao.</p>
                </article>

                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-git-branch-line"></i></span>
                    <h3>Pipeline comercial visual</h3>
                    <p>Leads e oportunidades organizados por etapa, com leitura clara do funil e do valor esperado.</p>
                </article>

                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-kanban-view-2-line"></i></span>
                    <h3>Projetos e entregas conectados</h3>
                    <p>Kanban por projeto, tarefas, checklist e acompanhamento operacional no mesmo workspace.</p>
                </article>

                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-file-list-3-line"></i></span>
                    <h3>Propostas e orcamentos</h3>
                    <p>Gere propostas, acompanhe aprovacoes e transforme a venda em projeto sem retrabalho.</p>
                </article>

                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-wallet-3-line"></i></span>
                    <h3>Financeiro operacional</h3>
                    <p>Entradas, saidas, gastos fixos e leitura mensal para manter a previsibilidade da operacao.</p>
                </article>

                <article class="fd-site-landing-feature-card" data-reveal="up">
                    <span class="fd-site-landing-feature-icon"><i class="ri-shield-user-line"></i></span>
                    <h3>Permissoes por papel</h3>
                    <p>Owner, admin, operacional, financeiro e viewer com acessos separados conforme o contexto.</p>
                </article>
            </div>
        </section>

        <section class="fd-site-landing-section fd-site-landing-cta-band" id="planos" data-reveal="up">
            <div class="fd-site-landing-section-head">
                <p class="fd-site-landing-section-kicker">Pronto para comecar?</p>
                <h2>Centralize comercial, projetos e financeiro em um unico workspace</h2>
                <p>Comece agora e organize a operacao da sua agencia ou rotina freelancer com mais clareza.</p>
            </div>

            <div class="fd-site-landing-cta-band-actions">
                <a href="<?= ($base ?? '') ?>/cadastro" class="fd-site-landing-btn fd-site-landing-btn-primary fd-site-landing-btn-flow">Criar minha conta</a>
                <a href="<?= ($base ?? '') ?>/login" class="fd-site-landing-btn fd-site-landing-btn-ghost fd-site-landing-btn-shine">
                    <span>Ja tenho acesso</span>
                    <span class="fd-site-landing-btn-shine-ray" aria-hidden="true"></span>
                </a>
            </div>
        </section>

        <section class="fd-site-landing-section fd-site-landing-faq" id="faq" data-reveal="up">
            <div class="fd-site-landing-section-head">
                <p class="fd-site-landing-section-kicker">FAQ</p>
                <h2>Perguntas comuns</h2>
            </div>

            <div class="fd-site-landing-faq-grid">
                <article class="fd-site-landing-panel">
                    <h3>Serve para freelancer solo?</h3>
                    <p>Sim. O FlowDesk foi pensado tanto para operacao solo quanto para pequenas equipes e agencias.</p>
                </article>
                <article class="fd-site-landing-panel">
                    <h3>Consigo controlar projetos e financeiro juntos?</h3>
                    <p>Esse e exatamente o ponto do produto: manter contexto comercial, entrega e cobranca dentro do mesmo fluxo.</p>
                </article>
                <article class="fd-site-landing-panel">
                    <h3>Tem area do cliente?</h3>
                    <p>Sim. O produto ja considera portal do cliente e permissoes por papel dentro da estrutura do workspace.</p>
                </article>
            </div>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
