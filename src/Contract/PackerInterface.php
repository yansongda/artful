<?php

declare(strict_types=1);

namespace Yansongda\Artful\Contract;

use Yansongda\Supports\Collection;

interface PackerInterface
{
    public function pack(null|array|Collection $payload): string;

    public function unpack(string $payload): ?array;
}
