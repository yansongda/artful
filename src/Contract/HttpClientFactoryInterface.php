<?php

declare(strict_types=1);

namespace Yansongda\Artful\Contract;

use Psr\Http\Client\ClientInterface;

interface HttpClientFactoryInterface
{
    public function create(array $options = []): ClientInterface;
}
