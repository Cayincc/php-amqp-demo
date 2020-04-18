<?php

declare(strict_types=1);

namespace App\Process;

use App\Utils\AMQPConnection;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Process(name="SimpleQueueConsumer")
 */
class SimpleQueueConsumer extends AbstractProcess
{
    public const QUEUE_NAME = 'test_simple_queue';

    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);

        $logger->info('simplequeuec 启动');

        $connection = AMQPConnection::getConnection();

        $channel = new AMQPChannel($connection->getConnection());

        $channel->queue_declare(self::QUEUE_NAME, false, false, false,false, false, [], null);

        $channel->basic_consume(self::QUEUE_NAME, '', false, false, false, false, function (AMQPMessage $message) use ($logger) {
            sleep(1);
            $logger->info('simplequeuec 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null, []);

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();

        $logger->info('simplequeuec 关闭');
    }

    public function isEnable(): bool
    {
        return true;
    }
}
