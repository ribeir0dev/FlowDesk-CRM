<?php

class AdminAccountModel
{
    private array $columnCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ');
        $stmt->execute([$table, $column]);
        $this->columnCache[$key] = (int) $stmt->fetchColumn() > 0;

        return $this->columnCache[$key];
    }

    private function billingCycleSelect(): string
    {
        return $this->hasColumn('subscriptions', 'billing_cycle')
            ? "COALESCE(s.billing_cycle, 'monthly')"
            : "'monthly'";
    }

    public function listAccounts(): array
    {
        $billingCycle = $this->billingCycleSelect();
        $stmt = $this->pdo->query("
            SELECT
                w.id AS account_id,
                w.nome AS workspace_nome,
                u.nome,
                u.email,
                s.id AS subscription_id,
                s.plan_id,
                p.nome AS plano_nome,
                p.code AS plano_code,
                {$billingCycle} AS billing_cycle,
                s.expires_at,
                CASE
                    WHEN s.id IS NULL THEN 'sem_assinatura'
                    WHEN s.expires_at IS NOT NULL AND DATE(s.expires_at) < CURRENT_DATE THEN 'expired'
                    ELSE 'active'
                END AS status
            FROM workspaces w
            LEFT JOIN workspace_members wm
              ON wm.id = (
                    SELECT wm2.id
                    FROM workspace_members wm2
                    WHERE wm2.workspace_id = w.id
                      AND wm2.role = 'owner'
                    ORDER BY wm2.is_primary DESC, wm2.id ASC
                    LIMIT 1
                 )
            LEFT JOIN usuarios u ON u.id = wm.user_id
            LEFT JOIN subscriptions s
              ON s.id = (
                    SELECT s2.id
                    FROM subscriptions s2
                    WHERE s2.workspace_id = w.id
                    ORDER BY s2.id DESC
                    LIMIT 1
                 )
            LEFT JOIN plans p ON p.id = s.plan_id
            ORDER BY w.id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAccount(int $workspaceId): ?array
    {
        $billingCycle = $this->billingCycleSelect();
        $stmt = $this->pdo->prepare("
            SELECT
                w.id AS account_id,
                w.nome AS workspace_nome,
                u.nome,
                u.email,
                s.id AS subscription_id,
                s.plan_id,
                p.nome AS plano_nome,
                p.code AS plano_code,
                {$billingCycle} AS billing_cycle,
                s.expires_at,
                CASE
                    WHEN s.id IS NULL THEN 'sem_assinatura'
                    WHEN s.expires_at IS NOT NULL AND DATE(s.expires_at) < CURRENT_DATE THEN 'expired'
                    ELSE 'active'
                END AS status
            FROM workspaces w
            LEFT JOIN workspace_members wm
              ON wm.id = (
                    SELECT wm2.id
                    FROM workspace_members wm2
                    WHERE wm2.workspace_id = w.id
                      AND wm2.role = 'owner'
                    ORDER BY wm2.is_primary DESC, wm2.id ASC
                    LIMIT 1
                 )
            LEFT JOIN usuarios u ON u.id = wm.user_id
            LEFT JOIN subscriptions s
              ON s.id = (
                    SELECT s2.id
                    FROM subscriptions s2
                    WHERE s2.workspace_id = w.id
                    ORDER BY s2.id DESC
                    LIMIT 1
                 )
            LEFT JOIN plans p ON p.id = s.plan_id
            WHERE w.id = ?
            LIMIT 1
        ");
        $stmt->execute([$workspaceId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    public function listPlans(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, nome, code, preco
            FROM plans
            ORDER BY preco ASC, id ASC
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateSubscription(int $workspaceId, int $planId, string $billingCycle, string $expiresAt): bool
    {
        if (!in_array($billingCycle, ['monthly', 'annual'], true)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresAt);
        if (!$date || $date->format('Y-m-d') !== $expiresAt) {
            return false;
        }

        $status = $expiresAt >= date('Y-m-d') ? 'active' : 'expired';
        $expiresAtSql = $expiresAt . ' 23:59:59';

        $this->pdo->beginTransaction();

        try {
            $workspace = $this->pdo->prepare('SELECT id FROM workspaces WHERE id = ? FOR UPDATE');
            $workspace->execute([$workspaceId]);
            if (!$workspace->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $plan = $this->pdo->prepare('SELECT id FROM plans WHERE id = ? LIMIT 1');
            $plan->execute([$planId]);
            if (!$plan->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            $subscription = $this->pdo->prepare('
                SELECT id
                FROM subscriptions
                WHERE workspace_id = ?
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ');
            $subscription->execute([$workspaceId]);
            $subscriptionId = (int) $subscription->fetchColumn();
            $hasBillingCycle = $this->hasColumn('subscriptions', 'billing_cycle');

            if ($subscriptionId > 0) {
                $sql = $hasBillingCycle
                    ? 'UPDATE subscriptions
                       SET plan_id = ?, status = ?, billing_cycle = ?, expires_at = ?, trial_ends_at = NULL, updated_at = NOW()
                       WHERE id = ?'
                    : 'UPDATE subscriptions
                       SET plan_id = ?, status = ?, expires_at = ?, trial_ends_at = NULL, updated_at = NOW()
                       WHERE id = ?';
                $params = $hasBillingCycle
                    ? [$planId, $status, $billingCycle, $expiresAtSql, $subscriptionId]
                    : [$planId, $status, $expiresAtSql, $subscriptionId];

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = $hasBillingCycle
                    ? 'INSERT INTO subscriptions
                         (workspace_id, plan_id, status, billing_cycle, started_at, expires_at, trial_ends_at)
                       VALUES (?, ?, ?, ?, NOW(), ?, NULL)'
                    : 'INSERT INTO subscriptions
                         (workspace_id, plan_id, status, started_at, expires_at, trial_ends_at)
                       VALUES (?, ?, ?, NOW(), ?, NULL)';
                $params = $hasBillingCycle
                    ? [$workspaceId, $planId, $status, $billingCycle, $expiresAtSql]
                    : [$workspaceId, $planId, $status, $expiresAtSql];

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('[FlowDesk][AdminPlan] ' . $exception->getMessage());
            return false;
        }
    }
}

