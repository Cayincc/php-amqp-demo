<?php

declare(strict_types=1);

namespace App\Controller;

use App\JsonRpc\Contracts\Comment\CommentServiceInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;

class CommentController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('perPage', 2);

        $commentClient = ApplicationContext::getContainer()->get(CommentServiceInterface::class);

        $list = $commentClient->getList($page, $perPage);

        return $response->json([
            'data' => $list,
        ]);
    }
}
