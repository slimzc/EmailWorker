<?php
use PHPUnit\Framework\TestCase;
use SisProing\Limiter\SesRateLimiter;
use SisProing\Logger\PostgresEmailLogger;
use Aws\Ses\SesClient;

class SesRateLimiterTest extends TestCase
{
    public function testEnforceMethods()
    {
        $client = $this->getMockBuilder(SesClient::class)
            ->addMethods(['getSendQuota'])
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('getSendQuota')->willReturn(new class {
            public function get($key) {
                return ['MaxSendRate' => 10, 'Max24HourSend' => 1000][$key];
            }
        });

        $logger = $this->createMock(PostgresEmailLogger::class);
        $logger->method('countSentSince')->willReturn(0);

        $limiter = new SesRateLimiter($client, $logger);
        $limiter->enforcePerSecond(5);
        $limiter->enforceDaily(5);
        $this->assertTrue(true); // if no exception
    }
}
