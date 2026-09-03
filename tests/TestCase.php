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

        // 仅 hyperf 矩阵（CI job env FRAMEWORK=hyperf）模拟 hyperf 环境有全局容器，结构化驱动 ContainerServiceProvider 探测命中 hyperfApplication() 分支；其余环境不预置注入——首个触发容器探测的测试经 defaultApplication() 拿到 pimple 容器（ContainerFactory 工厂副作用会写入 ApplicationContext，后续测试命中 hyperf 分支，行为分布与现状一致）；本地开发需安装 hyperf/pimple
        if (getenv('FRAMEWORK') === 'hyperf' && class_exists(ApplicationContext::class) && class_exists(ContainerFactory::class)) {
            ApplicationContext::setContainer((new ContainerFactory())());
        }

        Artful::config($config);
    }

    protected function tearDown(): void
    {
        Artful::clear();
    }
}
