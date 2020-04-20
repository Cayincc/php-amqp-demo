<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Contract\StdoutLoggerInterface;

/**
 * @Consumer(exchange="hyperf_topic_exchange", queue="hyperf_topic_queue_2", name ="TopicTwoConsumer", nums=1, enable=true)
 */
class TopicTwoConsumer extends ConsumerMessage
{
    protected $routingKey = [
        '*.*.rabbit',
        'lazy.#'
    ];
    public function consume($data): string
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info("hyperftopicqueuec2 消费:[{$data}]");
        return Result::ACK;
    }

    public function isEnable(): bool
    {
        return true;
    }
}
