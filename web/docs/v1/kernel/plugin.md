# 🔌 Plugin

得益于 pipeline，`Artful` 中的所有数据变换都通过 plugin 来实现，因此使用方式非常灵活简单。

## 定义

```php
<?php

declare(strict_types=1);

namespace Yansongda\Artful\Contract;

use Closure;
use Yansongda\Artful\Rocket;

interface PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket;
}
```

## 内置插件

`Artful` 内置了一些常用的插件，您可以直接使用，也可以参考其实现自定义插件。

具体可参考：`\Yansongda\Artful\Plugin` 目录下的插件。

## 详细说明

这里以 `yansongda/pay` 中的相关插件为例，来说明 plugin 的使用。

### 支付宝电脑支付

以支付宝的电脑支付为例，我们知道，支付宝电脑支付首先需要 组装(assembly) 一系列支付宝要求的参数，
然后，需要以 form 表单，或者 GET 的方式请求支付宝的地址，这样才能跳转到支付宝的电脑支付页面进行支付。

所以，除了支付宝公共的，生成签名、验签、调用支付宝API 等等公共的事情以外，我们还需要两个 Plugin

- 组装参数 Plugin

```php
<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Alipay\V2\Pay\Web;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Direction\ResponseDirection;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Traits\SupportServiceProviderTrait;

/**
 * @see https://opendocs.alipay.com/open/59da99d0_alipay.trade.page.pay?pathHash=8e24911d&ref=api&scene=22
 */
class PayPlugin implements PluginInterface
{
    use SupportServiceProviderTrait;

    /**
     * @throws ContainerException
     * @throws ServiceNotFoundException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Alipay][Pay][Web][PayPlugin] 插件开始装载', ['rocket' => $rocket]);

        $this->loadAlipayServiceProvider($rocket);

        $rocket->setDirection(ResponseDirection::class)
            ->mergePayload([
                'method' => 'alipay.trade.page.pay',
                'biz_content' => array_merge(
                    [
                        'product_code' => 'FAST_INSTANT_TRADE_PAY',
                    ],
                    $rocket->getParams()
                ),
            ]);

        Logger::info('[Alipay][Pay][Web][PayPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }
}
```

这个 Plugin 的目的就是为了组装一系列支付宝所需要的参数，同时，由于电脑支付是不需要后端 http 调用支付宝接口的，
只需要一个浏览器的响应，所以，我们把 🚀 的 `Direction` 设置成了 `ResponseDirection::class`。

- 跳转响应 Plugin

```php
<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Alipay\V2;

use Closure;
use GuzzleHttp\Psr7\Response;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;
use Yansongda\Supports\Collection;

class ResponseHtmlPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        /* @var Rocket $rocket */
        $rocket = $next($rocket);

        Logger::debug('[Alipay][ResponseHtmlPlugin] 插件开始装载', ['rocket' => $rocket]);

        $radar = $rocket->getRadar();

        $response = 'GET' === $radar->getMethod() ?
            $this->buildRedirect($radar->getUri()->__toString(), $rocket->getPayload()) :
            $this->buildHtml($radar->getUri()->__toString(), $rocket->getPayload());

        $rocket->setDestination($response);

        Logger::info('[Alipay][ResponseHtmlPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $rocket;
    }

    protected function buildRedirect(string $endpoint, Collection $payload): Response
    {
        $url = $endpoint.(!str_contains($endpoint, '?') ? '?' : '&').$payload->query();

        $content = sprintf(
            '<!DOCTYPE html>
                    <html lang="en">
                        <head>
                            <meta charset="UTF-8" />
                            <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
                    
                            <title>Redirecting to %1$s</title>
                        </head>
                        <body>
                            Redirecting to %1$s.
                        </body>
                    </html>',
            htmlspecialchars($url, ENT_QUOTES)
        );

        return new Response(302, ['Location' => $url], $content);
    }

    protected function buildHtml(string $endpoint, Collection $payload): Response
    {
        $sHtml = "<form id='alipay_submit' name='alipay_submit' action='".$endpoint."' method='POST'>";
        foreach ($payload->all() as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipay_submit'].submit();</script>";

        return new Response(200, [], $sHtml);
    }
}
```

在处理好支付宝所需要的参数之后，按照其它正常逻辑，应该调用支付宝API获取数据了，
但是由于 电脑支付 不是直接调用支付宝API的，
所以，这里使用了 `后置 plugin` 处理组装相关 html 代码进行 post 或者 GET 请求访问支付宝电脑支付页面。

最后，得益于 🚀 的 `Direction` 机制，最终返回给你的就是一个符合 PSR7 规范的 `Response` 对象了，
您可以集成到任何符合相关规范的框架中。

### 支付宝查询订单

```php
<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Alipay\V2\Pay\Web;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;

/**
 * @see https://opendocs.alipay.com/open/bff76748_alipay.trade.query?pathHash=e3ddce1d&ref=api&scene=23
 */
class QueryPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Alipay][Pay][Web][QueryPlugin] 插件开始装载', ['rocket' => $rocket]);

        $rocket->mergePayload([
            'method' => 'alipay.trade.query',
            'biz_content' => $rocket->getParams(),
        ]);

        Logger::info('[Alipay][Pay][Web][QueryPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }
}
```

通过以上代码，我们大概能明白，查询订单的 `QueryPlugin` 插件，通过支付宝官方文档，我们知道，
查询订单的 API 将传参中的 method 改为了 `alipay.trade.query`，其它参数均是个性化参数，和入参有关，
因此，我们在做查询订单时，是需要简单的把 method 按要求更改即可，是不是很简单？

### 微信查询订单

```php
<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Wechat\V3\Pay\Jsapi;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Supports\Collection;

use function Yansongda\Pay\get_wechat_config;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/query-by-out-trade-no.html
 * @see https://pay.weixin.qq.com/docs/partner/apis/partner-jsapi-payment/query-by-out-trade-no.html
 */
class QueryPlugin implements PluginInterface
{
    /**
     * @throws InvalidParamsException
     * @throws ContainerException
     * @throws ServiceNotFoundException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Wechat][Pay][Jsapi][QueryPlugin] 插件开始装载', ['rocket' => $rocket]);

        $params = $rocket->getParams();
        $config = get_wechat_config($params);
        $payload = $rocket->getPayload();
        $outTradeNo = $payload?->get('out_trade_no') ?? null;

        if (empty($outTradeNo)) {
            throw new InvalidParamsException(Exception::PARAMS_NECESSARY_PARAMS_MISSING, '参数异常: Jsapi 通过商户订单号查询订单，参数缺少 `out_trade_no`');
        }

        $rocket->setPayload([
            '_method' => 'GET',
            '_url' => 'v3/pay/transactions/out-trade-no/'.$outTradeNo.'?'.$this->normal($config),
            '_service_url' => 'v3/pay/partner/transactions/out-trade-no/'.$outTradeNo.'?'.$this->service($payload, $config),
        ]);

        Logger::info('[Wechat][Pay][Jsapi][QueryPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function normal(array $config): string
    {
        return http_build_query([
            'mchid' => $config['mch_id'] ?? '',
        ]);
    }

    protected function service(Collection $payload, array $config): string
    {
        return http_build_query([
            'sp_mchid' => $config['mch_id'] ?? '',
            'sub_mchid' => $payload->get('sub_mchid', $config['sub_mch_id'] ?? ''),
        ]);
    }
}
```

通过微信官方文档，我们知道，查询订单的 API 将传参中的 url 是随参数变化而变化的，因此我们需要从 `payload` 中拿到相关的参数丢给微信，
通过以上代码我们能很容易明白。
