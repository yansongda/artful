<?php

declare(strict_types=1);

namespace Yansongda\Artful;

use Closure;
use Illuminate\Container\Container as LaravelContainer;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Yansongda\Artful\Contract\DirectionInterface;
use Yansongda\Artful\Contract\PackerInterface;
use Yansongda\Artful\Contract\ServiceProviderInterface;
use Yansongda\Artful\Direction\CollectionDirection;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\ContainerNotFoundException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Packer\JsonPacker;
use Yansongda\Artful\Service\ConfigServiceProvider;
use Yansongda\Artful\Service\ContainerServiceProvider;
use Yansongda\Artful\Service\EventServiceProvider;
use Yansongda\Artful\Service\HttpServiceProvider;
use Yansongda\Artful\Service\LoggerServiceProvider;

class Artful
{
    /**
     * @var string[]
     */
    private array $coreService = [
        ContainerServiceProvider::class,
        ConfigServiceProvider::class,
        LoggerServiceProvider::class,
        EventServiceProvider::class,
        HttpServiceProvider::class,
    ];

    private static null|Closure|ContainerInterface $container = null;

    /**
     * @throws ContainerException
     */
    private function __construct(array $config, Closure|ContainerInterface $container = null)
    {
        $this->registerServices($config, $container);

        Artful::set(DirectionInterface::class, CollectionDirection::class);
        Artful::set(PackerInterface::class, JsonPacker::class);
    }

    /**
     * @return mixed
     *
     * @throws ContainerException
     * @throws ServiceNotFoundException
     */
    public static function __callStatic(string $service, array $config)
    {
        if (!empty($config)) {
            self::config(...$config);
        }

        return self::get($service);
    }

    /**
     * @throws ContainerException
     */
    public static function config(array $config = [], Closure|ContainerInterface $container = null): bool
    {
        if (self::hasContainer() && !($config['_force'] ?? false)) {
            return false;
        }

        new self($config, $container);

        return true;
    }

    /**
     * @codeCoverageIgnore
     *
     * @throws ContainerException
     */
    public static function set(string $name, mixed $value): void
    {
        try {
            $container = Artful::getContainer();

            if ($container instanceof LaravelContainer) {
                $container->singleton($name, $value instanceof Closure ? $value : static fn () => $value);

                return;
            }

            if (method_exists($container, 'set')) {
                $container->set(...func_get_args());

                return;
            }
        } catch (ContainerNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException('容器异常: '.$e->getMessage());
        }

        throw new ContainerException('容器异常: 当前容器类型不支持 `set` 方法');
    }

    /**
     * @codeCoverageIgnore
     *
     * @throws ContainerException
     */
    public static function make(string $service, array $parameters = []): mixed
    {
        try {
            $container = Artful::getContainer();

            if (method_exists($container, 'make')) {
                return $container->make(...func_get_args());
            }
        } catch (ContainerNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException('容器异常: '.$e->getMessage());
        }

        $parameters = array_values($parameters);

        return new $service(...$parameters);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws ContainerException
     */
    public static function get(string $service): mixed
    {
        try {
            return Artful::getContainer()->get($service);
        } catch (NotFoundExceptionInterface $e) {
            throw new ServiceNotFoundException('服务未找到: '.$e->getMessage());
        } catch (ContainerNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException('容器异常: '.$e->getMessage());
        }
    }

    /**
     * @throws ContainerNotFoundException
     */
    public static function has(string $service): bool
    {
        return Artful::getContainer()->has($service);
    }

    public static function setContainer(null|Closure|ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * @throws ContainerNotFoundException
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container instanceof ContainerInterface) {
            return self::$container;
        }

        if (self::$container instanceof Closure) {
            return (self::$container)();
        }

        throw new ContainerNotFoundException('容器未找到: `getContainer()` failed! Maybe you should `setContainer()` first', Exception\Exception::CONTAINER_NOT_FOUND);
    }

    public static function hasContainer(): bool
    {
        return self::$container instanceof ContainerInterface || self::$container instanceof Closure;
    }

    public static function clear(): void
    {
        self::$container = null;
    }

    /**
     * @throws ContainerException
     */
    public static function load(string $service, mixed $data): void
    {
        self::registerService($service, $data);
    }

    /**
     * @throws ContainerException
     */
    public static function registerService(string $service, mixed $data): void
    {
        $var = new $service();

        if ($var instanceof ServiceProviderInterface) {
            $var->register($data);
        }
    }

    /**
     * @throws ContainerException
     */
    private function registerServices(array $config, Closure|ContainerInterface $container = null): void
    {
        foreach ($this->coreService as $service) {
            self::registerService($service, ContainerServiceProvider::class == $service ? $container : $config);
        }
    }
}
