<?php
namespace SisProing\Helper;

use DI\ContainerBuilder;
use SisProing\Model\EmailJob;
use SisProing\Queue\RedisEmailQueue;

class Mail
{
    private static $container;

    private static function getContainer(): \Psr\Container\ContainerInterface
    {
        if (!self::$container) {
            $builder = new ContainerBuilder();
            $config = require __DIR__ . '/../../config/config.php';
            $builder->addDefinitions([
                RedisEmailQueue::class => function () use ($config) {
                    return new RedisEmailQueue($config['redis']['host'], $config['redis']['port'], $config['redis']['queue']);
                },
            ]);
            self::$container = $builder->build();
        }
        return self::$container;
    }

    public static function enqueue(string $to, string $subject, string $body): void
    {
        $container = self::getContainer();
        /** @var RedisEmailQueue $queue */
        $queue = $container->get(RedisEmailQueue::class);
        $job = new EmailJob([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'headers' => [],
            'timestamp' => time(),
        ]);
        $queue->enqueue($job);
    }
}
