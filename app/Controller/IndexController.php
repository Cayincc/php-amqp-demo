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

use App\Constants\ErrorCode;
use App\Utils\AMQPConnection;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PhpAmqpLib\Channel\AMQPChannel;
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
     *P ---- Queue ---- C
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function simpleSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $connection = AMQPConnection::getConnection();
        $cid = \Hyperf\Utils\Coroutine::id();
        try {
            $channel = new AMQPChannel($connection->getConnection());
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }

        $message = new AMQPMessage('hello simple.'.$cid);

        $channel->queue_declare('test_simple_queue', false, false, false,false, false, [], null);

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
            $channel = new AMQPChannel($connection->getConnection());
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }


        $channel->queue_declare('test_work_queue', false, false, false,false, false, [], null);

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
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function workQueueFairSend(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $connection = AMQPConnection::getConnection();
        try {
            $channel = new AMQPChannel($connection->getConnection());
        } catch (\Exception $exception) {
            return $response->json([
                'code' => ErrorCode::SERVER_ERROR,
                'message' => $exception->getMessage()
            ]);
        }


        $channel->queue_declare('test_work_queue_fair', false, false, false,false, false, [], null);

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

}
