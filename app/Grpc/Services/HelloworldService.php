<?php

declare(strict_types=1);

namespace App\Grpc\Services;

use Grpc\Helloworld\HelloworldRequest;
use Grpc\Helloworld\HelloworldResponse;

class HelloworldService
{
    public function sayHello(HelloworldRequest $request): HelloworldResponse
    {
        $message = new HelloworldResponse();
        $message->setMessage('Hello World');
        $message->setUser($request);

        return $message;
    }
}
