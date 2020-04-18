<?php

declare(strict_types=1);

namespace App\JsonRpc\Services\Comment;

use App\JsonRpc\Contracts\Comment\CommentServiceInterface;
use Hyperf\DbConnection\Db;
use Hyperf\RpcServer\Annotation\RpcService;
use Jmhc\Mongodb\MongoDbConnection;
use MongoDB\Collection;

/**
 * Class CommentService
 * @RpcService(name="CommentService", protocol="jsonrpc", server="jsonrpc")
 */
class CommentService implements CommentServiceInterface {

    /**
     * @var MongoDbConnection
     */
    protected $mongodb;

    public function __construct()
    {
        $this->mongodb = Db::connection('mongodb');
    }

    public function getList(int $page, int $perPage)
    {
        $filter = [];
        $option = [
            'sort' => [
                '_id' => 1
            ],
            'skip' => ($page - 1) * $perPage,
            'limit' => $perPage
        ];
        return $this->mongodb->getCollection('comment')->find($filter, $option)->toArray();
    }
}