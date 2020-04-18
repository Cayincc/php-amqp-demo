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
 * @Process(name="WorkQueueFairTwoConsumer")
 */
class WorkQueueFairTwoConsumer extends AbstractProcess
{
    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info('workqueuefairc2 启动');
        $connection = AMQPConnection::getConnection();

        $channel = new AMQPChannel($connection->getConnection());

        $channel->queue_declare('test_work_queue_fair', false, false, false, false, false, [], null);

        //每个消费者发送确认消息之前，消息队列不发送下一个消息到消费者，一次只处理一个消息
        //限制发送同一个消费者不得超过一条消息
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume('test_work_queue_fair', '', false, false, false, false, function (AMQPMessage $message) use ($logger) {
            sleep(2);
            $logger->info('workqueuefairc2 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null, []);

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();

        $logger->info('workqueuefairc2 关闭');
    }

    public function isEnable(): bool
    {
        return false;
    }
}
