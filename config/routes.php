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

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::get('/comment', 'App\Controller\CommentController@index');

Router::get('/simplesend', 'App\Controller\IndexController@simpleSend');

Router::get('/workqueuesend', 'App\Controller\IndexController@workQueueSend');

Router::get('/workqueuefairsend', 'App\Controller\IndexController@workQueueFairSend');

Router::get('/publishSubscribeQueueSend', 'App\Controller\IndexController@publishSubscribeQueueSend');

Router::get('/routingqueuesend', 'App\Controller\IndexController@routingQueueSend');

Router::get('/topicqueuesend', 'App\Controller\IndexController@topicQueueSend');

Router::get('/hyperftopicqueuesend', 'App\Controller\IndexController@hyperfTopicQueueSend');

Router::get('/rpcqueueclient', 'App\Controller\IndexController@rpcQueueClient');

Router::get('/txsend', 'App\Controller\IndexController@txSend');

Router::get('/confirmsend', 'App\Controller\IndexController@confirmSend');

Router::get('/confirmmandatorysend', 'App\Controller\IndexController@confirmMandatorySend');

Router::get('/hello', 'App\Controller\IndexController@hello');

//grpc 服务
Router::addServer('grpc', static function () {
    Router::addGroup('/grpc.helloworld', function () {
        Router::post('/sayHello', 'App\Grpc\Services\HelloworldService@sayHello');
    });
});