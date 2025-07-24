<?php
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'aws' => [
        'key'    => $_ENV['AWS_KEY'] ?? null,
        'secret' => $_ENV['AWS_SECRET'] ?? null,
        'region' => $_ENV['AWS_REGION'] ?? 'us-east-1',
    ],
    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
        'queue' => $_ENV['QUEUE_NAME'] ?? 'email_queue',
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 5432),
        'name' => $_ENV['DB_NAME'] ?? 'email_worker',
        'user' => $_ENV['DB_USER'] ?? 'user',
        'pass' => $_ENV['DB_PASS'] ?? 'pass',
    ],
];
