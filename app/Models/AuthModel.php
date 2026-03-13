<?php
// app/Models/AuthModel.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

class AuthModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByLogin(string $userOrEmail): ?array
    {
        $sql = '
            SELECT id, nome, email, senha, plano, foto_perfil
            FROM usuarios
            WHERE email = ? OR nome = ?
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userOrEmail, $userOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function createUser(string $nome, string $email, string $senhaHash): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO usuarios (nome, email, senha)
            VALUES (?, ?, ?)
        ');
        return $stmt->execute([$nome, $email, $senhaHash]);
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
}
