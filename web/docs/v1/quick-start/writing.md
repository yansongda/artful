# 编写请求插件

以下为编写请求插件的示例代码：

```php
<?php

declare(strict_types=1);

namespace App\Service\Http\Yansongda\;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Direction\OriginResponseDirection;use Yansongda\Artful\Rocket;

class GetArtfulPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        $payload = $rocket->getPayload();

        $rocket->setDirection(OriginResponseDirection::class)
            ->setPayload([
                '_method' => 'GET',
                '_url' => 'https://artful.yansongda.cn?'.$payload?->query(),
            ]);

        return $next($rocket);
    }
}
```

以上就是一个简单的请求本网站的插件，我们可以在 `assembly` 方法中对请求进行修改，然后返回给下一个插件。

## `$payload = $rocket->getPayload();`

`getPayload` 方法用于获取请求的载荷，即，请求的参数，一定程度上，`$payload` 近似等于入参参数。

## `->setDirection(OriginResponseDirection::class)`

`setDirection` 方法用于设置请求的方向，即，请求的目标是什么。

这里，由于我们要请求本网站，而本网站是一个 html 网站，并不是 API 接口什么的，所以，我们获取原始的请求结果即可。

## `->setPayload([...])`

`setPayload` 方法用于设置请求的载荷，即，请求的参数。

这里其实就是明确 API 的 `请求方法`，`请求路径`，`请求参数` 等信息。