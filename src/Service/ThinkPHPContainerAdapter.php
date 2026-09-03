<?php

declare(strict_types=1);

namespace Yansongda\Artful\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use think\Container as ThinkPHPContainer;

/**
 * think\Container 的 PSR-11 适配器.
 *
 * think\Container::get() 仅在已显式绑定（has() 为真）时才会解析，
 * 不像 illuminate/container、hyperf/pimple 那样支持对未绑定类的自动解析，
 * 而 Pipeline 等组件会直接通过 PSR-11 的 get() 解析插件类，
 * 因此将本适配器绑定到 Psr\Container\ContainerInterface 上做自动解析兜底.
 */
class ThinkPHPContainerAdapter implements ContainerInterface
{
    public function __construct(private readonly ThinkPHPContainer $container) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): mixed
    {
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }

        return $this->container->make($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
