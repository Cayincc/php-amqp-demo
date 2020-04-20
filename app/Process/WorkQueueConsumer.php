<?php

declare(strict_types=1);

namespace App\Process;

use App\Constants\AMQPCode;
use App\Utils\AMQPConnection;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Process(name="WorkQueueConsumer")
 */
class WorkQueueConsumer extends AbstractProcess
{
    public const QUEUE_NAME = 'test_work_queue';

    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info('workqueuec1 启动');

        $connection = AMQPConnection::getConnection();
        $channel = AMQPConnection::getChannel($connection);

        $channel->queue_declare(self::QUEUE_NAME, false, AMQPCode::DURABLE_TRUE, false,false, false, [], null);

        $channel->basic_consume(self::QUEUE_NAME, '', false, false, false, false, function (AMQPMessage $message) use ($logger) {
            sleep(1);
            $logger->info('workqueuec1 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null, []);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();

        $logger->info('workqueuec1 关闭');
    }

    public function isEnable(): bool
    {
        return true;
    }
}
