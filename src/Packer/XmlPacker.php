<?php

declare(strict_types=1);

namespace Yansongda\Artful\Packer;

use Yansongda\Artful\Contract\PackerInterface;
use Yansongda\Supports\Arr;
use Yansongda\Supports\Collection;

class XmlPacker implements PackerInterface
{
    public function pack(null|array|Collection $payload): string
    {
        return Collection::wrap($payload)->toXml();
    }

    public function unpack(string $payload): array
    {
        return Arr::wrapXml($payload);
    }
}
