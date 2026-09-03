<?php

declare(strict_types=1);

namespace Yansongda\Artful\Tests\Stubs;

/**
 * 测试用「服务未找到」异常桩：满足 PSR-11 NotFoundExceptionInterface 契约，供 ContainerStub 抛出.
 */
class NotFoundExceptionStub extends \Exception implements \Psr\Container\NotFoundExceptionInterface
{
}
