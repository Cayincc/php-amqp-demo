<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

/**
 * @Producer(exchange="hyperf", routingKey="hyperf")
 */
class SimpleProducer extends ProducerMessage
{
    protected $type = Type::DIRECT;

    public function __construct($data)
    {
        $this->payload = $data;
    }

}
