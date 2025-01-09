<?php

namespace Yansongda\Artful\Tests;

use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\DirectionInterface;
use Yansongda\Artful\Contract\PackerInterface;
use Yansongda\Artful\Direction\CollectionDirection;
use Yansongda\Artful\Direction\NoHttpRequestDirection;
use Yansongda\Artful\Direction\ResponseDirection;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Rocket;
use Yansongda\Artful\Tests\Stubs\FooPackerStub;
use Yansongda\Supports\Collection;
use function Yansongda\Artful\filter_params;
use function Yansongda\Artful\get_direction;
use function Yansongda\Artful\get_packer;
use function Yansongda\Artful\get_radar_body;
use function Yansongda\Artful\get_radar_headers;
use function Yansongda\Artful\get_radar_method;
use function Yansongda\Artful\get_radar_url;
use function Yansongda\Artful\should_do_http_request;

class FunctionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artful::set(DirectionInterface::class, CollectionDirection::class);
        Artful::set(PackerInterface::class, JsonPacker::class);
    }

    public function testShouldDoHttpRequest()
    {
        $rocket = new Rocket();

        self::assertTrue(should_do_http_request($rocket->getDirection()));

        $rocket->setDirection(CollectionDirection::class);
        self::assertTrue(should_do_http_request($rocket->getDirection()));

        $rocket->setDirection(ResponseDirection::class);
        self::assertFalse(should_do_http_request($rocket->getDirection()));

        $rocket->setDirection(NoHttpRequestDirection::class);
        self::assertFalse(should_do_http_request($rocket->getDirection()));
    }

    public function testGetDirection()
    {
        self::assertInstanceOf(DirectionInterface::class, get_direction(DirectionInterface::class));

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_DIRECTION_INVALID);
        get_direction('invalid');
    }

    public function testPacker()
    {
        // default
        self::assertInstanceOf(JsonPacker::class, get_packer(PackerInterface::class));

        self::expectException(InvalidParamsException::class);
        self::expectExceptionCode(Exception::PARAMS_PACKER_INVALID);

        get_packer(FooPackerStub::class);
    }

    public function testFilterParams()
    {
        // none closure
        $params = [
            'name' => 'yansongda',
            '_method' => 'foo',
            'a' => null,
        ];
        self::assertEqualsCanonicalizing(['name' => 'yansongda'], filter_params($params)->all());

        // closure
        $params = [
            'name' => 'yansongda',
            '_method' => 'foo',
            'sign' => 'aaa',
            'sign_type' => 'bbb',
            's' => '',
            'a' => null,
        ];
        self::assertEqualsCanonicalizing(['name' => 'yansongda'], filter_params($params, fn ($k, $v) => '' !== $v && !is_null($v) && 'sign' != $k && 'sign_type' != $k)->all());
    }

    public function testGetRadarMethod()
    {
        self::assertNull(get_radar_method(null));
        self::assertNull(get_radar_method(new Collection()));
        self::assertEquals('GET', get_radar_method(new Collection(['_method' => 'get'])));
        self::assertEquals('POST', get_radar_method(new Collection(['_method' => 'post'])));
    }

    public function testGetRadarUrl()
    {
        self::assertNull(get_radar_url(null));
        self::assertNull(get_radar_url(new Collection()));
        self::assertEquals('https://yansongda.cn', get_radar_url(new Collection(['_url' => 'https://yansongda.cn'])));
    }

    public function testGetRadarBody()
    {
        self::assertNull(get_radar_body(new Collection([])));
        self::assertNull(get_radar_body(null));

        self::assertEquals('https://yansongda.cn', get_radar_body(new Collection(['_body' => 'https://yansongda.cn'])));
    }

    public function testGetRadarHeaders()
    {
        self::assertNull(get_radar_headers(new Collection([])));
        self::assertNull(get_radar_headers(null));

        self::assertEquals(['foo' => 'bar'], get_radar_headers(new Collection(['_headers' => ['foo' => 'bar']])));
    }
}
