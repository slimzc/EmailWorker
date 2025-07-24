<?php
namespace SisProing\Model;

class EmailJob
{
    public $to;
    public $subject;
    public $body;
    public $headers;
    public $timestamp;

    public function __construct(array $data)
    {
        $this->to = $data['to'] ?? '';
        $this->subject = $data['subject'] ?? '';
        $this->body = $data['body'] ?? '';
        $this->headers = $data['headers'] ?? [];
        $this->timestamp = $data['timestamp'] ?? time();
    }
}
