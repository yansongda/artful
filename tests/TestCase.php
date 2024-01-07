<?php

namespace Yansongda\Artful\Tests;

use Hyperf\Pimple\ContainerFactory;
use Hyperf\Context\ApplicationContext;
use Yansongda\Artful\Artful;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $config = [
            'yansongda' => [
                'default' => [
                    'client_id' => '123456',
                ],
            ],
        ];

        // hyperf 单测时，未在 hyperf 框架内，所以 sdk 没有 container, 手动设置一个
        if (class_exists(ApplicationContext::class) && class_exists(ContainerFactory::class)) {
            ApplicationContext::setContainer((new ContainerFactory())());
        }

        Artful::config($config);
    }

    protected function tearDown(): void
    {
        Artful::clear();
    }
}
