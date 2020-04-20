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
 * @Process(name="TopicQueueConsumer")
 */
class TopicQueueConsumer extends AbstractProcess
{
    public const EXCHANGE_NAME = 'test_topic_exchange';

    public const QUEUE_NAME = 'test_topic_queue_1';

    public const PREFETCH_COUNT = 1;

    public const ROUTING_KEY = [
        '*.orange.*'
    ];

    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);

        $logger->info('topicqueuec1 开启');

        $connection = AMQPConnection::getConnection();

        $channel = AMQPConnection::getChannel($connection);
        //声明topic类型交换机
        $channel->exchange_declare(self::EXCHANGE_NAME, AMQPCode::EXCHANGE_TOPIC, false, AMQPCode::DURABLE_TRUE, false, false, false, [], null);
        //声明队列
        $channel->queue_declare(self::QUEUE_NAME, false, AMQPCode::DURABLE_TRUE, false, false, false, [], null);
        //绑定队列和routing_key到交换机
        foreach (self::ROUTING_KEY as $routing_key) {
            $channel->queue_bind(self::QUEUE_NAME, self::EXCHANGE_NAME, $routing_key, false, [], null);
        }
        $channel->basic_qos(null, self::PREFETCH_COUNT, null);

        $channel->basic_consume(self::QUEUE_NAME, '', false, false, false, false, static function (AMQPMessage $message) use ($logger) {
            sleep(1);
            $logger->info('topicqueuec1 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();

        $logger->info('topicqueuec1 关闭');
    }

    public function isEnable(): bool
    {
        return true;
    }
}
