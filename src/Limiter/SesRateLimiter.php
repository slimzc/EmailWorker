<?php
namespace SisProing\Limiter;

use Aws\Ses\SesClient;
use DateTime;
use SisProing\Logger\PostgresEmailLogger;

class SesRateLimiter
{
    private $client;
    private $logger;

    public function __construct(SesClient $client, PostgresEmailLogger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function enforcePerSecond(int $batchSize): void
    {
        $quota = $this->client->getSendQuota();
        $rate = (int) $quota->get('MaxSendRate');
        if ($rate <= 0) {
            $rate = 1;
        }
        $delay = max(0, ($batchSize / $rate) - 1);
        if ($delay > 0) {
            usleep($delay * 1000000);
        }
    }

    public function enforceDaily(int $batchSize): void
    {
        $quota = $this->client->getSendQuota();
        $max = (int) $quota->get('Max24HourSend');
        $sent = $this->logger->countSentSince((new DateTime())->modify('-24 hours'));
        if (($sent + $batchSize) > $max) {
            $sleep = 60;
            sleep($sleep);
        }
    }
}
