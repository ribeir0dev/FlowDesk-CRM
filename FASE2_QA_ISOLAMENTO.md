# Fase 2 - QA de Isolamento Multi-tenant

Use esta checklist para validar que cada workspace enxerga apenas os seus proprios dados.

## Como validar

- Tenha pelo menos 2 workspaces com dados diferentes.
- Tenha pelo menos 1 usuario vinculado a cada workspace.
- Sempre teste com dados que facilitem identificar vazamento: nomes distintos, clientes distintos, projetos distintos.
- Marque `[x]` apenas quando o teste estiver realmente validado no navegador.

---

## Preparacao

- [x] Existe um `Workspace A` com dados proprios.
- [x] Existe um `Workspace B` com dados proprios.
- [x] O usuario do `Workspace A` nao esta vinculado ao `Workspace B`.
- [x] O usuario do `Workspace B` nao esta vinculado ao `Workspace A`.
- [x] Os dois workspaces possuem pelo menos 1 cliente cadastrado.
- [x] Os dois workspaces possuem pelo menos 1 projeto, 1 oportunidade e 1 orcamento.

---

## Login e Sessao

- [x] Fazer login com usuario do `Workspace A` redireciona corretamente para o dashboard.
- [x] Fazer login com usuario do `Workspace B` redireciona corretamente para o dashboard.
- [x] Logout continua funcional com workspace ativo.
- [x] Usuario sem `workspace_members` nao entra no painel.

---

## Dashboard

- [x] O dashboard do `Workspace A` mostra apenas clientes, tarefas, financeiro e hospedagens do `Workspace A`.
- [x] O dashboard do `Workspace B` mostra apenas clientes, tarefas, financeiro e hospedagens do `Workspace B`.
- [x] Os totais mudam corretamente quando se troca de usuario/workspace.

---

## Clientes

- [x] A listagem de clientes do `Workspace A` nao mostra clientes do `Workspace B`.
- [x] A listagem de clientes do `Workspace B` nao mostra clientes do `Workspace A`.
- [x] Criar cliente salva no workspace logado.
- [x] Editar cliente nao permite alterar cliente de outro workspace por URL ou form manual.
- [x] Upload de foto continua funcionando dentro do workspace correto.
- [x] Blocos do cliente salvam e carregam no workspace correto.
- [x] Relatorio publico por token continua funcionando para o cliente certo.

---

## Pipeline

- [x] O pipeline do `Workspace A` mostra apenas oportunidades do `Workspace A`.
- [x] O pipeline do `Workspace B` mostra apenas oportunidades do `Workspace B`.
- [x] Criar oportunidade salva no workspace logado.
- [x] Editar oportunidade nao altera registro de outro workspace.
- [x] Mover oportunidade por drag and drop nao aceita registro de outro workspace.
- [x] Marcar como ganha/perdida respeita o workspace atual.

---

## Projetos e Tarefas

- [x] A listagem de projetos do `Workspace A` nao mostra projetos do `Workspace B`.
- [x] A listagem de projetos do `Workspace B` nao mostra projetos do `Workspace A`.
- [x] Criar projeto salva no workspace logado.
- [x] Editar projeto nao altera projeto de outro workspace.
- [x] A tela de detalhe do projeto nao abre projeto de outro workspace por URL direta.
- [x] Criar tarefa salva no workspace correto.
- [x] Mover tarefa no kanban respeita o workspace correto.
- [x] Excluir tarefa nao remove tarefa de outro workspace.

---

## Orcamentos

- [x] A listagem de orcamentos do `Workspace A` nao mostra orcamentos do `Workspace B`.
- [x] A listagem de orcamentos do `Workspace B` nao mostra orcamentos do `Workspace A`.
- [x] Criar orcamento salva no workspace logado.
- [x] Editar orcamento nao altera orcamento de outro workspace.
- [x] Buscar orcamento por endpoint retorna vazio/erro quando o ID eh de outro workspace.
- [x] Itens de orcamento permanecem vinculados ao mesmo workspace.

---

## Financeiro

- [x] Entradas do `Workspace A` nao aparecem no `Workspace B`.
- [x] Saidas do `Workspace A` nao aparecem no `Workspace B`.
- [x] Gastos fixos do `Workspace A` nao aparecem no `Workspace B`.
- [x] Criar entrada salva no workspace logado.
- [x] Editar entrada nao altera registro de outro workspace.
- [x] Excluir entrada nao remove registro de outro workspace.
- [x] Criar saida salva no workspace logado.
- [x] Excluir saida respeita o workspace atual.
- [x] Criar gasto fixo salva no workspace logado.
- [x] Pagar ou remover gasto fixo nao atua em registro de outro workspace.
- [x] Os cards e graficos da aba `Analise` agregam apenas dados do workspace atual.

---

## Hospedagens

- [x] A listagem de hospedagens do `Workspace A` nao mostra hospedagens do `Workspace B`.
- [x] Criar hospedagem salva no workspace logado.
- [x] Excluir hospedagem nao remove registro de outro workspace.

---

## Busca Global

- [x] A busca global do `Workspace A` retorna apenas clientes e projetos do `Workspace A`.
- [x] A busca global do `Workspace B` retorna apenas clientes e projetos do `Workspace B`.

---

## URL Direta e Acesso Cruzado

- [x] `/cliente?id=...` nao abre cliente de outro workspace.
- [x] `/projeto?id=...` nao abre projeto de outro workspace.
- [x] `/orcamento?id=...` nao abre orcamento de outro workspace.
- [x] `/projetos/buscar?id=...` nao retorna projeto de outro workspace.
- [x] `/pipeline/buscar?id=...` nao retorna oportunidade de outro workspace.
- [x] `/orcamentos/buscar?id=...` nao retorna orcamento de outro workspace.
- [x] `/financeiro/entrada?id=...` nao retorna entrada de outro workspace.

---

## Fechamento

- [x] Cada workspace enxerga apenas seus proprios dados.
- [x] Nao houve vazamento por URL direta.
- [x] CRUD principal respeita `workspace_id` em todos os modulos centrais.
- [x] A Fase 2 pode ser considerada concluida com seguranca.
