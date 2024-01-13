<?php

namespace Yansongda\Artful\Tests\Plugin;

use Yansongda\Artful\Plugin\StartPlugin;
use Yansongda\Artful\Rocket;
use Yansongda\Artful\Tests\TestCase;

class StartPluginTest extends TestCase
{
    protected StartPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new StartPlugin();
    }

    public function testDefaultDirection()
    {
        $rocket = new Rocket();
        $rocket->setParams([
            'name' => 'yansongda',
            '_t' => 'test',
        ]);

        $result = $this->plugin->assembly($rocket, function ($rocket) { return $rocket; });

        self::assertEquals(['name' => 'yansongda', '_t' => 'test'], $result->getPayload()->all());
    }
}
