<?php
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/token.php';
spl_autoload_register(function ($class) {

    $base_dir = __DIR__ . '/../app/';

    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});