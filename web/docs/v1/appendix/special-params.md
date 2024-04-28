# 内置特殊参数

以下参数为内置特殊参数，用于 `Artful` 内置插件，如果您没有使用 `Artful` 内置插件，忽略即可，您可以随意自行设计特殊参数。

## `_return_rocket`

指定 `Artful::artful()` 方法是否返回 `Rocket` 实例。

## `_method`

指定请求方法，可选值为 `GET`、`POST`、`PUT`、`PATCH`、`DELETE`。

将在组装 `Radar` 请求时使用，具体可参考：`\Yansongda\Artful\Plugin\AddRadarPlugin`。

## `_url`

指定请求 url。

将在组装 `Radar` 请求时使用，具体可参考：`\Yansongda\Artful\Plugin\AddRadarPlugin`。

## `_body`

指定请求体。

将在组装 `Radar` 请求时使用，具体可参考：`\Yansongda\Artful\Plugin\AddRadarPlugin`。

## `_http`

http 客户端的配置文件，将在 httpFactory 创建 http 客户端时使用
