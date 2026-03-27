# Fase 1 - Checklist Final de Validacao

## Objetivo

Usar esta lista para validar, no navegador, se a base atual do FlowDesk esta pronta para receber o fechamento da Fase 1.

## Login e Sessao

- [ ] Abrir `/` e confirmar que a tela de login carrega sem erro visual.
- [ ] Alternar entre `Entrar` e `Criar conta`.
- [ ] Criar uma conta de teste e confirmar mensagem de sucesso.
- [ ] Fazer login com a conta criada.
- [ ] Confirmar redirecionamento para `/dashboard`.
- [ ] Testar logout e confirmar retorno para `/`.

## Dashboard

- [ ] Abrir `/dashboard` autenticado.
- [ ] Confirmar carregamento dos cards principais sem erro de PHP.
- [ ] Testar filtros do dashboard, se existirem na tela.
- [ ] Verificar se links para modulos principais funcionam.

## Clientes

- [ ] Abrir `/clientes`.
- [ ] Criar cliente novo pelo modal.
- [ ] Editar cliente existente.
- [ ] Abrir `/cliente?id=...` de um cliente valido.
- [ ] Enviar foto do cliente.
- [ ] Editar ao menos um bloco de acesso.
- [ ] Abrir o relatorio compartilhavel do cliente.
- [ ] Confirmar mensagens de sucesso e erro nos fluxos acima.

## Pipeline

- [ ] Abrir `/pipeline`.
- [ ] Criar nova oportunidade.
- [ ] Editar oportunidade existente.
- [ ] Testar marcar como ganha.
- [ ] Testar marcar como perdida.
- [ ] Testar drag and drop entre estagios.
- [ ] Confirmar mensagem de retorno apos salvar.

## Projetos

- [ ] Abrir `/projetos`.
- [ ] Criar novo projeto.
- [ ] Editar projeto existente.
- [ ] Abrir `/projeto?id=...` de um projeto valido.
- [ ] Criar tarefa.
- [ ] Editar tarefa.
- [ ] Mover tarefa entre colunas no kanban.
- [ ] Confirmar mensagem de retorno nas telas de listagem.

## Orcamentos

- [ ] Abrir `/orcamentos`.
- [ ] Criar orcamento com pelo menos 2 itens.
- [ ] Editar orcamento existente.
- [ ] Excluir orcamento.
- [ ] Abrir detalhe/publicacao do orcamento.
- [ ] Testar compartilhamento e PDF.
- [ ] Confirmar mensagens de sucesso e erro.

## Financeiro

- [ ] Abrir `/financeiro`.
- [ ] Criar entrada.
- [ ] Editar entrada existente.
- [ ] Criar saida.
- [ ] Excluir saida.
- [ ] Criar gasto fixo.
- [ ] Marcar gasto fixo como pago.
- [ ] Confirmar grafico e filtros sem erro JS.

## Hospedagens

- [ ] Abrir `/hospedagens`.
- [ ] Criar hospedagem.
- [ ] Excluir hospedagem.
- [ ] Confirmar mensagens de sucesso e erro.

## Configuracoes

- [ ] Abrir `/configuracoes`.
- [ ] Atualizar nome e email.
- [ ] Atualizar senha.
- [ ] Enviar nova foto de perfil.
- [ ] Alternar tema.
- [ ] Confirmar mensagem de retorno.

## Erros e Navegacao

- [ ] Acessar uma rota invalida e confirmar tela `404`.
- [ ] Confirmar que `/403.php`, `/404.php` e `/500.php` renderizam.
- [ ] Verificar menu lateral no desktop.
- [ ] Verificar abertura/fechamento do menu no mobile.
- [ ] Confirmar que modais nao aparecem abertos na pagina.

## Criterio de Fechamento

Marcar a Fase 1 como concluida quando:

- todos os fluxos criticos acima abrirem e salvarem sem erro fatal;
- modais principais abrirem e preencherem corretamente;
- feedbacks de sucesso/erro aparecerem nas telas principais;
- nao houver dependencia operacional critica de `modules/...`, `inc/...` ou actions antigas no uso normal.
