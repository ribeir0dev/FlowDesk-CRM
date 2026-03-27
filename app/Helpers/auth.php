<?php
// FlowDesk auth helpers

if (!function_exists('gerarTokenPublico')) {
    function gerarTokenPublico($length = 64) {
        // Gera um token hex seguro, ex: 64 caracteres
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('fd_ensure_session')) {
    function fd_ensure_session(): void
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

        fd_enforce_session_security();
    }
}

if (!function_exists('fd_base_path')) {
    function fd_base_path(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($base === '/' || $base === '\\' || $base === '.') {
            return '';
        }

        return $base;
    }
}

if (!function_exists('fd_current_workspace_id')) {
    function fd_current_workspace_id(): ?int
    {
        fd_ensure_session();
        $workspaceId = $_SESSION['current_workspace_id'] ?? null;
        return $workspaceId ? (int) $workspaceId : null;
    }
}

if (!function_exists('fd_session_idle_timeout')) {
    function fd_session_idle_timeout(): int
    {
        return !empty($_SESSION['auth_remember']) ? 60 * 60 * 24 * 30 : 60 * 60 * 2;
    }
}

if (!function_exists('fd_session_absolute_timeout')) {
    function fd_session_absolute_timeout(): int
    {
        return !empty($_SESSION['auth_remember']) ? 60 * 60 * 24 * 30 : 60 * 60 * 12;
    }
}

if (!function_exists('fd_destroy_authenticated_session')) {
    function fd_destroy_authenticated_session(?string $reason = null): void
    {
        $_SESSION = [];
        session_unset();
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if ($reason !== null) {
            session_start();
            $_SESSION['auth_flash_error'] = $reason;
        }
    }
}

if (!function_exists('fd_mark_login_session')) {
    function fd_mark_login_session(bool $remember = false): void
    {
        $now = time();
        $_SESSION['auth_remember'] = $remember ? 1 : 0;
        $_SESSION['auth_started_at'] = $now;
        $_SESSION['auth_last_activity_at'] = $now;
        $_SESSION['auth_last_regenerated_at'] = $now;
    }
}

if (!function_exists('fd_enforce_session_security')) {
    function fd_enforce_session_security(): void
    {
        if (empty($_SESSION['user_id'])) {
            return;
        }

        $now = time();
        $startedAt = (int) ($_SESSION['auth_started_at'] ?? $now);
        $lastActivityAt = (int) ($_SESSION['auth_last_activity_at'] ?? $startedAt);
        $lastRegeneratedAt = (int) ($_SESSION['auth_last_regenerated_at'] ?? $startedAt);

        $idleExpired = ($now - $lastActivityAt) > fd_session_idle_timeout();
        $absoluteExpired = ($now - $startedAt) > fd_session_absolute_timeout();

        if ($idleExpired || $absoluteExpired) {
            fd_destroy_authenticated_session('sessao');

            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
            $publicAuthPaths = [
                fd_base_path() . '/login',
                fd_base_path() . '/esqueci-senha',
                fd_base_path() . '/redefinir-senha',
                fd_base_path() . '/cadastro',
            ];

            if (!in_array($path, $publicAuthPaths, true)) {
                header('Location: ' . fd_base_path() . '/login?erro=sessao');
                exit;
            }

            return;
        }

        $_SESSION['auth_last_activity_at'] = $now;

        if (($now - $lastRegeneratedAt) > 60 * 15) {
            session_regenerate_id(true);
            $_SESSION['auth_last_regenerated_at'] = $now;
        }
    }
}

if (!function_exists('fd_current_workspace_role')) {
    function fd_current_workspace_role(): ?string
    {
        fd_ensure_session();
        $role = $_SESSION['current_workspace_role'] ?? null;
        return is_string($role) && $role !== '' ? $role : null;
    }
}

if (!function_exists('fd_workspace_needs_onboarding')) {
    function fd_workspace_needs_onboarding(): bool
    {
        fd_ensure_session();

        $role = fd_current_workspace_role();
        if (!in_array($role, ['owner', 'admin'], true)) {
            return false;
        }

        $workspaceId = fd_current_workspace_id();
        if (!$workspaceId) {
            return false;
        }

        global $pdo;
        if (!($pdo instanceof PDO)) {
            require __DIR__ . '/../../config/db.php';
        }

        try {
            $stmt = $pdo->prepare('
                SELECT onboarding_concluido_em
                FROM workspaces
                WHERE id = ?
                LIMIT 1
            ');
            $stmt->execute([$workspaceId]);
            $value = $stmt->fetchColumn();

            return empty($value);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('fd_current_cliente_id')) {
    function fd_current_cliente_id(): ?int
    {
        fd_ensure_session();

        if (fd_current_workspace_role() !== 'viewer') {
            return null;
        }

        $workspaceId = (int) ($_SESSION['current_workspace_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($workspaceId <= 0 || $userId <= 0) {
            return null;
        }

        if (isset($_SESSION['current_cliente_id'])) {
            $clienteId = (int) $_SESSION['current_cliente_id'];
            return $clienteId > 0 ? $clienteId : null;
        }

        global $pdo;
        if (!($pdo instanceof PDO)) {
            require __DIR__ . '/../../config/db.php';
        }

        try {
            $stmt = $pdo->prepare('
                SELECT cliente_id
                FROM cliente_usuarios
                WHERE workspace_id = ?
                  AND user_id = ?
                ORDER BY id ASC
                LIMIT 1
            ');
            $stmt->execute([$workspaceId, $userId]);
            $clienteId = (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $clienteId = 0;
        }

        $_SESSION['current_cliente_id'] = $clienteId > 0 ? $clienteId : null;
        return $clienteId > 0 ? $clienteId : null;
    }
}

if (!function_exists('fd_workspace_roles')) {
    function fd_workspace_roles(): array
    {
        return ['owner', 'admin', 'operacional', 'financeiro', 'viewer'];
    }
}

if (!function_exists('fd_default_workspace_path')) {
    function fd_default_workspace_path(?string $role = null): string
    {
        $role = $role ?? fd_current_workspace_role();
        $base = fd_base_path();
        $viewerClienteId = fd_current_cliente_id();

        if (in_array($role, ['owner', 'admin'], true) && fd_workspace_needs_onboarding()) {
            return $base . '/onboarding';
        }

        return match ($role) {
            'financeiro' => $base . '/financeiro',
            'operacional' => $base . '/clientes',
            'viewer' => $viewerClienteId !== null ? $base . '/cliente?id=' . $viewerClienteId : $base . '/clientes',
            default => $base . '/dashboard',
        };
    }
}

if (!function_exists('fd_role_rank')) {
    function fd_role_rank(?string $role): int
    {
        return match ($role) {
            'owner' => 50,
            'admin' => 40,
            'financeiro' => 30,
            'operacional' => 20,
            'viewer' => 10,
            default => 0,
        };
    }
}

if (!function_exists('fd_has_any_role')) {
    function fd_has_any_role(string|array $roles): bool
    {
        $currentRole = fd_current_workspace_role();
        if ($currentRole === null) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($currentRole, $roles, true);
    }
}

if (!function_exists('fd_has_min_role')) {
    function fd_has_min_role(string $role): bool
    {
        return fd_role_rank(fd_current_workspace_role()) >= fd_role_rank($role);
    }
}

if (!function_exists('fd_require_workspace')) {
    function fd_require_workspace(): void
    {
        fd_ensure_session();

        if (empty($_SESSION['user_id']) || empty($_SESSION['current_workspace_id'])) {
            header('Location: ' . fd_base_path() . '/login');
            exit;
        }
    }
}

if (!function_exists('fd_require_role')) {
    function fd_require_role(string|array $roles): void
    {
        fd_require_workspace();

        if (!fd_has_any_role($roles)) {
            header('Location: ' . fd_base_path() . '/403.php');
            exit;
        }
    }
}

if (!function_exists('fd_enforce_workspace_onboarding')) {
    function fd_enforce_workspace_onboarding(): void
    {
        fd_require_workspace();

        if (!fd_workspace_needs_onboarding()) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $base = fd_base_path();
        $allowedPaths = [
            $base . '/onboarding',
            $base . '/onboarding/salvar',
            $base . '/logout',
            $base . '/workspace/trocar',
        ];

        if (!in_array($path, $allowedPaths, true)) {
            header('Location: ' . $base . '/onboarding');
            exit;
        }
    }
}

if (!function_exists('fd_audit_log')) {
    function fd_audit_log(
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        ?array $payload = null
    ): bool {
        fd_ensure_session();

        $workspaceId = (int) ($_SESSION['current_workspace_id'] ?? 0);
        if ($workspaceId <= 0) {
            return false;
        }

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if (!class_exists('AuditLogModel', false)) {
            require_once __DIR__ . '/../Models/AuditLogModel.php';
        }

        global $pdo;
        if (!($pdo instanceof PDO)) {
            require __DIR__ . '/../../config/db.php';
        }

        static $auditModel = null;
        if (!$auditModel instanceof AuditLogModel) {
            $auditModel = new AuditLogModel($pdo);
        }

        return $auditModel->registrar($workspaceId, $userId, $acao, $entidade, $entidadeId, $payload);
    }
}

function require_login(): void
{
    fd_ensure_session();

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . fd_base_path() . '/login');
        exit;
    }
}
