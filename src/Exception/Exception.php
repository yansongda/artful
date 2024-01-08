<?php

declare(strict_types=1);

namespace Yansongda\Artful\Exception;

use Throwable;

class Exception extends \Exception
{
    public const UNKNOWN_ERROR = 9999;

    /**
     * 关于容器.
     */
    public const CONTAINER_ERROR = 1000;

    public const CONTAINER_NOT_FOUND = 1001;

    public const CONTAINER_SERVICE_NOT_FOUND = 1002;

    /**
     * 关于参数.
     */
    public const PARAMS_ERROR = 2000;

    public const PARAMS_DIRECTION_INVALID = 2001;

    public const PARAMS_PACKER_INVALID = 2002;

    public const PARAMS_EVENT_DRIVER_INVALID = 2003;

    public const PARAMS_HTTP_CLIENT_INVALID = 2004;

    public const PARAMS_LOGGER_DRIVER_INVALID = 2005;

    public const PARAMS_HTTP_CLIENT_FACTORY_INVALID = 2006;

    public const PARAMS_PLUGIN_INCOMPATIBLE = 2007;

    public const PARAMS_PARSER_DIRECTION_INVALID = 2008;

    public const PARAMS_SHORTCUT_INVALID = 2009;

    /**
     * 关于响应.
     */
    public const RESPONSE_ERROR = 3000;

    public const REQUEST_RESPONSE_ERROR = 3001;

    public const RESPONSE_UNPACK_ERROR = 3002;

    public const RESPONSE_EMPTY = 3003;

    public mixed $extra;

    public function __construct(string $message = '未知异常', int $code = self::UNKNOWN_ERROR, mixed $extra = null, ?Throwable $previous = null)
    {
        $this->extra = $extra;

        parent::__construct($message, $code, $previous);
    }
}
