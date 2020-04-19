<?php

namespace App\Utils;

use Hyperf\Amqp\Connection;
use Hyperf\Amqp\Pool\PoolFactory;
use Hyperf\Utils\ApplicationContext;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class AMQPConnection
 * @method static Connection getConnection(string $poolName = 'default');
 * @method static AMQPChannel getChannel(Connection $connection);
 */

class AMQPConnection {

    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;
    /**
     * @var PoolFactory;
     */
    protected $poolFactory;

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->poolFactory = $this->container->get(PoolFactory::class);
    }

    public static function __callStatic($name, $arguments)
    {
        return (new static)->$name(...$arguments);
    }

    private function getConnection(string $poolName = 'default'): Connection
    {
        $pool = $this->poolFactory->getPool($poolName);
        /** @var \Hyperf\Amqp\Connection $connection */
        $connection = $pool->get();

        return $connection;
    }

    private function getChannel(Connection $connection): AMQPChannel
    {
        return new AMQPChannel($connection->getConnection());
    }

}