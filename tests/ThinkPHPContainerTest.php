<?php

declare(strict_types=1);

namespace Yansongda\Artful\Tests;

use Closure;
use Hyperf\Context\ApplicationContext;
use ReflectionProperty;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Contract\ConfigInterface;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Contract\ShortcutInterface;
use Yansongda\Artful\Direction\NoHttpRequestDirection;
use Yansongda\Artful\Plugin\StartPlugin;
use Yansongda\Artful\Rocket;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;
use Yansongda\Supports\Pipeline;

/**
 * ThinkPHP 容器独立测试，需要安装 `topthink/think-container`（CI thinkphp 任务）.
 *
 * 注意：不能继承项目的 TestCase（其会做通用的 config 初始化），
 * 以便独立验证 thinkphp 场景下的容器检测与各个容器交互方法.
 */
class ThinkPHPContainerTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(\think\Container::class)) {
            self::markTestSkipped('未安装 `topthink/think-container`，跳过 ThinkPHP 容器测试');
        }
    }

    protected function setUp(): void
    {
        // 防御性清理可能被其它测试预置的 hyperf 容器上下文，避免抢占 thinkphp 检测
        if (class_exists(ApplicationContext::class) && ApplicationContext::hasContainer()) {
            $property = new ReflectionProperty(ApplicationContext::class, 'container');
            $property->setValue(null, null);
        }

        Artful::clear();
    }

    protected function tearDown(): void
    {
        Artful::clear();
    }

    public function testThinkPHPContainerDetected(): void
    {
        Artful::config(['name' => 'yansongda']);

        self::assertInstanceOf(\think\Container::class, Artful::getContainer());
    }

    public function testGetServiceSetAsScalar(): void
    {
        Artful::config();

        Artful::set('age', 28);

        self::assertEquals(28, Artful::get('age'));
    }

    public function testGetServiceSetAsObject(): void
    {
        Artful::config(['name' => 'yansongda']);

        $config = Artful::get(ConfigInterface::class);

        self::assertInstanceOf(Config::class, $config);
        self::assertEquals('yansongda', $config->get('name'));
    }

    public function testConfigWithExplicitContainer(): void
    {
        // 显式传容器路径不绑 PSR 接口，artful()/shortcut() 在该模式下不可用（与 laravel 既有行为一致），
        // 本测试仅验证 config/get 能力
        $container = \think\Container::getInstance();

        Artful::config(['name' => 'yansongda'], $container);

        self::assertSame($container, Artful::getContainer());
        self::assertEquals('yansongda', Artful::get(ConfigInterface::class)->get('name'));
    }

    public function testMakeServiceAlwaysFresh(): void
    {
        Artful::config();

        // think 的 make() 默认缓存实例，验证 Artful::make() 保持每次新建的语义
        self::assertNotSame(Artful::make(Pipeline::class), Artful::make(Pipeline::class));
    }

    public function testPipelineResolvesStringPlugins(): void
    {
        Artful::config();

        // Pipeline 通过 PSR-11 get() 解析字符串插件，验证 think 容器下的自动解析兜底
        $result = Artful::artful([StartPlugin::class, ThinkPHPFooPlugin::class], ['_config' => [], '_return_rocket' => true]);

        self::assertInstanceOf(Rocket::class, $result);
        self::assertEquals('bar', $result->getDestination()->get('foo'));
    }

    public function testShortcut(): void
    {
        Artful::config();

        $result = Artful::shortcut(ThinkPHPFooShortcut::class, ['_config' => []]);

        self::assertInstanceOf(Collection::class, $result);
        self::assertEquals('bar', $result->get('foo'));
    }
}

class ThinkPHPFooPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        $rocket->setDirection(NoHttpRequestDirection::class)
            ->setDestination(new Collection(['foo' => 'bar']));

        return $next($rocket);
    }
}

class ThinkPHPFooShortcut implements ShortcutInterface
{
    public function getPlugins(array $params): array
    {
        return [ThinkPHPFooPlugin::class];
    }
}
