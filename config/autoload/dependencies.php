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

return [
    Hyperf\JsonRpc\JsonRpcTransporter::class => Hyperf\JsonRpc\JsonRpcPoolTransporter::class,
    App\JsonRpc\Contracts\Math\CalculatorServiceInterface::class => App\JsonRpc\Consumers\Math\CalculatorServiceConsumer::class,
    App\JsonRpc\Contracts\Comment\CommentServiceInterface::class => App\JsonRpc\Consumers\Comment\CommentServiceConsumer::class
];
