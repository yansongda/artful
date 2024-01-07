<?php

namespace Yansongda\Artful\Tests\Plugin;

use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Packer\QueryPacker;
use Yansongda\Artful\Packer\XmlPacker;
use Yansongda\Artful\Plugin\AddPayloadBodyPlugin;
use Yansongda\Artful\Rocket;
use Yansongda\Artful\Tests\TestCase;
use Yansongda\Supports\Collection;

class AddPayloadBodyPluginTest extends TestCase
{
    protected AddPayloadBodyPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new AddPayloadBodyPlugin();
    }

    public function testQueryPackerNormal()
    {
        $payload = new Collection([
            "name" => "yansongda",
            'age' => 30,
        ]);

        $rocket = new Rocket();
        $rocket->setPacker(QueryPacker::class)->setPayload($payload);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame($payload->query(), $result->getPayload()->get('_body'));
    }

    public function testQueryPackerUnderlineAndNull()
    {
        $payload = new Collection([
            "name" => "yansongda",
            '_age' => 30,
            'aaa' => null,
        ]);

        $rocket = new Rocket();
        $rocket->setPacker(QueryPacker::class)->setPayload($payload);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        unset($payload['_age'], $payload['aaa']);

        self::assertSame($payload->except('_age')->query(), $result->getPayload()->get('_body'));
    }

    public function testQueryPackerEmpty()
    {
        $payload = new Collection([
            '_age' => '30',
        ]);

        $rocket = new Rocket();
        $rocket->setPacker(QueryPacker::class)->setPayload($payload);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame('', $result->getPayload()->get('_body'));
    }

    public function testJsonPackerNormal()
    {
        $payload = new Collection([
            "name" => "yansongda",
            'age' => 30,
            '_a' => 'a',
            'b' => null,
        ]);

        $rocket = new Rocket();
        $rocket->setPacker(JsonPacker::class)->setPayload($payload);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame($payload->except(['_a', 'b'])->toJson(), $result->getPayload()->get('_body'));
    }

    public function testXmlPackerNormal()
    {
        $payload = new Collection([
            "name" => "yansongda",
            'age' => 30,
            '_a' => 'a',
            'b' => null,
        ]);

        $rocket = new Rocket();
        $rocket->setPacker(XmlPacker::class)->setPayload($payload);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertSame($payload->except(['_a', 'b'])->toXml(), $result->getPayload()->get('_body'));
    }
}
