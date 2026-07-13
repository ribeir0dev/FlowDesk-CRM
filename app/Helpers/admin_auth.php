<?php

if (!function_exists('fd_admin_session_key')) {
    function fd_admin_session_key(): string
    {
        return 'flowdesk_admin_auth';
    }
}

if (!function_exists('fd_admin_ensure_session')) {
    function fd_admin_ensure_session(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            session_start();
        }
    }
}

if (!function_exists('fd_admin_is_authenticated')) {
    function fd_admin_is_authenticated(): bool
    {
        fd_admin_ensure_session();
        $auth = $_SESSION[fd_admin_session_key()] ?? null;

        if (!is_array($auth) || empty($auth['authenticated'])) {
            return false;
        }

        $now = time();
        $lastActivity = (int) ($auth['last_activity_at'] ?? 0);
        if ($lastActivity <= 0 || ($now - $lastActivity) > 7200) {
            unset($_SESSION[fd_admin_session_key()]);
            return false;
        }

        $_SESSION[fd_admin_session_key()]['last_activity_at'] = $now;
        return true;
    }
}

if (!function_exists('fd_admin_login')) {
    function fd_admin_login(string $login): void
    {
        fd_admin_ensure_session();
        session_regenerate_id(true);

        $_SESSION[fd_admin_session_key()] = [
            'authenticated' => true,
            'login' => $login,
            'authenticated_at' => time(),
            'last_activity_at' => time(),
        ];

        unset($_SESSION['flowdesk_admin_login_attempts'], $_SESSION['flowdesk_admin_lock_until']);
    }
}

if (!function_exists('fd_admin_logout')) {
    function fd_admin_logout(): void
    {
        fd_admin_ensure_session();
        unset(
            $_SESSION[fd_admin_session_key()],
            $_SESSION['flowdesk_admin_login_attempts'],
            $_SESSION['flowdesk_admin_lock_until'],
            $_SESSION['flowdesk_admin_flash']
        );
        session_regenerate_id(true);
    }
}

if (!function_exists('fd_admin_require_auth')) {
    function fd_admin_require_auth(): void
    {
        if (!fd_admin_is_authenticated()) {
            header('Location: ' . fd_base_path() . '/admin/login');
            exit;
        }
    }
}

if (!function_exists('fd_admin_flash')) {
    function fd_admin_flash(string $type, string $message): void
    {
        fd_admin_ensure_session();
        $_SESSION['flowdesk_admin_flash'] = [
            'type' => in_array($type, ['success', 'danger', 'warning', 'info'], true) ? $type : 'info',
            'message' => $message,
        ];
    }
}

if (!function_exists('fd_admin_pull_flash')) {
    function fd_admin_pull_flash(): ?array
    {
        fd_admin_ensure_session();
        $flash = $_SESSION['flowdesk_admin_flash'] ?? null;
        unset($_SESSION['flowdesk_admin_flash']);

        return is_array($flash) ? $flash : null;
    }
}
