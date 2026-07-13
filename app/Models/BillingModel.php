<?php

class BillingModel
{
    public function __construct(private PDO $pdo)
    {
    }

    private array $columnCache = [];

    private function canonicalPlanCatalog(): array
    {
        return [
            'free' => [
                'plan_nome' => 'Free',
                'plan_code' => 'free',
                'plan_preco' => 0.00,
                'users_limit' => 1,
                'clients_limit' => 2,
                'projects_limit' => 2,
                'orcamentos_limit' => 5,
                'storage_limit_mb' => 512,
            ],
            'starter' => [
                'plan_nome' => 'Starter',
                'plan_code' => 'starter',
                'plan_preco' => 9.90,
                'users_limit' => 3,
                'clients_limit' => 5,
                'projects_limit' => 5,
                'orcamentos_limit' => 15,
                'storage_limit_mb' => 2048,
            ],
            'pro' => [
                'plan_nome' => 'Pro',
                'plan_code' => 'pro',
                'plan_preco' => 49.90,
                'users_limit' => 10,
                'clients_limit' => 25,
                'projects_limit' => null,
                'orcamentos_limit' => null,
                'storage_limit_mb' => 4096,
            ],
            'enterprise' => [
                'plan_nome' => 'Enterprise',
                'plan_code' => 'enterprise',
                'plan_preco' => 209.90,
                'users_limit' => null,
                'clients_limit' => null,
                'projects_limit' => null,
                'orcamentos_limit' => null,
                'storage_limit_mb' => 20480,
            ],
        ];
    }

    private function applyCanonicalPlanDefaults(array $plan): array
    {
        $code = (string) ($plan['plan_code'] ?? $plan['code'] ?? '');
        $catalog = $this->canonicalPlanCatalog();
        if ($code === '' || !isset($catalog[$code])) {
            return $plan;
        }

        foreach ($catalog[$code] as $field => $value) {
            if (!array_key_exists($field, $plan) || $plan[$field] === null || $plan[$field] === '') {
                $plan[$field] = $value;
            }
        }

        if (!isset($plan['plan_nome']) && isset($plan['nome'])) {
            $plan['plan_nome'] = $plan['nome'];
        }
        if (!isset($plan['plan_code']) && isset($plan['code'])) {
            $plan['plan_code'] = $plan['code'];
        }
        if (!isset($plan['plan_preco']) && isset($plan['preco'])) {
            $plan['plan_preco'] = $plan['preco'];
        }

        return $plan;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $this->columnCache[$key] = (int) $stmt->fetchColumn() > 0;

        return $this->columnCache[$key];
    }

    private function planSelectFields(): string
    {
        $fields = [
            'p.id AS plan_id',
            'p.nome AS plan_nome',
            'p.code AS plan_code',
            'p.preco AS plan_preco',
            'p.users_limit',
            'p.clients_limit',
            'p.projects_limit',
            'p.storage_limit_mb',
        ];

        $optional = [
            'orcamentos_limit',
            'trial_days',
            'grace_days',
            'is_active',
            'sort_order',
            'premium_features_json',
        ];

        foreach ($optional as $column) {
            if ($this->hasColumn('plans', $column)) {
                $fields[] = 'p.' . $column;
            }
        }

        return implode(",\n                ", $fields);
    }

    public function getCurrentSubscription(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.workspace_id,
                s.plan_id,
                s.status,
                " . ($this->hasColumn('subscriptions', 'billing_cycle') ? "s.billing_cycle" : "'monthly' AS billing_cycle") . ",
                s.started_at,
                s.expires_at,
                s.trial_ends_at,
                {$this->planSelectFields()}
            FROM subscriptions s
            INNER JOIN plans p ON p.id = s.plan_id
            WHERE s.workspace_id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->execute([$workspaceId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($subscription === null) {
            return null;
        }

        $effectiveExpiry = $subscription['expires_at'] ?? $subscription['trial_ends_at'] ?? null;
        if (!empty($effectiveExpiry)) {
            $expiryDate = substr((string) $effectiveExpiry, 0, 10);
            $subscription['status'] = $expiryDate < date('Y-m-d') ? 'expired' : 'active';
        }

        $subscription = $this->applyCanonicalPlanDefaults($subscription);
        $subscription['premium_features'] = [];
        if (!empty($subscription['premium_features_json'])) {
            $decoded = json_decode((string) $subscription['premium_features_json'], true);
            if (is_array($decoded)) {
                $subscription['premium_features'] = array_values(array_filter($decoded, 'is_string'));
            }
        }

        return $subscription;
    }

    public function listPlans(): array
    {
        $where = $this->hasColumn('plans', 'is_active') ? 'WHERE p.is_active = 1' : '';
        $order = $this->hasColumn('plans', 'sort_order') ? 'ORDER BY p.sort_order ASC, p.preco ASC, p.id ASC' : 'ORDER BY p.preco ASC, p.id ASC';

        $stmt = $this->pdo->query("
            SELECT
                {$this->planSelectFields()}
            FROM plans p
            {$where}
            {$order}
        ");

        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($plans as &$plan) {
            $plan = $this->applyCanonicalPlanDefaults($plan);
            $plan['premium_features'] = [];
            if (!empty($plan['premium_features_json'])) {
                $decoded = json_decode((string) $plan['premium_features_json'], true);
                if (is_array($decoded)) {
                    $plan['premium_features'] = array_values(array_filter($decoded, 'is_string'));
                }
            }
        }
        unset($plan);

        return $plans;
    }

    public function getWorkspaceUsage(int $workspaceId): array
    {
        $tables = [
            'users' => ['table' => 'workspace_members', 'column' => 'workspace_id'],
            'clients' => ['table' => 'clientes', 'column' => 'workspace_id'],
            'projects' => ['table' => 'projetos', 'column' => 'workspace_id'],
            'orcamentos' => ['table' => 'orcamentos', 'column' => 'workspace_id'],
        ];

        $usage = [
            'users' => 0,
            'clients' => 0,
            'projects' => 0,
            'orcamentos' => 0,
            'storage_bytes' => 0,
            'storage_mb' => 0,
        ];

        foreach ($tables as $key => $meta) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$meta['table']} WHERE {$meta['column']} = ?");
            $stmt->execute([$workspaceId]);
            $usage[$key] = (int) $stmt->fetchColumn();
        }

        $usage['storage_bytes'] = $this->getWorkspaceStorageBytes($workspaceId);
        $usage['storage_mb'] = (int) ceil($usage['storage_bytes'] / 1024 / 1024);

        return $usage;
    }

    public function acquireWorkspaceBillingLock(int $workspaceId, int $timeoutSeconds = 5): bool
    {
        $lockName = 'flowdesk:billing:' . $workspaceId;
        $stmt = $this->pdo->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute([$lockName, max(1, $timeoutSeconds)]);

        return (int) $stmt->fetchColumn() === 1;
    }

    public function releaseWorkspaceBillingLock(int $workspaceId): void
    {
        $lockName = 'flowdesk:billing:' . $workspaceId;
        $stmt = $this->pdo->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->execute([$lockName]);
    }

    public function getLocalUploadSize(?string $path): int
    {
        $path = trim((string) $path);
        if ($path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return 0;
        }

        $relativePath = '/' . ltrim($path, '/');
        if (!str_starts_with($relativePath, '/uploads/')) {
            return 0;
        }

        $publicDir = realpath(__DIR__ . '/../../public');
        $uploadsDir = realpath(__DIR__ . '/../../public/uploads');
        $filePath = realpath(__DIR__ . '/../../public' . $relativePath);

        if (!$publicDir || !$uploadsDir || !$filePath) {
            return 0;
        }

        if (!str_starts_with($filePath, $uploadsDir) || !is_file($filePath)) {
            return 0;
        }

        return (int) filesize($filePath);
    }

    private function getWorkspaceStorageBytes(int $workspaceId): int
    {
        $paths = [];

        if ($this->hasColumn('clientes', 'foto_perfil')) {
            $stmt = $this->pdo->prepare("
                SELECT foto_perfil
                FROM clientes
                WHERE workspace_id = ?
                  AND foto_perfil IS NOT NULL
                  AND foto_perfil <> ''
            ");
            $stmt->execute([$workspaceId]);
            $paths = array_merge($paths, $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if ($this->hasColumn('codigos', 'preview_image')) {
            $stmt = $this->pdo->prepare("
                SELECT preview_image
                FROM codigos
                WHERE workspace_id = ?
                  AND preview_image IS NOT NULL
                  AND preview_image <> ''
            ");
            $stmt->execute([$workspaceId]);
            $paths = array_merge($paths, $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        $total = 0;
        foreach (array_unique(array_filter($paths, 'is_string')) as $path) {
            $total += $this->getLocalUploadSize($path);
        }

        return $total;
    }

    public function getStorageGate(int $workspaceId, int $incomingBytes, int $replacingBytes = 0): array
    {
        $snapshot = $this->getWorkspaceSnapshot($workspaceId);
        $storage = $snapshot['resources']['storage_mb'] ?? null;
        $limitMb = $storage['limit'] ?? null;
        $isUnlimited = (bool) ($storage['is_unlimited'] ?? true);
        $usedBytes = (int) ($snapshot['usage']['storage_bytes'] ?? 0);
        $nextBytes = max(0, $usedBytes - max(0, $replacingBytes)) + max(0, $incomingBytes);
        $limitBytes = $limitMb === null ? null : (int) $limitMb * 1024 * 1024;
        $allowed = $isUnlimited || $limitBytes === null || $nextBytes <= $limitBytes;
        $planName = $snapshot['subscription']['plan_nome'] ?? 'Plano atual';

        return [
            'allowed' => $allowed,
            'resource' => 'storage_mb',
            'label' => 'Armazenamento',
            'used_bytes' => $usedBytes,
            'incoming_bytes' => $incomingBytes,
            'replacing_bytes' => $replacingBytes,
            'next_bytes' => $nextBytes,
            'limit' => $limitMb,
            'limit_bytes' => $limitBytes,
            'plan_name' => $planName,
            'message' => $allowed ? '' : sprintf(
                'Armazenamento atingiu o limite de %s no plano %s.',
                $this->formatBytes((int) $limitBytes),
                $planName
            ),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / 1024 / 1024 / 1024, 1, ',', '.') . ' GB';
        }

        return number_format($bytes / 1024 / 1024, 0, ',', '.') . ' MB';
    }

    public function countPendingInvites(int $workspaceId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM workspace_invites
            WHERE workspace_id = ?
              AND status = 'pending'
              AND expires_at >= NOW()
        ");
        $stmt->execute([$workspaceId]);

        return (int) $stmt->fetchColumn();
    }

    public function getResourceGate(int $workspaceId, string $resource, int $increment = 1, int $extraUsed = 0): array
    {
        $snapshot = $this->getWorkspaceSnapshot($workspaceId);
        $resources = $snapshot['resources'] ?? [];
        $resourceData = $resources[$resource] ?? null;

        if ($resourceData === null) {
            return [
                'allowed' => true,
                'resource' => $resource,
                'label' => ucfirst($resource),
                'used' => null,
                'limit' => null,
                'next_total' => null,
                'message' => '',
            ];
        }

        $used = (int) ($resourceData['used'] ?? 0) + max(0, $extraUsed);
        $nextTotal = $used + max(1, $increment);
        $limit = $resourceData['limit'];
        $allowed = $resourceData['is_unlimited'] || $limit === null || $nextTotal <= (int) $limit;
        $planName = $snapshot['subscription']['plan_nome'] ?? 'Plano atual';

        $message = '';
        if (!$allowed) {
            $message = sprintf(
                '%s atingiu o limite de %d no plano %s.',
                $resourceData['label'],
                (int) $limit,
                $planName
            );
        }

        return [
            'allowed' => $allowed,
            'resource' => $resource,
            'label' => $resourceData['label'],
            'used' => $used,
            'limit' => $limit,
            'next_total' => $nextTotal,
            'message' => $message,
            'plan_name' => $planName,
        ];
    }

    public function getWorkspaceSnapshot(int $workspaceId): array
    {
        $subscription = $this->getCurrentSubscription($workspaceId);
        $plans = $this->listPlans();
        $usage = $this->getWorkspaceUsage($workspaceId);

        $limits = [
            'users' => $subscription['users_limit'] ?? null,
            'clients' => $subscription['clients_limit'] ?? null,
            'projects' => $subscription['projects_limit'] ?? null,
            'orcamentos' => $subscription['orcamentos_limit'] ?? null,
            'storage_mb' => $subscription['storage_limit_mb'] ?? null,
        ];

        $resources = [];
        $labels = [
            'users' => 'Usuarios',
            'clients' => 'Clientes',
            'projects' => 'Projetos',
            'orcamentos' => 'Orcamentos',
            'storage_mb' => 'Armazenamento',
        ];

        foreach ($limits as $key => $limit) {
            $used = $usage[$key] ?? null;
            $isUnlimited = $limit === null || $limit === '' || (int) $limit === 0;
            $limitValue = $isUnlimited ? null : (int) $limit;
            $usedValue = is_numeric($used) ? (int) $used : null;
            $ratio = ($limitValue && $usedValue !== null) ? min(100, (int) round(($usedValue / max(1, $limitValue)) * 100)) : null;

            $resources[$key] = [
                'label' => $labels[$key] ?? ucfirst($key),
                'used' => $usedValue,
                'limit' => $limitValue,
                'is_unlimited' => $isUnlimited,
                'ratio' => $ratio,
                'is_near_limit' => $ratio !== null && $ratio >= 80,
                'is_over_limit' => $limitValue !== null && $usedValue !== null && $usedValue >= $limitValue,
            ];
        }

        return [
            'subscription' => $subscription,
            'plans' => $plans,
            'usage' => $usage,
            'resources' => $resources,
        ];
    }
}
