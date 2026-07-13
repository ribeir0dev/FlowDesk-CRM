# Google Drive como storage de arquivos

## Decisao

O FlowDesk vai usar o Google Drive do proprio usuario/workspace para armazenar arquivos ligados a clientes. A aplicacao deve criar uma pasta raiz do FlowDesk e uma subpasta para cada cliente, guardando ali imagens, fotos, comprovantes, logos e demais arquivos operacionais.

## Escopo da primeira entrega

- Criar tabelas para configuracao do Google Drive por workspace.
- Criar tabela para mapear cada cliente para uma pasta do Drive.
- Criar tabela de metadados dos arquivos ligados ao cliente.
- Preparar models/services internos sem acoplar os controllers atuais diretamente na API do Google.

## Fluxo esperado

1. O owner do workspace acessa `Configuracoes > Integracoes > Google Drive`.
2. O usuario conecta sua conta Google via OAuth.
3. O sistema solicita o escopo `https://www.googleapis.com/auth/drive.file`.
4. O sistema cria ou reutiliza a pasta raiz configurada, por padrao `FlowDesk`.
5. Ao anexar arquivo a um cliente, o sistema cria ou reutiliza a subpasta do cliente.
6. O arquivo e enviado ao Google Drive.
7. O FlowDesk salva os metadados em `cliente_arquivos`.

## Padrao de pastas

```text
FlowDesk/
  Cliente Exemplo #12/
    briefing.pdf
    logo.png
    comprovante-pagamento.webp
```

## Seguranca

- Usar o escopo minimo `drive.file`, evitando acesso amplo ao Drive inteiro.
- Conectar o Drive apenas por usuarios com permissao administrativa no workspace.
- Nunca registrar access token ou refresh token em logs.
- Armazenar tokens sempre criptografados antes de persistir.
- Validar tipo e tamanho dos arquivos antes do upload.
- Nunca tornar arquivos publicos por padrao.
- Sempre validar `workspace_id` e `cliente_id` antes de listar, criar ou baixar arquivos.

## Proxima etapa

Implementar OAuth do Google Drive:

- Gerar URL de autorizacao.
- Receber callback.
- Trocar `code` por tokens.
- Persistir tokens criptografados.
- Criar pasta raiz.
- Exibir status real da integracao no card do Google Drive.

Depois disso, migrar o upload de arquivos do cliente para usar `GoogleDriveStorageService` como destino principal.

## Referencias oficiais

- Escopos OAuth do Google: https://developers.google.com/identity/protocols/oauth2/scopes
- Uploads no Google Drive API: https://developers.google.com/drive/api/v3/manage-uploads
- Pastas no Google Drive API: https://developers.google.com/workspace/drive/api/guides/folder
