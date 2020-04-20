<?php
declare(strict_types=1);

namespace App\JsonRpc\Services\Math;

use App\JsonRpc\Contracts\Math\CalculatorServiceInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Class CalculatorService
 * @RpcService(name="CalculatorService", protocol="jsonrpc", server="jsonrpc")
 */
class CalculatorService implements CalculatorServiceInterface {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('log',  'default');
    }

    public function add(float $a, float $b): float
    {
        $this->logger->info(sprintf('a = %s, b = %s', gettype($a), gettype($b)));
        return $a + $b;
    }
}