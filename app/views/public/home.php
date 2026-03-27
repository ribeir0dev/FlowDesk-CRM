<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'FlowDesk | CRM operacional para freelancers e agencias';
include __DIR__ . '/../../../app/views/layouts/partials/header-login.php';
?>

<div class="fd-framer-shell">
    <header class="fd-framer-topbar">
        <a href="<?= ($base ?? '') ?>/" class="fd-framer-brand">
            <span>FlowDesk</span>
        </a>

        <nav class="fd-framer-nav">
            <a href="#produto">Produto</a>
            <a href="#explicacao">Funcionalidades</a>
            <a href="#fluxo">Fluxo</a>
            <a href="#visao">Visao</a>
            <a href="#faq">FAQ</a>
        </nav>

        <div class="fd-framer-actions">
            <a href="<?= ($base ?? '') ?>/login" class="fd-framer-btn fd-framer-btn-ghost">Entrar</a>
            <a href="<?= ($base ?? '') ?>/cadastro" class="fd-framer-btn fd-framer-btn-primary">Criar conta</a>
        </div>
    </header>

    <main class="fd-framer-main">
        <section class="fd-framer-hero" id="produto" data-reveal="up">
            <div class="fd-framer-hero-copy" data-reveal="up">
                <span class="fd-framer-pill">CRM completo para freelancers e agencias</span>
                <h1>CRM completo para freelancers e agências com projetos, financeiro e clientes em um só lugar</h1>
                <p>Gestão comercial, entrega e financeiro no mesmo workspace. Um CRM pensado para quem vende, executa projetos e precisa manter o cliente no contexto certo.</p>

                <div class="fd-framer-hero-actions">
                    <a href="<?= ($base ?? '') ?>/cadastro" class="fd-framer-btn fd-framer-btn-primary">Começar agora</a>
                    <a href="<?= ($base ?? '') ?>/login" class="fd-framer-btn fd-framer-btn-ghost">Entrar no meu workspace</a>
                </div>

                <div class="fd-framer-proof-row">
                    <span>Cadastro com conta e workspace</span>
                    <span>CRM, projetos e financeiro no mesmo painel</span>
                    <span>Portal do cliente e permissões por papel</span>
                </div>
            </div>

            <div class="fd-framer-mock" data-reveal="scale">
                <div class="fd-framer-mock-head">
                    <div class="fd-framer-mock-brand">
                        <img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt="FlowDesk">
                        <span>FlowDesk Workspace</span>
                    </div>
                    <div class="fd-framer-mock-badges">
                        <span>Operacional</span>
                        <span>Fluxo real</span>
                    </div>
                </div>

                <div class="fd-framer-mock-body">
                    <aside class="fd-framer-mock-sidebar">
                        <span class="is-active"><i class="ri-group-line"></i> Clientes</span>
                        <span><i class="ri-git-branch-line"></i> Pipeline</span>
                        <span><i class="ri-kanban-view-2-line"></i> Projetos</span>
                        <span><i class="ri-wallet-3-line"></i> Financeiro</span>
                    </aside>

                    <div class="fd-framer-mock-grid">
                        <article>
                            <small>Clientes</small>
                            <strong>Cadastro + timeline</strong>
                            <p>Ficha com dados, histórico, projetos e blocos de acesso.</p>
                        </article>
                        <article>
                            <small>Pipeline</small>
                            <strong>Lead até fechamento</strong>
                            <p>Oportunidades por etapa, ganho, perda e continuidade.</p>
                        </article>
                        <article>
                            <small>Projetos</small>
                            <strong>Kanban de entrega</strong>
                            <p>Tarefas, datas e acompanhamento visual da execução.</p>
                        </article>
                        <article class="fd-framer-mock-wide">
                            <small>Financeiro e orçamentos</small>
                            <strong>Mesmo contexto operacional</strong>
                            <p>Proposta, recebimento, saída, gasto fixo e análise mensal sem trocar de sistema.</p>
                        </article>
                        <article>
                            <small>Permissões</small>
                            <strong>Time e cliente</strong>
                            <p>Owner, admin, operacional, financeiro e viewer com acessos separados.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="fd-framer-explain" id="explicacao" data-reveal="up">
            <div class="fd-framer-section-head">
                <span class="fd-framer-pill">Visão geral</span>
                <h2>O que é um CRM para freelancers e agências?</h2>
            </div>

            <div class="fd-framer-explain-grid">
                <article class="fd-framer-card fd-framer-card-large">
                    <p>Um CRM para freelancers e agências é um sistema que organiza comercial, clientes, projetos, propostas e financeiro no mesmo fluxo.</p>
                </article>

                <article class="fd-framer-card">
                    <p>Para freelancers e equipes pequenas, o principal valor está em sair de planilhas e ferramentas soltas para uma operação visual e integrada.</p>
                </article>

                <article class="fd-framer-card">
                    <p>O FlowDesk foi feito exatamente para esse cenário: vender, organizar projetos, cobrar e acompanhar clientes sem quebrar o contexto.</p>
                </article>

                <article class="fd-framer-card fd-framer-card-wide">
                    <h3>Por que não usar planilhas ou várias ferramentas?</h3>
                    <ul>
                        <li>Falta contexto entre cliente, projeto e financeiro</li>
                        <li>Perde-se informação na operação</li>
                        <li>Retrabalho e desalinhamento</li>
                        <li>Baixa previsibilidade comercial</li>
                        <li>Dificuldade para crescer com clareza</li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="fd-framer-features" data-reveal="up">
            <div class="fd-framer-section-head">
                <span class="fd-framer-pill">Tudo em um só lugar</span>
                <h2>Funcionalidades de um CRM completo para freelancers e agências</h2>
            </div>

            <div class="fd-framer-feature-grid">
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-group-line"></i>
                    <h3>Gestão de clientes com contexto</h3>
                    <p>Ficha do cliente com dados centrais, blocos de acesso e histórico operacional.</p>
                </article>
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-git-branch-line"></i>
                    <h3>Pipeline comercial visual</h3>
                    <p>Leads e oportunidades organizados por estágio com leitura clara do funil.</p>
                </article>
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-kanban-view-2-line"></i>
                    <h3>Projetos com kanban de entrega</h3>
                    <p>Tarefas, datas e acompanhamento prático da execução em andamento.</p>
                </article>
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-file-list-3-line"></i>
                    <h3>Propostas e orçamentos</h3>
                    <p>Orçamentos com itens, total, PDF e continuidade entre venda e cobrança.</p>
                </article>
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-wallet-3-line"></i>
                    <h3>Controle financeiro no fluxo</h3>
                    <p>Entradas, saídas, gastos fixos e análise por período no mesmo sistema.</p>
                </article>
                <article class="fd-framer-feature-card" data-reveal="up">
                    <i class="ri-shield-user-line"></i>
                    <h3>Permissões e acompanhamento de cliente</h3>
                    <p>Perfis separados para time e portal do cliente com acesso controlado.</p>
                </article>
            </div>
        </section>

        <section class="fd-framer-flow" id="fluxo" data-reveal="up">
            <div class="fd-framer-section-head">
                <span class="fd-framer-pill">Fluxo conectado</span>
                <h2>Um CRM que conecta vendas, projetos e financeiro</h2>
                <p>Cliente, proposta, projeto e financeiro organizados na operação de um único lugar.</p>
            </div>

            <div class="fd-framer-flow-steps">
                <span><i class="ri-user-3-line lead"></i> Lead</span>
                <i class="ri-arrow-right-long-line"></i>
                <span><i class="ri-file-chart-line proposta"></i> Proposta</span>
                <i class="ri-arrow-right-long-line"></i>
                <span><i class="ri-kanban-view-2-line projeto"></i> Projeto</span>
                <i class="ri-arrow-right-long-line"></i>
                <span><i class="ri-wallet-3-line caixa"></i> Caixa</span>
                <i class="ri-arrow-right-long-line"></i>
                <span><i class="ri-customer-service-2-line entrega"></i> Entrega</span>
            </div>

            <div class="fd-framer-flow-copy">
                <p>1. O lead entra no funil comercial e pode evoluir até proposta.</p>
                <p>2. A proposta aprovada vira projeto, sem recomeçar o processo.</p>
                <p>3. O financeiro acompanha recebimentos, saídas e análise no mesmo painel.</p>
                <p>4. O cliente acompanha o que faz sentido, sem expor toda a operação interna.</p>
            </div>

            <div class="fd-framer-flow-action">
                <a href="<?= ($base ?? '') ?>/cadastro" class="fd-framer-btn fd-framer-btn-primary">Criar workspace grátis</a>
            </div>
        </section>

        <section class="fd-framer-visuals" id="visao" data-reveal="up">
            <div class="fd-framer-section-head">
                <span class="fd-framer-pill">Na prática</span>
                <h2>Um sistema pensado para operação real, não só para cadastro</h2>
            </div>

            <div class="fd-framer-visual-grid">
                <article class="fd-framer-visual-card">
                    <div class="fd-framer-visual-thumb"></div>
                    <div class="fd-framer-visual-copy">
                        <h3>Dashboard com visão mensal e indicadores</h3>
                        <p>Leitura da operação com contexto visual, tarefas do dia e indicadores úteis para decidir melhor.</p>
                    </div>
                </article>
                <article class="fd-framer-visual-card">
                    <div class="fd-framer-visual-thumb"></div>
                    <div class="fd-framer-visual-copy">
                        <h3>Pipeline e projetos com navegação curta</h3>
                        <p>Fluxos mais curtos para encontrar cliente, etapa comercial, projeto e situação financeira.</p>
                    </div>
                </article>
            </div>

            <div class="fd-framer-dots">
                <span class="is-active"></span>
                <span></span>
            </div>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
