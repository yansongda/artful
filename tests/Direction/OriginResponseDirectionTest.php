<?php

namespace Yansongda\Artful\Tests\Direction;

use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Direction\OriginResponseDirection;
use Yansongda\Artful\Tests\TestCase;

class OriginResponseDirectionTest extends TestCase
{
    protected OriginResponseDirection $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new OriginResponseDirection();
    }

    public function testResponseNull()
    {
        self::expectException(InvalidResponseException::class);
        self::expectExceptionCode(Exception::RESPONSE_EMPTY);

        $this->parser->guide(new JsonPacker(), null);
    }
}
