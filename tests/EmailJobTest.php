<?php
use PHPUnit\Framework\TestCase;
use SisProing\Model\EmailJob;

class EmailJobTest extends TestCase
{
    public function testConstructor()
    {
        $data = [
            'to' => 'a@example.com',
            'subject' => 'Hello',
            'body' => 'Test',
            'headers' => ['from' => 'b@example.com'],
            'timestamp' => 123,
        ];
        $job = new EmailJob($data);
        $this->assertEquals('a@example.com', $job->to);
        $this->assertEquals('Hello', $job->subject);
        $this->assertEquals('Test', $job->body);
        $this->assertEquals(['from' => 'b@example.com'], $job->headers);
        $this->assertEquals(123, $job->timestamp);
    }
}
