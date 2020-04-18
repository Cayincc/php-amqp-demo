<?php

declare(strict_types=1);

namespace App\JsonRpc\Consumers\Comment;

use App\JsonRpc\Contracts\Comment\CommentServiceInterface;
use Hyperf\RpcClient\AbstractServiceClient;

class CommentServiceConsumer extends AbstractServiceClient implements CommentServiceInterface {

    protected $serviceName = 'CommentService';

    protected $protocol = 'jsonrpc';

    public function getList(int $page, int $perPage)
    {
        return $this->__request(__FUNCTION__, compact('page', 'perPage'));
    }
}