<?php
namespace SisProing\Suppression;

use PDO;

class PostgresSuppressionList
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function isSuppressed(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM email_suppressions WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetchColumn();
    }
}
