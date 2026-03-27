<?php
http_response_code(500);
$statusCode = 500;
$title = 'Erro interno';
$message = 'Ocorreu um erro inesperado ao processar esta requisicao. Tente novamente em instantes.';
$actionLabel = 'Tentar novamente';
$actionHref = isset($_SERVER['REQUEST_URI']) ? (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: (($base ?? '') . '/')) : (($base ?? '') . '/');
require __DIR__ . '/_error_template.php';
