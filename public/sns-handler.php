<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Sns\SnsClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$awsConfig = [
    'region'  => $_ENV['AWS_REGION'],
    'version' => '2010-03-31',
    'credentials' => [
        'key' => $_ENV['AWS_KEY'],
        'secret' => $_ENV['AWS_SECRET'],
    ],
];

$client = new SnsClient($awsConfig);
$body = file_get_contents('php://input');
$messageType = $_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'] ?? '';
$message = json_decode($body, true);

if ($messageType === 'SubscriptionConfirmation' && isset($message['Token'], $message['TopicArn'])) {
    $client->confirmSubscription([
        'TopicArn' => $message['TopicArn'],
        'Token' => $message['Token'],
    ]);
    echo 'OK';
    return;
}

$pdo = new PDO(
    sprintf('pgsql:host=%s;port=%d;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']),
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

if (isset($message['notificationType'])) {
    $type = strtolower($message['notificationType']);
    if ($type === 'bounce') {
        foreach ($message['bounce']['bouncedRecipients'] as $recipient) {
            $stmt = $pdo->prepare('INSERT INTO email_suppressions (email, type) VALUES (:email, :type) ON CONFLICT (email) DO UPDATE SET type = :type, ts = now()');
            $stmt->execute(['email' => $recipient['emailAddress'], 'type' => 'bounce']);
        }
    } elseif ($type === 'complaint') {
        foreach ($message['complaint']['complainedRecipients'] as $recipient) {
            $stmt = $pdo->prepare('INSERT INTO email_suppressions (email, type) VALUES (:email, :type) ON CONFLICT (email) DO UPDATE SET type = :type, ts = now()');
            $stmt->execute(['email' => $recipient['emailAddress'], 'type' => 'complaint']);
        }
    }
}

echo 'OK';
