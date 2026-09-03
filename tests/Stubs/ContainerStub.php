<?php

declare(strict_types=1);

namespace Yansongda\Artful\Tests\Stubs;

use Yansongda\Artful\Contract\ContainerInterface;

/**
 * 测试用最小容器桩：仅实现 Artful 容器调用面（get/has/set/make），替代测试期对 pimple 的依赖.
 */
class ContainerStub implements ContainerInterface
{
    public array $entries = [];

    /**
     * @throws NotFoundExceptionStub 服务未注册时抛出
     */
    public function get(string $id): mixed
    {
        if (!isset($this->entries[$id])) {
            throw new NotFoundExceptionStub('服务未找到: '.$id);
        }

        return $this->entries[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    public function set(string $name, mixed $entry): mixed
    {
        $this->entries[$name] = $entry;

        return $entry;
    }

    public function make(string $name, array $parameters = []): mixed
    {
        return new $name(...array_values($parameters));
    }
}
