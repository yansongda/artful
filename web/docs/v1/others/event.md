# 事件

在请求 API 过程中，可能会想监听一些事件，好同时处理一些其它任务。

SDK 使用 [symfony/event-dispatcher](https://github.com/symfony/event-dispatcher) 组件进行事件的相关操作。

在使用之前，需要先确保安装了 `symfony/event-dispatcher` 组件，如果没有，请安装

```shell
composer require symfony/event-dispatcher
```

## 使用

::: tip
使用事件系统前，确保已初始化 Artful。即调用了 `Artful::config($config)`
:::

```php
<?php

use Yansongda\Artful\Event;
use Yansongda\Artful\Event\HttpStart;

// 1. 新建一个监听器
class HttpStartListener
{
    public function sendEmail(HttpStart $event)
    {
        // 可以直接通过 $event 获取事件的额外数据，例如：
        //      支付传递的参数：$event->params

        // coding to send email...
    }
}

// 2. 添加监听器
Event::addListener(HttpStart::class, [new HttpStartListener(), 'sendEmail']);

// 3. 喝杯咖啡
```

## 事件

### 核心逻辑开始

- 事件类：Yansongda\Artful\Event\ArtfulStart
- 说明：此事件将在进入核心流程时进行抛出。此时 Artful 只进行了相关初始化操作，其它所有操作均未开始。
- 额外数据：
    - $rocket (相关参数)
    - $plugins (所有使用的插件)
    - $params (传递的原始参数)

### 核心逻辑结束

- 事件类：Yansongda\Artful\Event\ArtfulEnd
- 说明：此事件将在所有参数处理完毕时抛出。
- 额外数据：
    - $rocket (相关参数)

### 开始调用API

- 事件类：Yansongda\Artful\Event\HttpStart
- 说明：此事件将在请求 API 前抛出。
- 额外数据：
    - $rocket (相关参数)

### 调用API结束

- 事件类：Yansongda\Artful\Event\HttpEnd
- 说明：此事件将在请求 API 完成之后抛出。
- 额外数据：
    - $rocket (相关参数)
