<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Amqp\Producer\TopicProducer;
use App\Constants\AMQPCode;
use App\Constants\ErrorCode;
use App\Utils\AMQPConnection;
use Hyperf\Amqp\Producer;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\HttpServer\Contract\RequestInterface;

class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    /**
     * 简单队列
     * P ---- Queue ---- C
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function simpleSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $connection = AMQPConnection::getConnection();
        $cid = \Hyperf\Utils\Coroutine::id();
        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }

        $message = new AMQPMessage('hello simple.'.$cid);

        $channel->queue_declare('test_simple_queue', false, AMQPCode::DURABLE_TRUE, false,false, false, [], null);

        $channel->basic_publish($message, '', 'test_simple_queue');

        $channel->close();
        $connection->release();

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * 轮询分发 round-robin
     *                 |--C1
     * P --- Queue ----|
     *                 |--C2
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function workQueueSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        var_dump(\Hyperf\Utils\Coroutine::inCoroutine());
        $connection = AMQPConnection::getConnection();
        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }


        $channel->queue_declare('test_work_queue', false, AMQPCode::DURABLE_TRUE, false,false, false, [], null);

        for ($i = 0; $i < (int)$request->input('num', 20); $i++) {
//            $message = new AMQPMessage('hello workQueue.'.$i);
//            $channel->basic_publish($message, '', 'test_work_queue');
            go(function () use ($channel, $i) {
                $id = \Hyperf\Utils\Coroutine::id();
                $message = new AMQPMessage('hello workQueue.'.$i);
                $channel->basic_publish($message, '', 'test_work_queue');
            });
        }


        $channel->close();

        $connection->release();

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * 公平分发 fair-dispatch
     *
     *                 | prefetch=1
     *                 |------------C1
     * P --- Queue ----|
     *                 | prefetch=1
     *                 |------------C2
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function workQueueFairSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $connection = AMQPConnection::getConnection();
        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }


        $channel->queue_declare('test_work_queue_fair', false, AMQPCode::DURABLE_TRUE, false,false, false, [], null);

        for ($i = 0; $i < (int)$request->input('num', 20); $i++) {
//            $message = new AMQPMessage('hello workQueue.'.$i);
//            $channel->basic_publish($message, '', 'test_work_queue');
            go(function () use ($channel, $i) {
                $message = new AMQPMessage('hello workQueueFair.'.$i);
                $channel->basic_publish($message, '', 'test_work_queue_fair');
            });
        }


        $channel->close();

        $connection->release();

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * 发布订阅模式 Publish/Subscribe
     *
     *       (fanout)     |--- Queue --- C1
     * P --- Exchange ----|
     *                    |--- Queue --- C2
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function publishSubscribeQueueSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $connection = AMQPConnection::getConnection();
        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }

        //声明fanout类型交换机
        $channel->exchange_declare('test_publish_subscribe_exchange', AMQPCode::EXCHANGE_FANOUT, false, AMQPCode::DURABLE_TRUE, false,false, false, [], null);

        $message = new AMQPMessage('hello Publish/Subscribe.');
        $channel->basic_publish($message, 'test_publish_subscribe_exchange', '');
        
        $channel->close();
        $connection->release();

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * 路由模式 Routing
     *                      orange
     *                    | ------ Queue --- C1
     *       (direct)     |
     * P --- Exchange --- |
     *                    | black
     *                    | ------ |
     *                    | green  |
     *                    | ------ | --- Queue --- C2
     *                    | orange |
     *                    | ------ |
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function routingQueueSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $available_key = [
            'orange',
            'black',
            'green'
        ];

        if (!in_array($routing_key = (string)$request->input('routing_key'), $available_key)) {
            return $response->json([
                'code' => ErrorCode::UNPROCESSABLE_ENTITY,
                'message' => '无效的routing_key'
            ]);
        }
        $connection = AMQPConnection::getConnection();

        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }

        //声明direct类型交换机
        $channel->exchange_declare('test_routing_exchange', AMQPCode::EXCHANGE_DIRECT, false, AMQPCode::DURABLE_TRUE, false, false, false, [], null);

        $message = new AMQPMessage("hello routing.[{$routing_key}]");

        $channel->basic_publish($message, 'test_routing_exchange', $routing_key);

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * 主题模式 topic
     *
     *                     *.orange.*
     *                    | ------ Queue --- C1
     *       (topic)      |
     * P --- Exchange --- |
     *                    | *.*.rabbit
     *                    | ------ |
     *                    | lazy.# |
     *                    | ------ | --- Queue --- C2
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function topicQueueSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $routing_key = (string)$request->input('routing_key');
        if (!preg_match('/^\w+\.\w+\.\w+$/', $routing_key)) {
            return $response->json([
                'code' => ErrorCode::UNPROCESSABLE_ENTITY,
                'message' => '无效的routing_key'
            ]);
        }

        $connection = AMQPConnection::getConnection();

        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }
        //声明topic类型交换机
        $channel->exchange_declare('test_topic_exchange', AMQPCode::EXCHANGE_TOPIC, false, AMQPCode::DURABLE_TRUE, false, false, false, [], null);

        $message = new AMQPMessage("hello topic.[{$routing_key}]");

        $channel->basic_publish($message, 'test_topic_exchange', $routing_key);

        $channel->close();

        $connection->release();

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function hyperfTopicQueueSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $routing_key = (string)$request->input('routing_key');
        if (!preg_match('/^\w+\.\w+\.\w+$/', $routing_key)) {
            return $response->json([
                'code' => ErrorCode::UNPROCESSABLE_ENTITY,
                'message' => '无效的routing_key'
            ]);
        }

        $data = [
            'routingKey' => $routing_key,
            'payload'=> "hello hyperf topic.[{$routing_key}]"
        ];
        $message = new TopicProducer($data);
        $producer = ApplicationContext::getContainer()->get(Producer::class);

        if (!$producer->produce($message)) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => '发送失败'
            ]);
        }

        return $response->json([
            'code' => 200,
            'message' => '发送成功'
        ]);
    }

    /**
     * rpc模式 Request/reply pattern
     *
     *              request (rpc_queue)
     *            | ------> Queue     ---> |
     * client --->|                        | ---> server
     *            | reply   (reply_to)     |
     *            | <------ Queue     <--- |
     *
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function rpcQueueClient(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $num = $request->input('num', 0);

        $connection = AMQPConnection::getConnection();
        try {
            $channel = AMQPConnection::getChannel($connection);
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }
        //生成关联id
        $correlation_id = uniqid();
        $rep = null;

        //声明回复队列
        [$reply_queue, ,] = $channel->queue_declare('', false, false, true, false, false, [], null);

        //消费回复队列
        $channel->basic_consume($reply_queue, '', false, true, false, false, static function (AMQPMessage $message) use ($correlation_id, &$rep) {
            if ($message->get('correlation_id') === $correlation_id) {
                $rep = $message->body;
            }
        });

        $message = new AMQPMessage($num, [
            'correlation_id' => $correlation_id,
            'reply_to' => $reply_queue
        ]);
        //向请求队列发送消息
        $channel->basic_publish($message, '', 'test_rpc_queue');

        while (!$rep) {
            $channel->wait();
        }

        return $response->json([
            'code' => 200,
            'message' => "fib({$num}) = {$rep}"
        ]);
    }
}
