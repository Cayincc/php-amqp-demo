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

use App\Constants\AMQPCode;
use App\Constants\ErrorCode;
use App\Utils\AMQPConnection;
use Hyperf\HttpServer\Contract\ResponseInterface;
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
}
