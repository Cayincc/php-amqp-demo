<?php
declare(strict_types=1);
namespace App\JsonRpc\Consumers\Math;

use App\JsonRpc\Contracts\Math\CalculatorServiceInterface;
use Hyperf\RpcClient\AbstractServiceClient;

class CalculatorServiceConsumer extends AbstractServiceClient implements CalculatorServiceInterface {

    protected $serviceName = 'CalculatorService';

    protected $protocol = 'jsonrpc';

    /**
     * @param float $a
     * @param float $b
     * @return float
     */
    public function add(float $a, float $b): float
    {
        return $this->__request(__FUNCTION__, compact('a', 'b'));
    }
}