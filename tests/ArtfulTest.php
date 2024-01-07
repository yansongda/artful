<?php

namespace Yansongda\Artful\Tests;

use GuzzleHttp\Client;
use Hyperf\Pimple\ContainerFactory;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Yansongda\Artful\Contract\ConfigInterface;
use Yansongda\Artful\Contract\EventDispatcherInterface;
use Yansongda\Artful\Contract\HttpClientFactoryInterface;
use Yansongda\Artful\Contract\HttpClientInterface;
use Yansongda\Artful\Contract\LoggerInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Artful;
use Yansongda\Artful\HttpClientFactory;
use Yansongda\Artful\Tests\Stubs\FooServiceProviderStub;
use Yansongda\Supports\Config;
use Yansongda\Supports\Pipeline;

class ArtfulTest extends TestCase
{
    protected function setUp(): void
    {
        Artful::clear();
    }

    protected function tearDown(): void
    {
        Artful::clear();
    }

    public function testMagicCallNotFoundService()
    {
        self::expectException(ServiceNotFoundException::class);

        Artful::foo1([]);
    }

    public function testConfig()
    {
        $result = Artful::config(['name' => 'yansongda']);
        self::assertTrue($result);
        self::assertEquals('yansongda', Artful::get(ConfigInterface::class)->get('name'));

        // force
        $result1 = Artful::config(['name' => 'yansongda1', '_force' => true]);
        self::assertTrue($result1);
        self::assertEquals('yansongda1', Artful::get(ConfigInterface::class)->get('name'));

        // 直接使用 config 去设置 container
        if (class_exists(ContainerFactory::class)) {
            // container - closure
            Artful::clear();
            $container2 = (new ContainerFactory())();
            $result2 = Artful::config(['name' => 'yansongda2'], function () use ($container2) {
                return $container2;
            });
            self::assertTrue($result2);
            self::assertSame($container2, Artful::getContainer());

            // container - object
            Artful::clear();
            $container3 = (new ContainerFactory())();
            $result3 = Artful::config(['name' => 'yansongda2'], $container3);
            self::assertTrue($result3);
            self::assertSame($container3, Artful::getContainer());

            // container - object force
            Artful::clear();
            $container4 = (new ContainerFactory())();
            Artful::setContainer($container4);
            $result4 = Artful::config(['name' => 'yansongda2', '_force' => true]);
            self::assertTrue($result4);
            self::assertSame($container4, Artful::getContainer());
        }
    }

    public function testSetAndGet()
    {
        Artful::config(['name' => 'yansongda']);

        Artful::set('age', 28);

        self::assertEquals(28, Artful::get('age'));
    }

    public function testHas()
    {
        Artful::config(['name' => 'yansongda']);

        Artful::set('age', 28);

        self::assertFalse(Artful::has('name'));
        self::assertTrue(Artful::has('age'));
    }

    public function testGetContainerAndClear()
    {
        Artful::config(['name' => 'yansongda']);
        self::assertInstanceOf(ContainerInterface::class, Artful::getContainer());

        Artful::clear();

        $this->expectException(ContainerException::class);
        $this->expectExceptionCode(Exception::CONTAINER_NOT_FOUND);
        $this->expectExceptionMessage('容器未找到: `getContainer()` 方法调用失败! 或许你应该先 `setContainer()`');

        Artful::getContainer();
    }

    public function testMakeService()
    {
        Artful::config(['name' => 'yansongda']);
        self::assertNotSame(Artful::make(Pipeline::class), Artful::make(Pipeline::class));
    }

    public function testRegisterService()
    {
        Artful::config(['name' => 'yansongda']);

        Artful::registerService(FooServiceProviderStub::class, []);

        self::assertEquals('bar', Artful::get('foo'));
    }

    public function testCoreServiceContainer()
    {
        Artful::config(['name' => 'yansongda']);

        self::assertInstanceOf(ContainerInterface::class, Artful::getContainer());
    }

    public function testCoreServiceConfig()
    {
        $config = ['name' => 'yansongda'];
        Artful::config($config);

        self::assertInstanceOf(Config::class, Artful::get(ConfigInterface::class));
        self::assertEquals($config['name'], Artful::get(ConfigInterface::class)->get('name'));

        // 修改 config 的情况
        $config2 = [
            'name' => 'yansongda2',
        ];
        Artful::set(ConfigInterface::class, new Config($config2));

        self::assertEquals($config2['name'], Artful::get(ConfigInterface::class)->get('name'));
    }

    public function testCoreServiceLogger()
    {
        $config = ['name' => 'yansongda','logger' => ['enable' => true]];
        Artful::config($config);

        self::assertInstanceOf(Logger::class, Artful::get(LoggerInterface::class));

        $otherLogger = new Logger('test');
        Artful::set(LoggerInterface::class, $otherLogger);
        self::assertEquals($otherLogger, Artful::get(LoggerInterface::class));
    }

    public function testCoreServiceEvent()
    {
        $config = ['name' => 'yansongda'];
        Artful::config($config);

        self::assertInstanceOf(EventDispatcher::class, Artful::get(EventDispatcherInterface::class));
    }

    public function testCoreServiceHttpClient()
    {
        $config = ['name' => 'yansongda'];
        Artful::config($config);

        self::assertInstanceOf(HttpClientFactory::class, Artful::get(HttpClientFactoryInterface::class));

        // 使用外部 http client
        $factory = Artful::get(HttpClientFactoryInterface::class);

        $client = new Client(['timeout' => 3.0]);
        Artful::set(HttpClientInterface::class, $client);

        self::assertEquals($client, $factory->create());
    }
}
