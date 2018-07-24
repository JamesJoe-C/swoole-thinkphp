<?php

//composer require xiaolin/swoole-rpc
use Lin\Swoole\Rpc\Client\Client;

/**
 * Class TestClient.
 *
 * @method test
 */
class TestClient extends Client
{
    protected $service = 'test';

    protected $host = '127.0.0.1';

    protected $port = 11520;
}

$result = TestClient::getInstance()->test();
