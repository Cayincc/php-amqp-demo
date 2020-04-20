<?php

declare(strict_types=1);

namespace App\Process;

use App\Utils\AMQPConnection;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Process(name="RpcQueueServer")
 */
class RpcQueueServer extends AbstractProcess
{

    public const QUEUE_NAME = 'test_rpc_queue';

    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->info('RpcQueueServer 启动');

        $connection = AMQPConnection::getConnection();
        $channel = AMQPConnection::getChannel($connection);

        $channel->queue_declare(self::QUEUE_NAME, false, false, false, false, false, [], null);

        //处理rpc请求并返回结果至回复队列
        $callback = function (AMQPMessage $message) use ($logger) {
            $num = (int)$message->body;
            $logger->info("call fib({$num})");

            $msg = new AMQPMessage((string)$this->fib($num), [
                'correlation_id' => $message->get('correlation_id')
            ]);

            //推送消息到回复队列
            $message->delivery_info['channel']->basic_publish($msg, '', $message->get('reply_to'));

            return $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        };

        $channel->basic_qos(null, 1, null);
        //监听请求队列，获取rpc请求
        $channel->basic_consume(self::QUEUE_NAME, '', false, false, false, false, $callback, null, []);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->release();
    }

    public function isEnable(): bool
    {
        return true;
    }

    public function fib(int $num): int
    {
        if ($num === 0) {
            return 0;
        }

        if ($num === 1) {
            return 1;
        }

        return $this->fib($num - 1) + $this->fib($num - 2);
    }
}
