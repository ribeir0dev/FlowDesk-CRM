<?php

require_once __DIR__ . '/errors.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/../app/Helpers/helpers.php';
