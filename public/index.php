<?php
require_once __DIR__ . '/../config/errors.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../app/Helpers/helpers.php';
require_once __DIR__ . '/../app/Core/router.php';

set_exception_handler(function (Throwable $e) {
    error_log('[FlowDesk] Uncaught exception: ' . $e);
    http_response_code(500);
    require __DIR__ . '/500.php';
    exit;
});

$router = new Router();

require __DIR__ . '/../app/routes/web.php';

$router->dispatch();
