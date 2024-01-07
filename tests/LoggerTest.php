<?php

namespace Yansongda\Artful\Tests;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\EventDispatcherInterface;
use Yansongda\Artful\Contract\LoggerInterface;
use Yansongda\Artful\Event;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Logger;
use Yansongda\Supports\Collection;

class LoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artful::config([
            '_force' => true,
            'logger' => [
                'enable' => true,
            ],
        ]);
    }

    public function testNormal()
    {
        Logger::info('test');

        self::assertTrue(true);
    }

    public function testExternalNotPsr()
    {
        $client = new Collection(['timeout' => 3.0]);
        Artful::set(LoggerInterface::class, $client);

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_LOGGER_DRIVER_INVALID);

        Logger::info('test');
    }
}
