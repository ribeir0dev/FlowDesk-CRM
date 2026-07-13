<?php

require_once __DIR__ . '/env.php';

function fd_configure_error_logging(): void
{
    static $configured = false;
    if ($configured) {
        return;
    }

    fd_load_env();

    $env = strtolower((string) (getenv('APP_ENV') ?: 'production'));
    $isLocalEnv = in_array($env, ['local', 'development'], true);
    $timezone = trim((string) (getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'));
    $logPath = trim((string) (getenv('APP_LOG_PATH') ?: ''));

    if ($timezone !== '') {
        date_default_timezone_set($timezone);
    }

    if ($logPath === '') {
        $logDir = dirname(__DIR__) . '/storage/logs';
        $logPath = $logDir . '/flowdesk-error.log';
    } else {
        $logDir = dirname($logPath);
    }

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    if (!is_writable($logDir)) {
        $logPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'flowdesk-error.log';
    }

    ini_set('log_errors', '1');
    ini_set('error_log', $logPath);
    ini_set('display_errors', $isLocalEnv ? '1' : '0');
    ini_set('display_startup_errors', $isLocalEnv ? '1' : '0');
    error_reporting(E_ALL);

    register_shutdown_function(static function () use ($logPath): void {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) $error['type'], $fatalTypes, true)) {
            return;
        }

        error_log(sprintf(
            '[FlowDesk][Fatal] %s in %s:%s',
            $error['message'] ?? 'Erro fatal desconhecido',
            $error['file'] ?? 'arquivo desconhecido',
            $error['line'] ?? 'linha desconhecida'
        ));
    });

    $configured = true;
}

fd_configure_error_logging();
