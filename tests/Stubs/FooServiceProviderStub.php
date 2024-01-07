<?php

declare(strict_types=1);

namespace Yansongda\Artful\Tests\Stubs;

use Yansongda\Artful\Contract\ServiceProviderInterface;
use Yansongda\Artful\Artful;
use Yansongda\Artful\Exception\ContainerException;

class FooServiceProviderStub implements ServiceProviderInterface
{
    /**
     * @throws ContainerException
     */
    public function register(mixed $data = null): void
    {
        Artful::set('foo', 'bar');
    }
}
