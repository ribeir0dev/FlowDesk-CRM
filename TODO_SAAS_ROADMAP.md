# FlowDesk CRM - Roadmap SaaS

## Como usar esta lista

- Marque `[x]` quando a etapa estiver concluida.
- Use este arquivo como guia de produto, arquitetura e operacao.
- A ordem abaixo prioriza reduzir risco antes de acelerar funcionalidades.

---

## Fase 1 - Estabilizacao da Base Atual

Status: concluida

### Objetivo

Transformar o painel atual em uma base confiavel, consistente e pronta para suportar evolucao SaaS.

### Backlog

- [x] Revisar todos os fluxos principais manualmente: login, clientes, pipeline, projetos, orcamentos, financeiro, hospedagens e configuracoes.
- [ ] Corrigir textos restantes com encoding quebrado em views, partials, controllers e mensagens de retorno.
- [x] Padronizar todos os endpoints internos para evitar mistura entre rotas publicas e acesso direto a controllers.
- [x] Revisar `public/assets/js/script.js` e separar blocos grandes em arquivos menores por modulo.
- [ ] Eliminar dependencias remanescentes da estrutura antiga `modules/...`, `inc/...` e assets legados.
- [ ] Revisar includes, redirects e actions antigas que ainda possam quebrar em cenarios secundarios.
- [x] Validar todos os modais de criar, editar e excluir em cada modulo.
- [x] Padronizar mensagens de sucesso e erro em toda a aplicacao.
- [x] Revisar upload de avatar, fotos e arquivos para garantir caminhos consistentes.
- [x] Criar uma pagina simples de erro padrao para `403`, `404` e `500`.

### Progresso executado

- [x] Migrar a superficie principal do painel para o padrao visual `fd-*`.
- [x] Unificar o shell principal do painel, sidebar, topbar, tema e base visual global.
- [x] Restaurar funcionamento dos modais com Bootstrap e alinhar o visual deles ao painel.
- [x] Corrigir includes quebrados e mapeamentos de views na estrutura nova.
- [x] Corrigir o erro de bootstrap em `app/Helpers/token.php` que quebrava o carregamento do painel.
- [x] Corrigir o fluxo de edicao de projetos.
- [x] Corrigir o fluxo de edicao de oportunidades do pipeline.
- [x] Corrigir o fluxo de edicao de orcamentos, incluindo backend de atualizacao.
- [x] Criar rotas publicas para orcamentos: buscar, criar, atualizar e excluir.
- [x] Criar rotas publicas para projetos: buscar, criar, concluir, atualizar e tarefas.
- [x] Criar rotas publicas para pipeline: buscar, criar, atualizar, excluir, mover e marcacoes.
- [x] Criar rotas publicas para financeiro e hospedagens nas acoes principais.
- [x] Ajustar formularios e actions principais das views para usar rotas publicas do app.
- [x] Corrigir redirects herdados de `/index.php` para a raiz `/`.
- [x] Padronizar uso do `base` da aplicacao no layout principal e no JS central.
- [x] Corrigir filtros do dashboard para usar a rota `/dashboard` em vez de `?mod=dashboard`.
- [x] Reduzir dependencia direta de caminhos legados `modules/...` no fluxo principal.
- [x] Criar rota publica para a busca global do painel.
- [x] Limpar o encoding mais visivel do layout principal e da tela de hospedagens.
- [x] Restaurar o fluxo publico de criacao de conta na tela de login.
- [x] Corrigir o fluxo de edicao de entradas no modulo financeiro.
- [x] Limpar o encoding mais visivel dos modais de clientes e financeiro.
- [x] Limpar o encoding mais visivel da tela de financeiro.
- [x] Adicionar feedback visual basico para acoes de hospedagens.
- [x] Limpar residuos legados em SearchController e HospedagemModel.
- [x] Separar o modulo de orcamentos do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar o modal global de confirmacao do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar o bloco de kanban de pipeline e projetos do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar o bloco de graficos do financeiro do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar os modais de edicao de tarefa, projeto e oportunidade do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar o fluxo de edicao de entradas do financeiro do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar a busca global do painel do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar as acoes rapidas do pipeline do `public/assets/js/script.js` em arquivo proprio.
- [x] Separar os helpers visuais globais do `public/assets/js/script.js` em arquivo proprio.
- [x] Mover o salvamento de blocos de clientes da action legada para `ClienteController`.
- [x] Corrigir seletores legados de sidebar para o layout `fd-sidebar`.
- [x] Transformar arquivos publicos e actions legadas em shims compatveis com a estrutura nova.
- [x] Limpar comentarios herdados mais expostos em helpers e models principais.
- [x] Corrigir actions e encoding mais visivel dos modais principais de pipeline, projetos e orcamentos.
- [x] Criar e integrar paginas padrao de erro para `403`, `404` e `500`.
- [x] Padronizar feedbacks basicos de sucesso e erro nas telas principais de configuracoes, clientes, projetos, pipeline e orcamentos.
- [x] Ajustar o retorno do upload de foto do cliente com mensagens explicitas de sucesso e erro.
- [x] Limpar o encoding residual mais visivel das telas principais de configuracoes, clientes, projetos, pipeline e orcamentos.
- [x] Remover campos `mod` residuais de filtros ja migrados para rotas dedicadas.
- [x] Limpar textos residuais de encoding na tela principal do dashboard.
- [x] Preparar checklist final de validacao manual para decidir o fechamento da Fase 1.
- [x] Corrigir controllers que ainda liam apenas `GET/POST` e ignoravam a `acao` injetada pelas rotas novas.
- [x] Corrigir o aviso de `session_start()` duplicado na tela de login.
- [x] Corrigir as rotas de autenticacao para injetar `acao` em `$_REQUEST` e restaurar o logout.
- [x] Corrigir caminhos publicos de upload e renderizacao de fotos de clientes e avatares.
- [x] Reescrever o filtro de mes do dashboard com input nativo funcional e sem dependencia de picker quebrado.
- [x] Aplicar o mesmo ajuste preventivo de filtro mensal na tela de financeiro.
- [x] Sincronizar o estado vazio visual do kanban de pipeline e projetos sem depender de refresh.
- [x] Padronizar feedbacks de criacao, edicao e conclusao em projetos.
- [x] Padronizar feedbacks de criacao, edicao e exclusao em orcamentos, incluindo o ID quando relevante.
- [x] Corrigir a criacao de entradas financeiras com cliente opcional para evitar erro 500 em pagamentos parciais.
- [x] Persistir a aba atual do financeiro apos criar, excluir e pagar registros.
- [x] Reforcar a sincronizacao do estado vazio do kanban no carregamento e apos cada drop.
- [x] Substituir os filtros mensais nativos por um month picker custom visual no dashboard e no financeiro.
- [x] Aplicar um date picker custom visual nos principais modais com selecao de data.

### Entregaveis

- [x] Painel estavel para uso diario.
- [x] Fluxos sem dependencia critica de codigo legado.
- [x] Interface principal consolidada em um padrao unico.

---

## Fase 2 - Fundacao Multi-tenant

Status: concluida

### Objetivo

Sair do modelo de painel unico e preparar a estrutura para multiplas contas, empresas ou workspaces.

### Preparacao previa

- [x] Definir a necessidade de uma pre-migracao estrutural do schema antes da fundacao multi-tenant.
- [x] Preparar checklist e SQL base da pre-fase de padronizacao estrutural.

### Modelagem

- [x] Criar entidade central `accounts` ou `workspaces`.
- [x] Definir que o tenant sera modelado por `workspace_id` em toda a camada operacional.
- [x] Criar tabela de relacionamento entre usuarios e contas.
- [x] Adicionar coluna de tenant em tabelas principais:
- [x] `clientes`
- [x] `oportunidades`
- [x] `projetos`
- [x] `projeto_tarefas`
- [x] `orcamentos`
- [x] `orcamento_itens`
- [x] `financeiro`
- [x] `gastos_fixos`
- [x] `hospedagens`
- [ ] `uploads`
- [ ] `configuracoes`
- [x] Migrar dados existentes para uma conta padrao inicial.

### Backend

- [x] Criar resolver de tenant na autenticacao e sessao.
- [x] Garantir que toda consulta filtre por tenant.
- [x] Garantir que toda criacao salve o tenant corretamente.
- [x] Bloquear acesso cruzado entre tenants por ID na URL.
- [x] Revisar controllers e models para impedir vazamento de dados entre contas.
- [x] Criar helper central para obter tenant atual com seguranca.

### Entregaveis

- [x] Cada conta enxerga apenas seus proprios dados.
- [x] Estrutura de banco pronta para uso multiempresa.
- [x] Base segura para adicionar membros e planos.

---

## Fase 3 - Autenticacao, Membros e Permissoes

Status: concluida

### Objetivo

Transformar login simples em sistema de acesso proprio de um SaaS.

### Autenticacao

- [x] Reestruturar cadastro para criar conta + usuario dono.
- [x] Implementar fluxo de recuperacao de senha.
- [x] Implementar confirmacao de email.
- [x] Criar onboarding inicial apos cadastro.
- [x] Criar fluxo de logout e sessao com expiracao previsivel.

### Membros

- [x] Permitir convidar usuarios para uma conta.
- [x] Permitir aceitar convite por email.
- [x] Criar tela de membros da equipe.
- [x] Permitir remover membro da conta.
- [x] Permitir trocar papel/permissao do membro.

### Permissoes

- [x] Definir papeis base:
- [x] `owner`
- [x] `admin`
- [x] `operacional`
- [x] `financeiro`
- [x] `viewer`
- [x] Definir matriz oficial de permissoes por modulo.
- [x] Restringir acoes criticas por papel.
- [x] Proteger visualizacao de modulos sensiveis, como financeiro e configuracoes.
- [x] Registrar quem fez alteracoes importantes.

### Entregaveis

- [x] Conta com dono e membros.
- [x] Controle de acesso por papel.
- [x] Autenticacao mais robusta e pronta para producao.

---

## Fase 4 - Estrutura de Produto SaaS

### Objetivo

Sair da logica de painel administrativo e consolidar a experiencia de produto.

### Produto

- [x] Criar uma landing page publica para posicionar o produto como SaaS.
- [x] Reformular a pagina de login para uma experiencia publica mais coerente com o produto.
- [x] Criar uma pagina de cadastro com formulario em etapas no frontend, mantendo compatibilidade com o backend atual.
- [x] Corrigir a navegacao e validacao das etapas do cadastro publico.
- [x] Separar a landing, o login e o cadastro em paginas proprias.
- [x] Refinar a landing para conversar com a identidade visual do FlowDesk em vez de depender de paleta externa.
- [x] Adicionar destaque de funcionalidades do painel e uma secao inicial de pricing na landing.
- [ ] Criar onboarding em etapas para nova conta.
- [ ] Criar dados iniciais opcionais para primeira experiencia.
- [ ] Criar pagina de dashboard inicial por tenant com estado vazio bem resolvido.
- [ ] Criar central de configuracoes da conta.
- [ ] Criar pagina de perfil do usuario separada da configuracao da conta.
- [ ] Criar preferencia de idioma, tema e fuso horario por usuario.

### UX

- [ ] Revisar formularios longos para reduzir friccao.
- [ ] Melhorar feedback visual de salvamento.
- [ ] Padronizar loading, empty state e mensagens de erro.
- [ ] Melhorar responsividade dos modulos mais densos.
- [ ] Criar navegacao mais clara para uso continuo em ambiente SaaS.
- [x] Transformar o modulo de clientes em uma visao operacional master-detail com lista e preview do cliente selecionado.

### Entregaveis

- [ ] Experiencia mais profissional de produto.
- [ ] Menor curva de entrada para novos clientes.
- [ ] Interface pronta para escalar com novos modulos.

---

## Fase 5 - Billing, Planos e Assinaturas

### Objetivo

Monetizar o sistema com planos recorrentes e regras de acesso.

### Planejamento comercial

- [ ] Definir planos iniciais.
- [ ] Definir limites por plano:
- [ ] usuarios
- [ ] clientes
- [ ] projetos
- [ ] orcamentos
- [ ] armazenamento
- [ ] recursos premium
- [ ] Definir trial, cancelamento e grace period.

### Tecnico

- [ ] Criar tabelas de assinaturas, planos, invoices e eventos de cobranca.
- [ ] Integrar gateway de pagamento.
- [ ] Registrar status da assinatura por conta.
- [ ] Bloquear ou limitar recursos conforme plano.
- [ ] Criar area de faturamento da conta.
- [ ] Criar historico de cobrancas.
- [ ] Criar webhooks de pagamento com reprocessamento seguro.

### Entregaveis

- [ ] Sistema vendavel como SaaS.
- [ ] Plano e assinatura vinculados a cada conta.
- [ ] Base comercial pronta para trial e recorrencia.

---

## Fase 6 - Infraestrutura e Ambiente de Producao

### Objetivo

Preparar o projeto para rodar como plataforma e nao apenas ambiente local.

### Ambiente

- [ ] Separar configuracoes por ambiente: local, staging e producao.
- [ ] Mover segredos para variaveis de ambiente.
- [ ] Revisar bootstrap e autoload para ambiente produtivo.
- [ ] Padronizar logs de aplicacao e erros.
- [ ] Configurar storage de arquivos fora da pasta publica local, quando necessario.

### Banco e deploy

- [ ] Criar migracoes versionadas de banco.
- [ ] Criar processo confiavel de deploy.
- [ ] Criar rotina de backup de banco.
- [ ] Criar politica de rollback.
- [ ] Revisar indices e performance das tabelas principais.

### Entregaveis

- [ ] Ambiente minimamente seguro para clientes reais.
- [ ] Deploy repetivel.
- [ ] Menor risco operacional.

---

## Fase 7 - Observabilidade, Seguranca e Confiabilidade

### Objetivo

Garantir operacao previsivel, auditavel e segura.

### Seguranca

- [ ] Revisar validacao e sanitizacao de entrada em todos os controllers.
- [ ] Garantir protecao CSRF em formularios criticos.
- [ ] Revisar controle de upload de arquivos.
- [ ] Revisar tokens publicos e links compartilhados.
- [ ] Revisar politicas de senha e sessao.
- [ ] Mapear riscos de acesso indevido por URL direta.

### Observabilidade

- [ ] Criar logging estruturado para erros.
- [ ] Criar log de auditoria para acoes sensiveis.
- [ ] Monitorar falhas de login, erros 500 e falhas de cobranca.
- [ ] Criar pagina ou painel interno de saude da aplicacao.

### Entregaveis

- [ ] Aplicacao mais segura para operacao real.
- [ ] Rastro minimo de auditoria.
- [ ] Capacidade de diagnosticar falhas com rapidez.

---

## Fase 8 - Testes e Qualidade

### Objetivo

Reduzir regressao e permitir evolucao continua com mais seguranca.

### Backlog

- [ ] Definir stack de testes para o projeto.
- [ ] Criar testes de autenticacao.
- [ ] Criar testes de isolamento multi-tenant.
- [ ] Criar testes de CRUD para clientes, pipeline, projetos e orcamentos.
- [ ] Criar testes de permissao por papel.
- [ ] Criar testes de faturamento e alteracao de plano.
- [ ] Criar smoke tests para rotas principais.
- [ ] Adicionar validacao automatica antes de deploy.

### Entregaveis

- [ ] Base menos dependente de teste manual.
- [ ] Menor risco ao mexer em rotas e regras centrais.
- [ ] Melhor previsibilidade de entrega.

---

## Fase 9 - Crescimento e Recursos Premium

### Objetivo

Expandir o produto depois da base SaaS estar consistente.

### Ideias

- [ ] Automacoes por modulo.
- [ ] Agenda e lembretes.
- [ ] Emails transacionais e notificacoes.
- [ ] Relatorios gerenciais avancados.
- [ ] Dashboard executivo por conta.
- [ ] Templates de propostas e projetos.
- [ ] Integracao com WhatsApp, email e gateways externos.
- [ ] API publica ou privada.
- [ ] White-label ou dominio personalizado.

### Entregaveis

- [ ] Camada de diferenciacao de produto.
- [ ] Recursos para planos premium.
- [ ] Espaco para crescimento comercial.

---

## Ordem Recomendada de Execucao

- [x] Primeiro: concluir Fase 1.
- [x] Segundo: executar Fase 2 antes de qualquer billing.
- [ ] Terceiro: concluir Fase 3 para suportar times e acessos.
- [ ] Quarto: estruturar Fase 4 e Fase 5 em paralelo leve.
- [ ] Quinto: consolidar Fase 6, 7 e 8 antes de vender em escala.
- [ ] Sexto: usar Fase 9 como crescimento, nao como prioridade inicial.

---

## Marco de Pronto Para Vender

Considere o produto minimamente pronto para venda quando estes itens estiverem concluidos:

- [ ] Multi-tenant funcional e seguro.
- [ ] Cadastro de conta e onboarding funcionando.
- [ ] Membros e papeis funcionando.
- [ ] Billing basico implementado.
- [ ] Deploy de producao validado.
- [ ] Logs e monitoramento minimos ativos.
- [ ] Fluxos principais cobertos por teste ou QA forte.

