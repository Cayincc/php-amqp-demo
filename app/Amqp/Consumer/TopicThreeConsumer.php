<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Contract\StdoutLoggerInterface;

/**
 * @Consumer(exchange="hyperf_topic_exchange", routingKey="#", queue="hyperf_topic_queue_3", name ="TopicThreeConsumer", nums=1, enable=true)
 */
class TopicThreeConsumer extends ConsumerMessage
{
    public function consume($data): string
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info("hyperftopicqueuec3 消费:[{$data}]");
        return Result::ACK;
    }

    public function isEnable(): bool
    {
        return true;
    }
}
