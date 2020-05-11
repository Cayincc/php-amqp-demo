<?php

namespace App\Grpc\Clients;

use Grpc\Helloworld\HelloworldRequest;
use Grpc\Helloworld\HelloworldResponse;
use Hyperf\GrpcClient\BaseClient;

class HelloworldClient extends BaseClient {
    public function sayHello(HelloworldRequest $argument)
    {
        return $this->simpleRequest(
            '/grpc.helloworld/sayHello',
            $argument,
            [HelloworldResponse::class, 'decode']
        );
    }
}