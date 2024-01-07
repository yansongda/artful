<?php

declare(strict_types=1);

namespace Yansongda\Artful\Plugin;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;

use function Yansongda\Artful\get_direction;
use function Yansongda\Artful\get_packer;

class ParserPlugin implements PluginInterface
{
    /**
     * @throws InvalidParamsException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        /* @var Rocket $rocket */
        $rocket = $next($rocket);

        Logger::debug('[ParserPlugin] 插件开始装载', ['rocket' => $rocket]);

        /* @var ResponseInterface $response */
        $response = $rocket->getDestination();

        $rocket->setDestination(get_direction($rocket->getDirection())->guide(
            get_packer($rocket->getPacker()),
            $response
        ));

        Logger::debug('[ParserPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $rocket;
    }
}
