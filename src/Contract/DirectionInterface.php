<?php

declare(strict_types=1);

namespace Yansongda\Artful\Contract;

use Psr\Http\Message\ResponseInterface;

interface DirectionInterface
{
    public function guide(PackerInterface $packer, ?ResponseInterface $response, array $params = []): mixed;
}
