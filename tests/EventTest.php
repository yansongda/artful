<?php

namespace Yansongda\Artful\Tests;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\EventDispatcherInterface;
use Yansongda\Artful\Event;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Supports\Collection;

class EventTest extends TestCase
{
    protected EventDispatcher $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new EventDispatcher();
    }

    public function testNormal()
    {
        Event::dispatch(new Event\HttpStart());

        self::assertTrue(true);
    }

    public function testExternalNotPsr()
    {
        $client = new Collection(['timeout' => 3.0]);
        Artful::set(EventDispatcherInterface::class, $client);

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_EVENT_DRIVER_INVALID);

        Event::dispatch(new Event\HttpStart());
    }

    public function testContainerNotExist()
    {
        Artful::clear();

        Event::dispatch(new Event\HttpStart());

        self::assertTrue(true);
    }
}
