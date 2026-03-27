# Fase 2 - Checklist de Banco de Dados

Use esta checklist para acompanhar a preparaÃ§Ã£o da base antes e durante a fundaÃ§Ã£o multi-tenant.

## Regras

- Marque `[x]` apenas quando a etapa estiver validada.
- Execute em ambiente local primeiro.
- NÃ£o rode tudo em produÃ§Ã£o de uma vez.
- Sempre faÃ§a backup antes de alteraÃ§Ãµes estruturais.

---

## Bloco 1 - PreparaÃ§Ã£o

- [ ] Fazer backup completo do banco atual `db_flowdesk`.
- [ ] Confirmar charset/collation alvo como `utf8mb4` / `utf8mb4_unicode_ci`.
- [ ] Revisar tabelas fora do core principal e decidir o destino de `modelos`.
- [ ] Revisar enums e slugs inconsistentes antes da migraÃ§Ã£o multi-tenant.
- [ ] Mapear todos os `UNIQUE` globais que precisarÃ£o virar `UNIQUE` por workspace.

---

## Bloco 2 - Novas tabelas SaaS

- [x] Criar tabela `workspaces`.
- [x] Criar tabela `workspace_members`.
- [x] Criar tabela `plans`.
- [x] Criar tabela `subscriptions`.
- [x] Criar tabela `audit_logs`.

---

## Bloco 3 - Adicionar `workspace_id`

- [x] Adicionar `workspace_id` em `clientes`.
- [x] Adicionar `workspace_id` em `cliente_blocos`.
- [x] Adicionar `workspace_id` em `funil_estagios`.
- [x] Adicionar `workspace_id` em `oportunidades`.
- [x] Adicionar `workspace_id` em `projetos`.
- [x] Adicionar `workspace_id` em `projeto_tarefas`.
- [x] Adicionar `workspace_id` em `orcamentos`.
- [x] Adicionar `workspace_id` em `orcamento_itens`.
- [x] Adicionar `workspace_id` em `financeiro_entradas`.
- [x] Adicionar `workspace_id` em `financeiro_saidas`.
- [x] Adicionar `workspace_id` em `financeiro_fixos`.
- [x] Adicionar `workspace_id` em `hospedagens`.

---

## Bloco 4 - MigraÃ§Ã£o de dados iniciais

- [x] Criar o workspace padrÃ£o inicial.
- [x] Migrar `clientes` para o workspace padrÃ£o.
- [x] Migrar `cliente_blocos` para o workspace padrÃ£o.
- [x] Migrar `funil_estagios` para o workspace padrÃ£o.
- [x] Migrar `oportunidades` para o workspace padrÃ£o.
- [x] Migrar `projetos` para o workspace padrÃ£o.
- [x] Migrar `projeto_tarefas` para o workspace padrÃ£o.
- [x] Migrar `orcamentos` para o workspace padrÃ£o.
- [x] Migrar `orcamento_itens` para o workspace padrÃ£o.
- [x] Migrar `financeiro_entradas` para o workspace padrÃ£o.
- [x] Migrar `financeiro_saidas` para o workspace padrÃ£o.
- [x] Migrar `financeiro_fixos` para o workspace padrÃ£o.
- [x] Migrar `hospedagens` para o workspace padrÃ£o.
- [x] Criar vÃ­nculo do usuÃ¡rio principal com papel `owner` em `workspace_members`.

---

## Bloco 5 - RestriÃ§Ãµes e Ã­ndices

- [x] Tornar `workspace_id` obrigatÃ³rio em `clientes`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `cliente_blocos`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `funil_estagios`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `oportunidades`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `projetos`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `projeto_tarefas`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `orcamentos`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `orcamento_itens`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `financeiro_entradas`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `financeiro_saidas`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `financeiro_fixos`.
- [x] Tornar `workspace_id` obrigatÃ³rio em `hospedagens`.
- [x] Criar FKs de `workspace_id` para `workspaces`.
- [x] Trocar `clientes.email` de unique global para unique por workspace.
- [x] Trocar `funil_estagios.slug` de unique global para unique por workspace.
- [ ] Criar Ã­ndices compostos principais por `workspace_id`.

---

## Bloco 6 - Ajustes de modelo

- [ ] Remover `usuarios.plano` do modelo atual de assinatura.
- [ ] Mover a lÃ³gica de plano para `subscriptions`.
- [ ] Revisar `financeiro_fixos` e decidir se jÃ¡ vira `financeiro_recorrencias`.
- [ ] Padronizar nomes de colunas de auditoria (`created_at`, `updated_at`) no planejamento de mÃ©dio prazo.
- [ ] Padronizar charset/collation das tabelas antigas.

---

## Bloco 7 - AplicaÃ§Ã£o

- [x] Guardar `current_workspace_id` na sessÃ£o.
- [x] Adaptar `AuthModel`.
- [x] Adaptar `AuthController`.
- [x] Adaptar `ClienteModel`.
- [x] Adaptar `OportunidadeModel`.
- [x] Adaptar `ProjetoModel`.
- [x] Adaptar `OrcamentoModel`.
- [x] Adaptar `FinanceiroModel`.
- [x] Adaptar `HospedagemModel`.
- [x] Adaptar `DashboardModel`.
- [x] Revisar queries soltas em views para filtrar por workspace.
- [x] Revisar endpoints de busca, relatÃ³rios e links pÃºblicos.

---

## Bloco 8 - QA da Fase 2

- [ ] Validar login com `current_workspace_id`.
- [ ] Validar isolamento de clientes por workspace.
- [ ] Validar isolamento de pipeline por workspace.
- [ ] Validar isolamento de projetos e tarefas por workspace.
- [ ] Validar isolamento de orÃ§amentos por workspace.
- [ ] Validar isolamento de financeiro por workspace.
- [ ] Validar isolamento de hospedagens por workspace.
- [ ] Validar dashboard agregado por workspace.
- [ ] Confirmar que um usuÃ¡rio nÃ£o acessa dados de outro workspace por URL direta.

---

## Fechamento

- [ ] Estrutura multi-tenant pronta no banco.
- [ ] AplicaÃ§Ã£o filtrando por workspace em todos os mÃ³dulos principais.
- [ ] Base preparada para membros, planos e billing.

