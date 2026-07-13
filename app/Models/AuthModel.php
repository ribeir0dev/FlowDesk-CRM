<?php
// app/Models/AuthModel.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

class AuthModel
{
    private PDO $pdo;
    private array $columnCache = [];
    private const DEFAULT_PIPELINE_STAGES = [
        ['nome' => 'Lead', 'slug' => 'lead', 'ordem' => 1, 'cor_hex' => '#2563eb'],
        ['nome' => 'Proposta enviada', 'slug' => 'proposta_enviada', 'ordem' => 2, 'cor_hex' => '#0ea5e9'],
        ['nome' => 'Fechado (ganho)', 'slug' => 'fechado_ganho', 'ordem' => 3, 'cor_hex' => '#22c55e'],
        ['nome' => 'Perdido', 'slug' => 'perdido', 'ordem' => 4, 'cor_hex' => '#ef4444'],
    ];

    private function hasUserColumn(string $column): bool
    {
        if (array_key_exists($column, $this->columnCache)) {
            return $this->columnCache[$column];
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = \'usuarios\'
              AND COLUMN_NAME = ?
        ');
        $stmt->execute([$column]);

        return $this->columnCache[$column] = (bool) $stmt->fetchColumn();
    }

    private function normalizePublicToken(string $value): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
        $value = rawurldecode($value);

        if (str_contains($value, 'token=')) {
            $query = parse_url($value, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                parse_str($query, $params);
                $value = (string) ($params['token'] ?? $value);
            }
        }

        $value = preg_replace('/[^a-f0-9]/i', '', $value) ?? '';
        return strtolower($value);
    }

    private function passwordResetExpiresAtUtc(): string
    {
        return gmdate('Y-m-d H:i:s', time() + (2 * 60 * 60));
    }

    private function emailVerificationExpiresAtUtc(): string
    {
        return gmdate('Y-m-d H:i:s', time() + (24 * 60 * 60));
    }

    private function isPasswordResetExpired(?string $expiresAt): bool
    {
        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return true;
        }

        $expiresTs = strtotime(trim($expiresAt) . ' UTC');
        if ($expiresTs === false) {
            return true;
        }

        return $expiresTs < time();
    }

    private function isEmailVerificationExpired(?string $expiresAt): bool
    {
        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return true;
        }

        $expiresTs = strtotime(trim($expiresAt) . ' UTC');
        if ($expiresTs === false) {
            return true;
        }

        return $expiresTs < time();
    }

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByLogin(string $userOrEmail): ?array
    {
        $sql = '
            SELECT
                u.id,
                u.nome,
                u.email,
                u.email_verificado_em,
                u.senha,
                u.foto_perfil,
                u.preferred_theme,
                u.preferred_locale,
                u.preferred_timezone,
                u.sidebar_modules_json,
                wm.workspace_id,
                wm.role AS workspace_role,
                wm.is_primary,
                w.nome AS workspace_nome,
                w.status AS workspace_status
            FROM usuarios u
            LEFT JOIN workspace_members wm
              ON wm.id = (
                    SELECT wm2.id
                    FROM workspace_members wm2
                    WHERE wm2.user_id = u.id
                    ORDER BY wm2.is_primary DESC, wm2.id ASC
                    LIMIT 1
                 )
            LEFT JOIN workspaces w
              ON w.id = wm.workspace_id
            WHERE u.email = ? OR u.nome = ?
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userOrEmail, $userOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function listarWorkspacesDoUsuario(int $userId): array
    {
        $sql = '
            SELECT
                wm.workspace_id,
                wm.role,
                wm.is_primary,
                w.nome AS workspace_nome,
                w.slug AS workspace_slug,
                w.status AS workspace_status
            FROM workspace_members wm
            INNER JOIN workspaces w
              ON w.id = wm.workspace_id
            WHERE wm.user_id = ?
            ORDER BY wm.is_primary DESC, w.nome ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarWorkspaceDoUsuario(int $userId, int $workspaceId): ?array
    {
        $sql = '
            SELECT
                wm.workspace_id,
                wm.role,
                wm.is_primary,
                w.nome AS workspace_nome,
                w.slug AS workspace_slug,
                w.status AS workspace_status
            FROM workspace_members wm
            INNER JOIN workspaces w
              ON w.id = wm.workspace_id
            WHERE wm.user_id = ?
              AND wm.workspace_id = ?
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $workspaceId]);
        $workspace = $stmt->fetch(PDO::FETCH_ASSOC);

        return $workspace ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nome, email
                 , email_verificado_em
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ');
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findUserById(int $userId): ?array
    {
        $socialColumns = [];
        foreach (['instagram_url', 'behance_url', 'website_url'] as $column) {
            $socialColumns[] = $this->hasUserColumn($column) ? $column : "NULL AS {$column}";
        }

        $stmt = $this->pdo->prepare('
            SELECT id, nome, email, senha, foto_perfil, ' . implode(', ', $socialColumns) . '
            FROM usuarios
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function makeWorkspaceSlug(string $value): string
    {
        $slug = mb_strtolower(trim($value), 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        $slug = trim((string) $slug, '-');

        if ($slug === '') {
            $slug = 'workspace';
        }

        return $slug;
    }

    private function nextWorkspaceSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        $stmt = $this->pdo->prepare('SELECT 1 FROM workspaces WHERE slug = ? LIMIT 1');

        while (true) {
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }

    private function seedDefaultPipelineStages(int $workspaceId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO funil_estagios (workspace_id, nome, slug, ordem, cor_hex, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ');

        foreach (self::DEFAULT_PIPELINE_STAGES as $stage) {
            $stmt->execute([
                $workspaceId,
                $stage['nome'],
                $stage['slug'],
                $stage['ordem'],
                $stage['cor_hex'],
            ]);
        }
    }

    public function createUser(
        string $nome,
        string $email,
        string $senhaHash,
        ?string $workspaceNome = null
    ): array|false {
        $workspaceNome = trim((string) $workspaceNome);
        if ($workspaceNome === '') {
            $workspaceNome = $nome !== '' ? ('Workspace de ' . $nome) : 'Novo Workspace';
        }

        $workspaceSlug = $this->nextWorkspaceSlug($this->makeWorkspaceSlug($workspaceNome));

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO usuarios (nome, email, senha)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$nome, $email, $senhaHash]);
            $userId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                INSERT INTO workspaces (nome, slug, status)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$workspaceNome, $workspaceSlug, 'active']);
            $workspaceId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                INSERT INTO workspace_members (workspace_id, user_id, role, is_primary)
                VALUES (?, ?, ?, 1)
            ');
            $stmt->execute([$workspaceId, $userId, 'owner']);

            $this->seedDefaultPipelineStages($workspaceId);

            $stmt = $this->pdo->prepare('SELECT id FROM plans WHERE code = ? LIMIT 1');
            $stmt->execute(['free']);
            $planId = $stmt->fetchColumn();

            if ($planId) {
                $stmt = $this->pdo->prepare('
                    INSERT INTO subscriptions (workspace_id, plan_id, status, started_at, trial_ends_at)
                    VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))
                ');
                $stmt->execute([$workspaceId, (int) $planId, 'trial']);
            }

            $verification = $this->createEmailVerificationWithinTransaction($userId, $email);

            $this->pdo->commit();
            return [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'verification_token' => $verification['token'] ?? null,
                'verification_url' => $verification['verification_url'] ?? null,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function updateUser(
        int $userId,
        string $nome,
        string $email,
        ?string $senhaHash = null,
        ?string $fotoPath = null
    ): bool {
        $campos  = ['nome = ?', 'email = ?'];
        $params  = [$nome, $email];

        if ($senhaHash !== null) {
            $campos[] = 'senha = ?';
            $params[] = $senhaHash;
        }

        if ($fotoPath !== null) {
            $campos[] = 'foto_perfil = ?';
            $params[] = $fotoPath;
        }

        $campos[] = 'atualizado_em = NOW()';

        $sql      = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?';
        $params[] = $userId;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateUserPassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE usuarios
            SET senha = ?, atualizado_em = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([$passwordHash, $userId]);
    }

    public function updateUserAvatar(int $userId, string $avatarPath): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE usuarios
            SET foto_perfil = ?, atualizado_em = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([$avatarPath, $userId]);
    }

    public function updateSocialLink(int $userId, string $network, ?string $url): bool
    {
        $columns = [
            'instagram' => 'instagram_url',
            'behance' => 'behance_url',
            'website' => 'website_url',
        ];

        if (!isset($columns[$network])) {
            return false;
        }

        $column = $columns[$network];
        if (!$this->hasUserColumn($column)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET {$column} = ?, atualizado_em = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$url, $userId]);
    }


    public function updateUserModulePreferences(int $userId, array $modules): bool
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($module) => trim((string) $module),
            $modules
        ))));

        $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE usuarios
            SET sidebar_modules_json = ?,
                atualizado_em = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([$payload, $userId]);
    }

    public function updateUserPreferences(int $userId, string $theme, string $locale, string $timezone): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE usuarios
            SET preferred_theme = ?,
                preferred_locale = ?,
                preferred_timezone = ?,
                atualizado_em = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([
            $theme,
            $locale,
            $timezone,
            $userId,
        ]);
    }

    public function createPasswordReset(int $userId, string $email): ?array
    {
        $token = gerarTokenPublico(64);
        $expiresAt = $this->passwordResetExpiresAtUtc();

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                UPDATE password_resets
                SET used_at = NOW()
                WHERE user_id = ?
                  AND used_at IS NULL
            ");
            $stmt->execute([$userId]);

            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, email, token, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, trim($email), $token, $expiresAt]);

            $this->pdo->commit();

            return [
                'token' => $token,
                'reset_url' => fd_base_path() . '/redefinir-senha?token=' . urlencode($token),
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return null;
        }
    }

    private function createEmailVerificationWithinTransaction(int $userId, string $email): array
    {
        $token = gerarTokenPublico(64);
        $expiresAt = $this->emailVerificationExpiresAtUtc();

        $stmt = $this->pdo->prepare("
            UPDATE email_verifications
            SET used_at = NOW()
            WHERE user_id = ?
              AND used_at IS NULL
        ");
        $stmt->execute([$userId]);

        $stmt = $this->pdo->prepare("
            INSERT INTO email_verifications (user_id, email, token, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, trim($email), $token, $expiresAt]);

        return [
            'token' => $token,
            'verification_url' => fd_base_path() . '/confirmar-email?token=' . urlencode($token),
        ];
    }

    public function createEmailVerification(int $userId, string $email): ?array
    {
        $this->pdo->beginTransaction();

        try {
            $data = $this->createEmailVerificationWithinTransaction($userId, $email);
            $this->pdo->commit();

            return $data;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return null;
        }
    }

    public function findEmailVerificationByToken(string $token): ?array
    {
        $token = $this->normalizePublicToken($token);
        if ($token === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                ev.id,
                ev.user_id,
                ev.email,
                ev.token,
                ev.expires_at,
                ev.used_at,
                u.nome,
                u.email_verificado_em
            FROM email_verifications ev
            INNER JOIN usuarios u ON u.id = ev.user_id
            WHERE TRIM(ev.token) = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);

        return $verification ?: null;
    }

    public function emailVerificationStatus(string $token): string
    {
        $verification = $this->findEmailVerificationByToken($token);

        if (!$verification) {
            return 'not_found';
        }

        if (!empty($verification['used_at']) || !empty($verification['email_verificado_em'])) {
            return 'used';
        }

        if ($this->isEmailVerificationExpired($verification['expires_at'] ?? null)) {
            return 'expired';
        }

        return 'ok';
    }

    public function consumeEmailVerification(string $token): bool
    {
        $token = $this->normalizePublicToken($token);
        $verification = $this->findEmailVerificationByToken($token);
        if (!$verification) {
            return false;
        }

        if (!empty($verification['used_at']) || !empty($verification['email_verificado_em'])) {
            return false;
        }

        if ($this->isEmailVerificationExpired($verification['expires_at'] ?? null)) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET email_verificado_em = NOW(), atualizado_em = NOW() WHERE id = ?');
            $stmt->execute([(int) $verification['user_id']]);

            $stmt = $this->pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?');
            $stmt->execute([(int) $verification['id']]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    public function findPasswordResetByToken(string $token): ?array
    {
        $token = $this->normalizePublicToken($token);
        if ($token === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                pr.id,
                pr.user_id,
                pr.email,
                pr.token,
                pr.expires_at,
                pr.used_at,
                u.nome
            FROM password_resets pr
            INNER JOIN usuarios u ON u.id = pr.user_id
            WHERE TRIM(pr.token) = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reset ?: null;
    }

    public function consumePasswordReset(string $token, string $newPasswordHash): bool
    {
        $token = $this->normalizePublicToken($token);
        $reset = $this->findPasswordResetByToken($token);
        if (!$reset) {
            return false;
        }

        if (!empty($reset['used_at'])) {
            return false;
        }

        if ($this->isPasswordResetExpired($reset['expires_at'] ?? null)) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET senha = ?, atualizado_em = NOW() WHERE id = ?');
            $stmt->execute([$newPasswordHash, (int) $reset['user_id']]);

            $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
            $stmt->execute([(int) $reset['id']]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }

    public function passwordResetStatus(string $token): string
    {
        $reset = $this->findPasswordResetByToken($token);

        if (!$reset) {
            return 'not_found';
        }

        if (!empty($reset['used_at'])) {
            return 'used';
        }

        if ($this->isPasswordResetExpired($reset['expires_at'] ?? null)) {
            return 'expired';
        }

        return 'ok';
    }
}
