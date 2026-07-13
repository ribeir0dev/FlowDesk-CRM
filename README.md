# FlowDesk CRM

FlowDesk e um CRM operacional para freelancers, agencias e equipes pequenas que precisam centralizar clientes, propostas, projetos, tarefas, financeiro, hospedagens e snippets tecnicos em um unico workspace.

O produto foi pensado para reduzir a troca de contexto entre planilhas, kanbans, ferramentas financeiras e blocos de notas. A proposta e entregar uma operacao mais clara, organizada e pronta para crescer.

## Visao Geral

O FlowDesk conecta os principais pontos da operacao:

- Clientes com historico, dados comerciais, acessos, atividades e materiais.
- Pipeline comercial para acompanhar oportunidades ate o fechamento.
- Propostas comerciais com pagina publica compartilhavel, aceite do cliente e solicitacao de ajustes.
- Projetos com kanban, tarefas ricas, checklist, comentarios e acompanhamento de entrega.
- Financeiro com contas a receber, contas a pagar, fechamento por cliente, Pix manual e documentos de cobranca.
- Hospedagens para gerenciar dominios, VPS, WordPress, renovacoes e vencimentos.
- Codigos para organizar snippets, instrucoes, categorias, favoritos e imagens de preview.
- Configuracoes de conta, workspace, modulos, integracoes e assinatura.

## Principais Recursos

- Dashboard operacional com indicadores e atividades recentes do workspace.
- Sistema de workspaces com roles e permissoes.
- Onboarding inicial em tela cheia.
- Controle visual de modulos ativos por usuario.
- Limites por plano e bloqueios visuais de criacao.
- Painel administrativo separado para gestao manual de planos.
- Links publicos protegidos por token curto para cobrancas e propostas.
- Sistema interno de notificacoes.
- Base de logs de erro para producao.
- Preparacao para integracao com Google Drive como armazenamento de arquivos.

## Stack

- PHP 8+
- MySQL / MariaDB
- PDO
- HTML, CSS e JavaScript vanilla
- Remix Icons
- MAMP para desenvolvimento local

## Estrutura

```text
app/                 Controllers, Models, Views, rotas e helpers
config/              Bootstrap, banco, ambiente e erros
database/migrations/ Scripts SQL incrementais
docs/                Documentacao tecnica e notas de producao
public/              Entrada publica, assets, CSS, JS e imagens fixas
storage/             Logs e arquivos internos nao publicos
```

## Status do Produto

O FlowDesk esta em desenvolvimento ativo. A base atual ja cobre os modulos principais do CRM, enquanto as proximas fases concentram integracoes, armazenamento externo, refinamentos de seguranca e automacoes.

## Licenca

Este projeto acompanha o arquivo `LICENSE` do repositorio.
