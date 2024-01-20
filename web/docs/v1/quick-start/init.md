# 初始化

初始化有两种方式，大家可根据自己的习惯选择合适的方式。

SDK 一旦初始化后，底层使用单例模式保存配置信息，所以，每次使用只需初始化一次即可，无需多次，后续重复初始化将不会生效
当然，您也可以使用 `_force` 参数强制初始化覆盖原来的配置项。

假设有以下配置文件：

```php
$config = [
    'name' => 'yansongda',
    // ...
    'logger' => [
        'enable' => false,
        'file' => './logs/pay.log',
        'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
        'type' => 'single', // optional, 可选 daily.
        'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
    ],
    'http' => [ // optional
        'timeout' => 5.0,
        'connect_timeout' => 5.0,
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],
];
```

## 方式一 <Badge type="tip" text="推荐" />

直接调用 `config` 方法初始化

```php
Artful::config($config);
```

如果需要强制初始化覆盖配置信息

```php
Artful::config(array_merge($config, ['_force' => true]));
```

## 方式二

在每次实际调用时顺便初始化

```php
Artful::{$provider}($config)->{$shortcut}($order);
```

如果需要强制初始化覆盖配置信息

```php
Artful::{$provider}(array_merge($config, ['_force' => true]))->{$shortcut}($order);
```
