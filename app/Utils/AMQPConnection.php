<?php

namespace App\Utils;

use Hyperf\Amqp\Connection;
use Hyperf\Amqp\Pool\AmqpConnectionPool;
use Hyperf\Utils\ApplicationContext;

class AMQPConnection {

    public static function getConnection()
    {
        $config = [
            'host' => '192.168.10.10',
            'port' => 5672,
            'user' => 'homestead',
            'password' => 'secret',
            'vhost' => '/vhost_homestead',
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
            ],
            'params' => [
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => 3.0,
                'read_write_timeout' => 6.0,
                'context' => null,
                'keepalive' => false,
                'heartbeat' => 3,
            ],
        ];
        $container = ApplicationContext::getContainer();
        return (new Connection($container, new AmqpConnectionPool($container, 'default'), $config));
    }
}