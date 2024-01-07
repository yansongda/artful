<?php

namespace Yansongda\Artful\Tests;

use GuzzleHttp\Client;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\HttpClientInterface;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\HttpClientFactory;
use Yansongda\Supports\Collection;

class HttpClientFactoryTest extends TestCase
{
    protected HttpClientFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new HttpClientFactory(Artful::getContainer());
    }

    public function testNormal()
    {
        $result = $this->factory->create();

        self::assertInstanceOf(Client::class, $result);
    }

    public function testExternalClient()
    {
        $client = new Client(['timeout' => 3.0]);
        Artful::set(HttpClientInterface::class, $client);

        self::assertEquals($client, $this->factory->create());
    }

    public function testExternalClientNotPsr()
    {
        $client = new Collection(['timeout' => 3.0]);
        Artful::set(HttpClientInterface::class, $client);

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_HTTP_CLIENT_INVALID);

        $this->factory->create();
    }
}
