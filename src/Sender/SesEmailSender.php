<?php
namespace SisProing\Sender;

use Aws\Ses\SesClient;
use Aws\ResultInterface;
use Aws\Ses\Exception\SesException;
use Aws\CommandPool;
use SisProing\Model\EmailJob;

class SesEmailSender
{
    private $client;
    private $concurrency;

    public function __construct(SesClient $client, int $concurrency = 5)
    {
        $this->client = $client;
        $this->concurrency = $concurrency;
    }

    public function sendBatch(array $jobs): array
    {
        $commands = [];
        foreach ($jobs as $job) {
            $commands[] = $this->client->getCommand('SendEmail', [
                'Destination' => [
                    'ToAddresses' => [$job->to],
                ],
                'Message' => [
                    'Subject' => ['Data' => $job->subject],
                    'Body' => [
                        'Text' => ['Data' => $job->body],
                    ],
                ],
                'Source' => $job->headers['from'] ?? 'no-reply@example.com',
            ]);
        }

        $results = [];
        $pool = new CommandPool($this->client, $commands, [
            'concurrency' => $this->concurrency,
            'fulfilled' => function (ResultInterface $result, $idx) use (&$results, $jobs) {
                $results[$idx] = ['success' => true, 'messageId' => $result->get('MessageId')];
            },
            'rejected' => function (SesException $reason, $idx) use (&$results) {
                $results[$idx] = ['success' => false, 'error' => $reason->getMessage()];
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();

        return $results;
    }
}
