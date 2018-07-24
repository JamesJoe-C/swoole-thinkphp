<?php

//composer require limingxinleo/x-swoole-rpc

require __DIR__.'/../vendor/autoload.php';

use Xin\Swoole\Rpc\Handler\Handler;
use Xin\Swoole\Rpc\Server;

class TestHandler extends Handler
{
    public function test()
    {
        return 'success';
    }
}

$server = new Server();
$server->setHandler('test', TestHandler::class)->serve('0.0.0.0', '11520', [
    'pid_file' => './socket.pid',
    'daemonize' => false,
    'max_request' => 500, // 每个worker进程最大处理请求次数
    'open_eof_check' => true,
    'package_eof' => "\r\n",
]);
