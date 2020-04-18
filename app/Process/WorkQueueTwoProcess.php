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
 * @Process(name="WorkQueueTwoProcess")
 */
class WorkQueueTwoProcess extends AbstractProcess
{
    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info('workqueuec2 启动');
        $connection = AMQPConnection::getConnection();

        try {
            $channel = new AMQPChannel($connection->getConnection());
        } catch (\Exception $exception) {
            var_dump($exception);
        }

        $channel->queue_declare('test_work_queue', false, false, false,false, false, [], null);

        $channel->basic_consume('test_work_queue', '', false, false, false, false, function (AMQPMessage $message) use ($logger) {
            sleep(1);
            $logger->info('workqueuec2 消费:'.$message->body.'/'.$message->delivery_info['delivery_tag']);
            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }, null, []);

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }
        
        $channel->close();
        $connection->release();

        $logger->info('workqueuec2 关闭');
    }

    public function isEnable(): bool
    {
        return false;
    }
}
