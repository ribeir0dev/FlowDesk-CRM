<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'FlowDesk | Estamos finalizando';
include __DIR__ . '/../layouts/partials/header-login.php';

$modules = [
    ['Clientes', 'Centralize dados, histórico e comunicação em um só lugar.', 'ri-user-3-line', 'blue', 'Quase pronto'],
    ['Pipeline', 'Acompanhe oportunidades e conversões com clareza.', 'ri-node-tree', 'purple', 'Quase pronto'],
    ['Projetos', 'Organize tarefas, prazos e entregas com eficiência.', 'ri-layout-grid-line', 'blue', 'Em ajustes'],
    ['Orçamentos', 'Crie propostas profissionais e envie em poucos cliques.', 'ri-file-list-3-line', 'orange', 'Em testes'],
    ['Financeiro', 'Tenha controle de receitas, despesas e recebimentos.', 'ri-money-dollar-circle-line', 'green', 'Em testes'],
    ['Hospedagens', 'Gerencie sites, domínios e serviços com facilidade.', 'ri-server-line', 'blue', 'Em desenvolvimento'],
    ['Códigos', 'Organize snippets, scripts e recursos reutilizáveis.', 'ri-code-box-line', 'purple', 'Em desenvolvimento'],
];

$actions = [
    ['Entrar na lista de espera', 'Seja avisado assim que o FlowDesk estiver disponível.', 'Quero entrar', 'ri-group-line', 'blue', ($base ?? '') . '/cadastro'],
    ['Acompanhar novidades', 'Fique por dentro dos avanços e lançamentos.', 'Ver novidades', 'ri-megaphone-line', 'purple', '#roadmap'],
    ['Solicitar acesso antecipado', 'Para testadores e parceiros que querem experimentar primeiro.', 'Solicitar acesso', 'ri-flashlight-line', 'blue', ($base ?? '') . '/cadastro'],
    ['Falar com a equipe', 'Tire dúvidas ou deixe sua sugestão para o produto.', 'Falar agora', 'ri-chat-3-line', 'green', 'mailto:contato@flowdesk.site'],
];
?>
<link rel="stylesheet" href="<?= ($base ?? '') ?>/assets/css/landing-coming-soon.css?v=20260701">

<div class="fd-coming">
  <div class="fd-coming-glow is-left" aria-hidden="true"></div><div class="fd-coming-glow is-right" aria-hidden="true"></div>
  <header class="fd-coming-header">
    <a href="<?= ($base ?? '') ?>/" class="fd-coming-brand"><img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt=""><strong>FlowDesk</strong></a>
    <nav class="fd-coming-nav"><a href="#produto">Produto</a><a href="#recursos">Recursos</a><a href="#para-quem">Para quem</a><a href="#precos">Preços</a><a href="#roadmap">Roadmap</a></nav>
    <div class="fd-coming-header-actions"><a href="<?= ($base ?? '') ?>/login" class="fd-coming-button is-ghost">Entrar</a><a href="<?= ($base ?? '') ?>/cadastro" class="fd-coming-button is-primary">Entrar na lista de espera</a></div>
  </header>

  <main>
    <section class="fd-coming-hero" id="produto">
      <div class="fd-coming-hero-arc" aria-hidden="true"></div>
      <div class="fd-coming-hero-content" data-reveal="up">
        <span class="fd-coming-pill"><i class="ri-code-s-slash-line"></i> Em desenvolvimento <b></b></span>
        <h1>Estamos finalizando<br>o <span>FlowDesk.</span></h1>
        <p>A plataforma está em fase final de construção. Em breve,<br>você vai organizar clientes, projetos, propostas e operação<br>em um só lugar — com mais clareza, controle e eficiência.</p>
        <div class="fd-coming-hero-actions"><a href="<?= ($base ?? '') ?>/cadastro" class="fd-coming-button is-primary is-large">Quero ser avisado no lançamento <i class="ri-arrow-right-line"></i></a><a href="<?= ($base ?? '') ?>/cadastro" class="fd-coming-button is-ghost is-large">Entrar na lista de espera</a></div>
      </div>
    </section>

    <section class="fd-coming-progress" data-reveal="up">
      <div class="fd-coming-progress-intro"><span class="fd-coming-rocket"><i class="ri-rocket-2-line"></i></span><div><h2>Produto em desenvolvimento</h2><p>Estamos construindo cada detalhe com foco em performance, segurança e experiência.</p></div></div>
      <div class="fd-coming-progress-main"><h2>Lançamento em breve</h2><div class="fd-coming-progress-row"><div class="fd-coming-progress-track"><span></span></div><strong>82%</strong></div><div class="fd-coming-progress-steps"><span class="is-done"><i class="ri-checkbox-circle-line"></i>Planejamento</span><span class="is-done"><i class="ri-checkbox-circle-line"></i>Desenvolvimento</span><span class="is-done"><i class="ri-checkbox-circle-line"></i>Testes</span><span class="is-current"><i></i>Ajustes finais</span><span><i></i>Lançamento</span></div></div>
    </section>

    <section class="fd-coming-section" id="recursos">
      <div class="fd-coming-heading" data-reveal="up"><h2>O que já está sendo <span>preparado</span></h2><p>Módulos integrados para centralizar sua operação do início ao fim.</p></div>
      <div class="fd-coming-modules"><?php foreach ($modules as [$title,$description,$icon,$tone,$status]): ?><article class="fd-coming-module" data-reveal="up"><span class="fd-coming-icon is-<?= $tone ?>"><i class="<?= $icon ?>"></i></span><h3><?= htmlspecialchars($title) ?></h3><p><?= htmlspecialchars($description) ?></p><small class="is-<?= $tone ?>"><b></b><?= htmlspecialchars($status) ?></small></article><?php endforeach; ?></div>
      <a href="#roadmap" class="fd-coming-text-link">Ver roadmap completo <i class="ri-arrow-right-line"></i></a>
    </section>

    <section class="fd-coming-section" id="para-quem">
      <div class="fd-coming-heading" data-reveal="up"><h2>Enquanto isso, <span>você pode</span></h2></div>
      <div class="fd-coming-actions-grid"><?php foreach ($actions as [$title,$description,$label,$icon,$tone,$url]): ?><article class="fd-coming-action" data-reveal="up"><span class="fd-coming-icon is-<?= $tone ?>"><i class="<?= $icon ?>"></i></span><div><h3><?= htmlspecialchars($title) ?></h3><p><?= htmlspecialchars($description) ?></p><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?> <i class="ri-arrow-right-line"></i></a></div></article><?php endforeach; ?></div>
    </section>

    <section class="fd-coming-final" id="precos" data-reveal="up"><div><h2>Seja um dos primeiros a testar o FlowDesk.</h2><p>Entre na lista de espera e garanta acesso antecipado ao lançamento.</p><a href="<?= ($base ?? '') ?>/cadastro" class="fd-coming-button is-primary is-large">Quero ser avisado no lançamento <i class="ri-arrow-right-line"></i></a><ul><li><i class="ri-checkbox-circle-line"></i>Acesso antecipado</li><li><i class="ri-checkbox-circle-line"></i>Sem compromisso</li><li><i class="ri-checkbox-circle-line"></i>Cancelamento fácil</li></ul></div><div class="fd-coming-final-mark"><img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt="Símbolo FlowDesk"></div></section>

    <footer class="fd-coming-footer" id="roadmap">
      <div class="fd-coming-footer-brand"><a href="<?= ($base ?? '') ?>/" class="fd-coming-brand"><img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt=""><strong>FlowDesk</strong></a><p>A plataforma completa para freelancers,<br>agências e equipes que querem<br>mais controle e resultados.</p><div class="fd-coming-socials"><a href="#"><i class="ri-instagram-line"></i></a><a href="#"><i class="ri-linkedin-fill"></i></a><a href="#"><i class="ri-discord-line"></i></a><a href="#"><i class="ri-youtube-fill"></i></a></div></div>
      <div><h3>Produto</h3><a href="#recursos">Recursos</a><a href="#recursos">Módulos</a><a href="#roadmap">Roadmap</a><a href="#precos">Preços</a></div><div><h3>Recursos</h3><a href="mailto:contato@flowdesk.site">Central de ajuda</a><a href="#">Tutoriais</a><a href="#">Blog</a><a href="#">Novidades</a></div><div><h3>Company</h3><a href="#">Sobre nós</a><a href="#">Carreiras</a><a href="#">Parceiros</a><a href="mailto:contato@flowdesk.site">Contato</a></div><div><h3>Legal</h3><a href="#">Política de Privacidade</a><a href="#">Termos de Uso</a></div>
      <p class="fd-coming-copyright">© 2026 FlowDesk. Todos os direitos reservados.</p>
    </footer>
  </main>
</div>
<script type="module" src="<?= ($base ?? '') ?>/assets/js/app.js"></script>
</body></html>
