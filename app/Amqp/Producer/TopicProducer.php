<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

/**
 * @Producer(exchange="hyperf_topic_exchange")
 */
class TopicProducer extends ProducerMessage
{
    protected $routingKey;

    public function __construct($data)
    {
        $this->routingKey = $data['routingKey'];
        $this->payload = $data['payload'];
    }
}
