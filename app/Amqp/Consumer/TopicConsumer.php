<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Contract\StdoutLoggerInterface;

/**
 * @Consumer(exchange="hyperf_topic_exchange", routingKey="*.orange.*", queue="hyperf_topic_queue_1", name ="TopicConsumer", nums=1, enable=true)
 */
class TopicConsumer extends ConsumerMessage
{
    public function consume($data): string
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info("hyperftopicqueuec1 消费:[{$data}]");
        return Result::ACK;
    }

    public function isEnable(): bool
    {
        return true;
    }
}
