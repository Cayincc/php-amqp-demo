<?php

declare(strict_types=1);

namespace App\Process;

use App\Utils\AMQPConnection;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Process(name="WorkQueueFairConsumer")
 */
class WorkQueueFairConsumer extends AbstractProcess
{
    public const QUEUE_NAME = 'test_work_queue_fair';

    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info('workqueuefairc1 启动');

        $connection = AMQPConnection::getConnection();

        $channel = new AMQPChannel($connection->getConnection());

        $channel->queue_declare(self::QUEUE_NAME, false, false, false, false, false, [], null);

        //每个消费者发送确认消息之前，消息队列不发送下一个消息到消费者，一次只处理一个消息
        //限制发送同一个消费者不得超过一条消息
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(self::QUEUE_NAME, '', false, false, false, false,static function (AMQPMessage $message) use ($logger) {
            sleep(2);
            $logger->info('workqueuefairc1 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null, []);

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();

        $logger->info('workqueuefairc1 关闭');
    }

    public function isEnable(): bool
    {
        return false;
    }
}
