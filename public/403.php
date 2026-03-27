<?php
http_response_code(403);
$statusCode = 403;
$title = 'Acesso negado';
$message = 'Voce nao tem permissao para acessar este recurso com a sessao atual.';
$actionLabel = 'Voltar';
$actionHref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (($base ?? '') . '/');
require __DIR__ . '/_error_template.php';
