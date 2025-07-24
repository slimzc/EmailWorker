<?php
namespace SisProing\Queue;

use Redis;
use SisProing\Model\EmailJob;

class RedisEmailQueue
{
    private $redis;
    private $queueName;

    public function __construct(string $host, int $port, string $queueName)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
        $this->queueName = $queueName;
    }

    public function enqueue(EmailJob $job): void
    {
        $this->redis->rPush($this->queueName, json_encode($job));
    }

    public function popBatch(int $size): array
    {
        $jobs = [];
        for ($i = 0; $i < $size; $i++) {
            $payload = $this->redis->lPop($this->queueName);
            if (!$payload) {
                break;
            }
            $jobs[] = new EmailJob(json_decode($payload, true));
        }
        return $jobs;
    }
}
