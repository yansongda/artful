<?php

namespace Yansongda\Artful\Tests\Direction;

use GuzzleHttp\Psr7\Response;
use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Direction\NoHttpRequestDirection;
use Yansongda\Artful\Tests\TestCase;

class NoHttpRequestDirectionTest extends TestCase
{
    protected NoHttpRequestDirection $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new NoHttpRequestDirection();
    }

    public function testNormal()
    {
        $response = new Response(200, [], '{"name": "yansongda"}');

        $result = $this->parser->guide(new JsonPacker(), $response);

        self::assertSame($response, $result);
    }

    public function testNull()
    {
        $result = $this->parser->guide(new JsonPacker(), null);

        self::assertNull($result);
    }
}
