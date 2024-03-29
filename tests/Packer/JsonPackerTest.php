<?php

namespace Yansongda\Artful\Tests\Packer;

use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Tests\TestCase;
use Yansongda\Supports\Collection;

class JsonPackerTest extends TestCase
{
    protected JsonPacker $packer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packer = new JsonPacker();
    }

    public function testPack()
    {
        $array = ['name' => 'yansongda', 'age' => 29];
        $str = '{"name":"yansongda","age":29}';

        self::assertEquals($str, $this->packer->pack($array));
        self::assertEquals($str, $this->packer->pack(Collection::wrap($array)));
    }

    public function testUnpack()
    {
        $array = ['name' => 'yansongda', 'age' => 29];
        $str = '{"name":"yansongda","age":29}';

        self::assertEquals($array, $this->packer->unpack($str));
    }

    public function testPackEmpty()
    {
        $str = '';

        self::assertEquals($str, $this->packer->pack([]));
        self::assertEquals($str, $this->packer->pack(new Collection()));
    }
}
