<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SisProing\Helper\Mail;

$options = getopt('', ['to:', 'subject:', 'body:']);

if (!isset($options['to'], $options['subject'], $options['body'])) {
    echo "Usage: php enqueue.php --to=EMAIL --subject=SUBJECT --body=BODY\n";
    exit(1);
}

Mail::enqueue($options['to'], $options['subject'], $options['body']);

echo "Enqueued\n";
