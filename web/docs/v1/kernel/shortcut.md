# 💤 快捷方式

Shortcut 即快捷方式，是一系列 Plugin 的组合，方便我们使用 Artful。

## 定义

```php
<?php

declare(strict_types=1);

namespace Yansongda\Artful\Contract;

interface ShortcutInterface
{
    /**
     * @return \Yansongda\Artful\Contract\PluginInterface[]|string[]
     */
    public function getPlugins(array $params): array;
}
```

## 详细说明

以我们刚刚在 [插件Plugin](/docs/v1/kernel/plugin.md) 中的例子来说明，
支付宝电脑支付，其实也是一种 快捷方式

```php
<?php

declare(strict_types=1);

namespace Yansongda\Pay\Shortcut\Alipay;

use Yansongda\Artful\Contract\ShortcutInterface;
use Yansongda\Artful\Plugin\ParserPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\AddPayloadSignaturePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\AddRadarPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\FormatPayloadBizContentPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\Pay\Web\PayPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\ResponseHtmlPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\StartPlugin;

class WebShortcut implements ShortcutInterface
{
    public function getPlugins(array $params): array
    {
        return [
            StartPlugin::class,
            PayPlugin::class,
            FormatPayloadBizContentPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponseHtmlPlugin::class,
            ParserPlugin::class,
        ];
    }
}
```

是不是灰常简单？
