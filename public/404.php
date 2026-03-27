<?php
http_response_code(404);
$statusCode = 404;
$title = 'Pagina nao encontrada';
$message = 'O endereco solicitado nao existe ou foi movido para outra rota da aplicacao.';
$actionLabel = 'Voltar ao dashboard';
$actionHref = (isset($_SESSION['user_id']) ? (($base ?? '') . '/dashboard') : (($base ?? '') . '/'));
require __DIR__ . '/_error_template.php';
