<?php
namespace SisProing\Worker;

use SisProing\Queue\RedisEmailQueue;
use SisProing\Suppression\PostgresSuppressionList;
use SisProing\Sender\SesEmailSender;
use SisProing\Logger\PostgresEmailLogger;
use SisProing\Limiter\SesRateLimiter;
use SisProing\Model\EmailJob;

class EmailWorker
{
    private $queue;
    private $suppression;
    private $sender;
    private $logger;
    private $limiter;
    private $batchSize;

    public function __construct(
        RedisEmailQueue $queue,
        PostgresSuppressionList $suppression,
        SesEmailSender $sender,
        PostgresEmailLogger $logger,
        SesRateLimiter $limiter,
        int $batchSize
    ) {
        $this->queue = $queue;
        $this->suppression = $suppression;
        $this->sender = $sender;
        $this->logger = $logger;
        $this->limiter = $limiter;
        $this->batchSize = $batchSize;
    }

    public function run(): void
    {
        while (true) {
            $jobs = $this->queue->popBatch($this->batchSize);
            if (!$jobs) {
                sleep(1);
                continue;
            }

            $this->limiter->enforceDaily(count($jobs));
            $this->limiter->enforcePerSecond(count($jobs));

            $sendJobs = [];
            foreach ($jobs as $job) {
                if ($this->suppression->isSuppressed($job->to)) {
                    $this->logger->logSuppressed($job);
                    continue;
                }
                $sendJobs[] = $job;
            }

            if (!$sendJobs) {
                continue;
            }

            $results = $this->sender->sendBatch($sendJobs);
            foreach ($results as $idx => $result) {
                $job = $sendJobs[$idx];
                if ($result['success']) {
                    $this->logger->logSent($job, $result['messageId']);
                } else {
                    $this->logger->logError($job, $result['error']);
                }
            }
        }
    }
}
