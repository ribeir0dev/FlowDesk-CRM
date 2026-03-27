# Fase 3 - Matriz de Permissoes Revisada

## Direcao Atual

Esta matriz substitui a ideia antiga de `viewer` como "somente leitura de tudo".

A nova regra recomendada para o FlowDesk e:

- `owner`: controle total do workspace
- `admin`: administracao ampla do workspace
- `operacional`: operacao comercial e de execucao
- `financeiro`: operacao financeira e modulos relacionados
- `viewer`: portal restrito do proprio cliente vinculado

---

## Regra Estrutural Para `viewer`

O `viewer` nao deve ser ligado ao cliente apenas por e-mail.

O ideal e criar um vinculo explicito entre usuario e cliente:

```sql
cliente_usuarios
---------------
id
workspace_id
cliente_id
user_id
created_at
```

### Motivo

- evita regra fragil baseada em e-mail
- permite mais de um usuario por cliente
- permite o mesmo usuario representar um cliente especifico dentro de um workspace
- deixa a permissao clara no banco e no backend

---

## O Que o `viewer` Passa a Ser

O `viewer` vira um perfil de acompanhamento externo, quase um portal do cliente.

Ele deve enxergar apenas dados vinculados ao cliente associado ao seu usuario:

- seu proprio cadastro de cliente
- seus projetos
- seus orcamentos
- suas hospedagens, quando fizer sentido

Ele nao deve enxergar:

- outros clientes
- pipeline geral
- financeiro interno da operacao
- equipe do workspace
- configuracoes administrativas

---

## Papeis

### `owner`

- dono da conta/workspace
- acesso total
- controle final sobre equipe, configuracoes e assinatura

### `admin`

- gestor interno
- acesso amplo ao workspace
- pode operar time, convites e modulos internos

### `operacional`

- perfil interno de producao e CRM
- foco em relacionamento, pipeline e entrega

### `financeiro`

- perfil interno de cobranca e acompanhamento financeiro
- foco em orcamentos, financeiro, clientes e hospedagens

### `viewer`

- perfil externo do cliente
- acesso somente ao proprio escopo vinculado

---

## Regra Geral Por Papel

- `owner`: tudo
- `admin`: tudo do workspace, exceto decisoes futuras exclusivas do dono
- `operacional`: `Clientes`, `Pipeline`, `Projetos`
- `financeiro`: `Orcamentos`, `Financeiro`, `Clientes`, `Hospedagens`
- `viewer`: somente dados do cliente vinculado

---

## Matriz por Modulo

### 1. Dashboard

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Visualizar dashboard interno | Sim | Sim | Sim | Sim | Nao |
| Visualizar indicadores financeiros internos | Sim | Sim | Sim | Nao | Nao |
| Visualizar dashboard do proprio cliente | Nao | Nao | Nao | Nao | Sim |

### 2. Clientes

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Listar clientes do workspace | Sim | Sim | Sim | Sim | Nao |
| Visualizar cliente | Sim | Sim | Sim | Sim | Apenas o proprio |
| Criar cliente | Sim | Sim | Nao | Sim | Nao |
| Editar cliente | Sim | Sim | Nao | Sim | Nao |
| Upload de foto/arquivos do cliente | Sim | Sim | Nao | Sim | Nao |
| Editar blocos compartilhados | Sim | Sim | Nao | Sim | Nao |
| Excluir cliente | Sim | Sim | Nao | Nao | Nao |

### 3. Pipeline

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Visualizar pipeline | Sim | Sim | Nao | Sim | Nao |
| Criar oportunidade | Sim | Sim | Nao | Sim | Nao |
| Editar oportunidade | Sim | Sim | Nao | Sim | Nao |
| Mover oportunidade de estagio | Sim | Sim | Nao | Sim | Nao |
| Marcar ganha/perdida | Sim | Sim | Nao | Sim | Nao |
| Excluir oportunidade | Sim | Sim | Nao | Nao | Nao |

### 4. Projetos

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Listar projetos | Sim | Sim | Nao | Sim | Apenas os proprios |
| Visualizar projeto | Sim | Sim | Nao | Sim | Apenas os proprios |
| Criar projeto | Sim | Sim | Nao | Sim | Nao |
| Editar projeto | Sim | Sim | Nao | Sim | Nao |
| Concluir projeto | Sim | Sim | Nao | Sim | Nao |
| Criar/editar tarefas | Sim | Sim | Nao | Sim | Nao |
| Mover tarefas no kanban | Sim | Sim | Nao | Sim | Nao |
| Excluir tarefas | Sim | Sim | Nao | Sim | Nao |
| Excluir projeto | Sim | Sim | Nao | Nao | Nao |

### 5. Orcamentos

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Listar orcamentos | Sim | Sim | Sim | Nao | Apenas os proprios |
| Visualizar orcamento | Sim | Sim | Sim | Nao | Apenas os proprios |
| Criar orcamento | Sim | Sim | Sim | Nao | Nao |
| Editar orcamento | Sim | Sim | Sim | Nao | Nao |
| Excluir orcamento | Sim | Sim | Nao | Nao | Nao |

### 6. Financeiro

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Acessar modulo financeiro | Sim | Sim | Sim | Nao | Nao |
| Visualizar entradas, saidas e gastos fixos | Sim | Sim | Sim | Nao | Nao |
| Criar/editar entrada | Sim | Sim | Sim | Nao | Nao |
| Criar/editar saida | Sim | Sim | Sim | Nao | Nao |
| Criar/editar gasto fixo | Sim | Sim | Sim | Nao | Nao |
| Excluir registros financeiros | Sim | Sim | Sim | Nao | Nao |

### 7. Hospedagens

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Listar hospedagens | Sim | Sim | Sim | Nao | Apenas as proprias |
| Visualizar hospedagem | Sim | Sim | Sim | Nao | Apenas as proprias |
| Criar hospedagem | Sim | Sim | Sim | Nao | Nao |
| Editar hospedagem | Sim | Sim | Sim | Nao | Nao |
| Excluir hospedagem | Sim | Sim | Nao | Nao | Nao |

### 8. Configuracoes do Workspace

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Visualizar proprio perfil | Sim | Sim | Sim | Sim | Sim |
| Editar proprio perfil | Sim | Sim | Sim | Sim | Sim |
| Visualizar equipe completa | Sim | Sim | Nao | Nao | Nao |
| Convidar membro | Sim | Sim | Nao | Nao | Nao |
| Revogar convite | Sim | Sim | Nao | Nao | Nao |
| Alterar papel de membro | Sim | Sim | Nao | Nao | Nao |
| Remover membro | Sim | Sim | Nao | Nao | Nao |

### 9. Assinatura e Billing SaaS

| Acao | owner | admin | financeiro | operacional | viewer |
|---|---|---|---|---|---|
| Visualizar plano atual | Sim | Sim | Sim | Nao | Nao |
| Alterar plano | Sim | Nao | Nao | Nao | Nao |
| Cancelar assinatura | Sim | Nao | Nao | Nao | Nao |
| Visualizar cobrancas | Sim | Sim | Sim | Nao | Nao |

---

## Ajustes de Navegacao Recomendados

### `operacional`

Deve ver no menu:

- Dashboard
- Clientes
- Pipeline
- Projetos

Nao deve ver:

- Orcamentos
- Financeiro
- Hospedagens
- gestao completa de configuracoes

### `financeiro`

Deve ver no menu:

- Dashboard
- Clientes
- Orcamentos
- Financeiro
- Hospedagens

Nao deve ver:

- Pipeline
- Projetos
- gestao de equipe

### `viewer`

Idealmente nao deve usar o mesmo menu interno completo.

Opcoes melhores:

1. Criar um menu reduzido com:
- Meu cliente
- Meus projetos
- Meus orcamentos
- Minhas hospedagens

2. Ou criar uma area separada tipo "Portal do Cliente"

---

## Impacto Tecnico Recomendado

### Banco

Criar:

```sql
cliente_usuarios
---------------
id
workspace_id
cliente_id
user_id
created_at
```

### Backend

- criar helper `fd_current_cliente_id()` para `viewer`
- toda query de `viewer` precisa adicionar filtro por `cliente_id`
- bloquear acesso por URL direta quando o recurso nao pertencer ao cliente vinculado

### Frontend

- esconder modulos fora do escopo do papel
- reduzir o menu do `viewer`
- esconder listas gerais quando o papel for cliente externo

---

## Ordem Recomendada de Implementacao

1. Criar `cliente_usuarios`
2. Vincular `viewer` a um cliente especifico
3. Ajustar o menu por papel
4. Restringir queries de `viewer` por `cliente_id`
5. Ajustar `operacional` para ver apenas `Clientes`, `Pipeline`, `Projetos`
6. Ajustar `financeiro` para ver apenas `Clientes`, `Orcamentos`, `Financeiro`, `Hospedagens`
7. Criar experiencia de "portal do cliente" para o `viewer`

---

## Conclusao

Esta revisao faz o papel `viewer` deixar de ser um perfil generico e passar a ter funcao real no produto.

O ganho principal e:

- mais coerencia de negocio
- menos risco de exposicao desnecessaria
- melhor base para vender o FlowDesk como workspace interno + portal do cliente
