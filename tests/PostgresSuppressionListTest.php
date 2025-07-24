<?php
use PHPUnit\Framework\TestCase;
use SisProing\Suppression\PostgresSuppressionList;

class PostgresSuppressionListTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE email_suppressions (email TEXT PRIMARY KEY, type TEXT, ts TIMESTAMP)');
        $this->pdo->exec("INSERT INTO email_suppressions (email, type, ts) VALUES ('x@example.com', 'bounce', CURRENT_TIMESTAMP)");
    }

    public function testIsSuppressed()
    {
        $list = new PostgresSuppressionList($this->pdo);
        $this->assertTrue($list->isSuppressed('x@example.com'));
        $this->assertFalse($list->isSuppressed('y@example.com'));
    }
}
