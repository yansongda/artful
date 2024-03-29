<?php

declare(strict_types=1);

namespace Yansongda\Artful\Direction;

use Psr\Http\Message\ResponseInterface;
use Yansongda\Artful\Contract\DirectionInterface;
use Yansongda\Artful\Contract\PackerInterface;
use Yansongda\Artful\Exception\Exception;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Supports\Collection;

class CollectionDirection implements DirectionInterface
{
    /**
     * @throws InvalidResponseException
     */
    public function guide(PackerInterface $packer, ?ResponseInterface $response, array $params = []): Collection
    {
        if (is_null($response)) {
            throw new InvalidResponseException(Exception::RESPONSE_EMPTY, '响应异常: 响应为空，不能进行 direction');
        }

        $body = (string) $response->getBody();

        if (!is_null($result = $packer->unpack($body, $params))) {
            return new Collection($result);
        }

        throw new InvalidResponseException(Exception::RESPONSE_UNPACK_ERROR, '响应异常: 解包错误', ['body' => $body, 'response' => $response]);
    }
}
