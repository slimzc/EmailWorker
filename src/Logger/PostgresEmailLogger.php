<?php
namespace SisProing\Logger;

use PDO;
use DateTime;
use SisProing\Model\EmailJob;

class PostgresEmailLogger
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function logSent(EmailJob $job, string $messageId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO email_logs (to_addr, subject, body, headers, sent_at, status, error_msg) VALUES (:to, :subject, :body, :headers, now(), :status, NULL)');
        $stmt->execute([
            'to' => $job->to,
            'subject' => $job->subject,
            'body' => $job->body,
            'headers' => json_encode($job->headers),
            'status' => 'sent',
        ]);
    }

    public function logError(EmailJob $job, string $error): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO email_logs (to_addr, subject, body, headers, sent_at, status, error_msg) VALUES (:to, :subject, :body, :headers, now(), :status, :error)');
        $stmt->execute([
            'to' => $job->to,
            'subject' => $job->subject,
            'body' => $job->body,
            'headers' => json_encode($job->headers),
            'status' => 'failed',
            'error' => $error,
        ]);
    }

    public function logSuppressed(EmailJob $job): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO email_logs (to_addr, subject, body, headers, sent_at, status, error_msg) VALUES (:to, :subject, :body, :headers, now(), :status, NULL)');
        $stmt->execute([
            'to' => $job->to,
            'subject' => $job->subject,
            'body' => $job->body,
            'headers' => json_encode($job->headers),
            'status' => 'suppressed',
        ]);
    }

    public function countSentSince(DateTime $since): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM email_logs WHERE status = 'sent' AND sent_at >= :since");
        $stmt->execute(['since' => $since->format('Y-m-d H:i:s')]);
        return (int) $stmt->fetchColumn();
    }
}
