<?php

declare(strict_types=1);

namespace Yansongda\Artful\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $message = '服务未找到', int $code = self::CONTAINER_SERVICE_NOT_FOUND, mixed $extra = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $extra, $previous);
    }
}
