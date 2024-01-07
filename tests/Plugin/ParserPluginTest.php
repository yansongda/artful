<?php

namespace Yansongda\Artful\Tests\Plugin;

use GuzzleHttp\Psr7\Response;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\DirectionInterface;
use Yansongda\Artful\Contract\PackerInterface;
use Yansongda\Artful\Direction\CollectionDirection;
use Yansongda\Artful\Direction\NoHttpRequestDirection;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Plugin\ParserPlugin;
use Yansongda\Artful\Rocket;
use Yansongda\Artful\Tests\TestCase;
use Yansongda\Supports\Collection;

class ParserPluginTest extends TestCase
{
    protected ParserPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new ParserPlugin();

        Artful::set(DirectionInterface::class, CollectionDirection::class);
        Artful::set(PackerInterface::class, JsonPacker::class);
    }

    public function testDefaultDirection()
    {
        $rocket = new Rocket();
        $rocket->setDestination(new Response(200, [], '{"name":"yansongda"}'));

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertEquals(['name' => 'yansongda'], $result->getDestination()->all());
    }

    public function testDestinationError()
    {
        $rocket = new Rocket();
        $rocket->setDestination(new Collection());

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_PARSER_DIRECTION_INVALID);

        $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });
    }

    public function testNoHttpRequestDirection()
    {
        Artful::set(DirectionInterface::class, NoHttpRequestDirection::class);

        $rocket = new Rocket();

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame($rocket, $result);
    }

    public function testObjectDirection()
    {
        Artful::set(DirectionInterface::class, new NoHttpRequestDirection());

        $rocket = new Rocket();

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame($rocket, $result);
    }
}
