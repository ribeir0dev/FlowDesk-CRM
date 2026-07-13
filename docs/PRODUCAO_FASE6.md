# FlowDesk CRM - Operacao de Producao

Este documento registra o processo minimo para publicar, manter e recuperar o FlowDesk em producao.

## 1. Arquivo `.env`

Nunca versionar `.env`. Use `.env.example` como base e crie um `.env` especifico no ambiente de producao.

Exemplo para Hostinger:

```env
APP_ENV=production
APP_URL=https://flowdesk.site/painel
APP_KEY=gere-uma-chave-longa-aleatoria
APP_TIMEZONE=America/Sao_Paulo
APP_LOG_PATH=/home/u710601266/domains/flowdesk.site/public_html/painel/storage/logs/flowdesk-error.log

DB_HOST=localhost
DB_NAME=u710601266_BKi78
DB_USER=u710601266_scmlti
DB_PASS=sua-senha-real-aqui
DB_CHARSET=utf8mb4
```

Checklist:

- `APP_ENV` deve ser `production` em producao.
- `APP_KEY` precisa ser longo e exclusivo por ambiente.
- `APP_LOG_PATH` deve apontar para uma pasta gravavel.
- `DB_PASS` nunca deve ser enviado para repositorio, print ou chat.

## 2. Deploy de arquivos

1. Fazer backup do banco antes de substituir arquivos.
2. Enviar o projeto para `public_html/painel`.
3. Nao enviar `node_modules`, `.git`, `.env` local, arquivos `tmp_*`, dumps antigos ou uploads de desenvolvimento.
4. Garantir que `public_html/painel/storage/logs` exista e tenha permissao de escrita.
5. Garantir que `public_html/painel/public/uploads` exista e tenha permissao de escrita se uploads locais forem usados.
6. Manter o `.env` de producao no servidor.
7. Acessar `https://flowdesk.site/painel/` e validar login, dashboard e um fluxo simples.

## 3. Banco de dados

Ao publicar uma versao nova:

1. Exportar backup do banco atual.
2. Rodar somente migrations ainda nao aplicadas.
3. Validar se novas colunas/tabelas existem.
4. Testar login e modulos principais.

Comando de referencia local:

```bash
mysqldump -u USUARIO -p BANCO > backup-flowdesk-YYYY-MM-DD.sql
```

Na Hostinger, se nao houver acesso SSH, use o phpMyAdmin para exportar/importar.

## 4. Rollback simples

Antes de deploy, manter:

- Zip da versao anterior dos arquivos.
- Dump SQL antes da migracao.
- Lista das migrations aplicadas.

Se algo quebrar:

1. Colocar o site em manutencao, se necessario.
2. Restaurar arquivos da versao anterior.
3. Restaurar dump do banco apenas se a migracao tiver alterado dados de forma incompatível.
4. Verificar `storage/logs/flowdesk-error.log`.
5. Registrar a causa antes de tentar novo deploy.

## 5. Validacao rapida pos-deploy

- Abrir landing publica.
- Fazer login.
- Abrir Dashboard.
- Criar e excluir um cliente de teste.
- Criar e excluir um projeto de teste.
- Criar e excluir um orcamento de teste.
- Abrir Financeiro.
- Abrir Configuracoes.
- Conferir se erros foram gravados em `storage/logs/flowdesk-error.log`.

## 6. Observacoes atuais

- Uploads ainda usam `public/uploads`.
- O plano futuro e mover anexos pesados para Google Drive ou storage externo.
- O projeto ja possui logging central em `config/errors.php`.
- O processo de migrations ainda e manual e deve ser formalizado antes de escala comercial.
