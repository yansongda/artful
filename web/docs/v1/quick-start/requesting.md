# 使用插件

我们知道，要想请求一个 API，我们得知道它的 `请求方法`，`请求路径`，`请求参数`，`请求头`，`请求体` 等信息。

我们在上一节中创建了一个请求本网站的插件，并完善了 `请求方法`，`请求路径`，`请求参数`，接下来，我们将把整个流程串起来。

```php
<?php

use Yansongda\Artful\Artful;

Artful::config();

$plugins = [
    \Yansongda\Artful\Plugin\StartPlugin::class,
    \App\Service\Http\Yansongda\GetArtfulPlugin::class,
    \Yansongda\Artful\Plugin\AddPayloadBodyPlugin::class,
    \Yansongda\Artful\Plugin\AddRadarPlugin::class,
    \Yansongda\Artful\Plugin\ParserPlugin::class,
];
$params = [];

$response = Artful::artful($plugins, $params);

$html = (string) $response->getBody();
```

## `Artful::config()`

`config` 方法用于初始化 Artful，这里我们没有啥特殊要求，所以不需要传入任何参数。

## `$plugins = [...]`

`$plugins` 用于设置 Artful 的插件列表，这里我们按照顺序依次添加了以下插件：

- `StartPlugin`：此插件就是简单的将所有 `$params` 入参合并到 `$payload` 里，没有其他特殊逻辑；
- `GetArtfulPlugin`：我们自己编写的请求本网站的插件；
- `AddPayloadBodyPlugin`：此插件将 `$payload` 的参数转换为请求体 `_body`，因为咱们这个是 GET 请求，没有请求体，所以 `_body` 也是空的；
- `AddRadarPlugin`：此插件将 `请求头`，`请求体` 给完善并最终转换为了 `Rocket` 的 `Radar`，将发起最终的 http 请求；
- `ParserPlugin`：此插件就是简单的将请求结果转换为 `Response` 对象；

## `$params = [...]`

`$params` 用于设置 Artful 的入参参数，这里我们没有啥特殊要求，所以不需要传入任何参数。

## `Artful::artful(...)`

`artful` 方法用于执行 Artful 的插件列表，这里我们传入了 `$plugins` 和 `$params`，并将返回值赋值给了 `$response`。

由于我们的 direction 是 `OriginResponseDirection`，所以 `$response` 是一个 `Response` 对象，我们可以直接获取到请求结果。
