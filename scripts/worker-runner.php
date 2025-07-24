<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use SisProing\Queue\RedisEmailQueue;
use SisProing\Suppression\PostgresSuppressionList;
use SisProing\Logger\PostgresEmailLogger;
use SisProing\Sender\SesEmailSender;
use SisProing\Limiter\SesRateLimiter;
use SisProing\Worker\EmailWorker;
use Aws\Ses\SesClient;

$config = require __DIR__ . '/../config/config.php';

$builder = new ContainerBuilder();
$builder->addDefinitions([
    'config' => $config,
    PDO::class => function () use ($config) {
        $db = $config['db'];
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $db['host'], $db['port'], $db['name']);
        return new PDO($dsn, $db['user'], $db['pass']);
    },
    SesClient::class => function () use ($config) {
        return new SesClient([
            'region' => $config['aws']['region'],
            'version' => '2010-12-01',
            'credentials' => [
                'key' => $config['aws']['key'],
                'secret' => $config['aws']['secret'],
            ],
        ]);
    },
    RedisEmailQueue::class => function () use ($config) {
        return new RedisEmailQueue($config['redis']['host'], $config['redis']['port'], $config['redis']['queue']);
    },
    PostgresSuppressionList::class => DI\autowire()->constructorParameter('pdo', DI\get(PDO::class)),
    PostgresEmailLogger::class => DI\autowire()->constructorParameter('pdo', DI\get(PDO::class)),
    SesEmailSender::class => DI\autowire()->constructorParameter('client', DI\get(SesClient::class)),
    SesRateLimiter::class => DI\autowire(),
    EmailWorker::class => function ($c) {
        return new EmailWorker(
            $c->get(RedisEmailQueue::class),
            $c->get(PostgresSuppressionList::class),
            $c->get(SesEmailSender::class),
            $c->get(PostgresEmailLogger::class),
            $c->get(SesRateLimiter::class),
            10
        );
    },
]);

$container = $builder->build();
$worker = $container->get(EmailWorker::class);
$worker->run();
