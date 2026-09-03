# 修复 Issue #42：hyperf/pimple 的 require-dev 与 CI 矩阵冲突

> **时间**：2026-09-02（2026-09-03 修订：吸收 plan-reviewer 初审意见 M1/M2/M3，同步 PR #39 基线漂移）
> **作者**：GLM-5.3 + yansongda
> **状态**：经过人工审核确认（初审结论"修改后执行"，意见已修订，待复审）

## 1. 背景与问题

**现状**：artful 无框架环境依赖 `hyperf/pimple` 作兜底 PSR-11 容器（`ContainerServiceProvider::defaultApplication()`），目前它放在 `composer.json` 的 require-dev 中；CI（tester.yml）以 default/laravel/hyperf × PHP 8.2–8.3 矩阵跑测试（PR #39 后 php `>=8.2`），laravel 矩阵只 `composer require "illuminate/container:^12.0 || ^13.0"`，require-dev 的 pimple 照常安装。

**困境**（均已验证，读过源码）：

1. **laravel 矩阵不真实**：tests 中仅有的 2 处 pimple 引用（`tests/TestCase.php:22` setUp 门控注入、`tests/ArtfulTest.php:67` testConfig 门控）在 laravel 矩阵下全部命中——"config 传容器"三段用例实际测的是 pimple 而非 illuminate/container；setUp 还向全局 `ApplicationContext` 注入 pimple 容器，扭曲探测边界。
2. **default 分支覆盖脆弱**：`Hyperf\Pimple\ContainerFactory::__invoke()` 末行会执行 `ApplicationContext::setContainer($container)`（已验证，vendor 源码 + 仓库内两处独立验证记录）——`defaultApplication()` 调用工厂即污染全局上下文。现状 default 矩阵下，仅"未经过 TestCase::setUp 注入"（如覆写 setUp 的 ArtfulTest）中首个触发容器探测的测试触达 `defaultApplication()`，其余测试全部命中 `hyperfApplication()` 分支；该触达点依赖测试执行顺序，无结构性保证。
3. **覆盖缩水无信号**：`testConfig` 三段用例被 `class_exists` 门控，环境变化时静默跳过，CI 全绿但没测到。
4. **上游同款问题**：`yansongda/pay`（master）模式完全相同（已验证，抓取其 composer.json 与 tester.yml），本次修复模式可平移。

**目标**：

- **laravel 矩阵真实化**——不装 pimple，测纯 illuminate/container 环境
- **laravel/hyperf 矩阵 ↔ 探测分支精确对应**——laravel 稳定覆盖 `laravelApplication()`，hyperf 矩阵结构化覆盖 `hyperfApplication()`（不再依赖隐式污染链）；default 分支受工厂副作用限制，保持现状覆盖水平（首个探测测试触达）并如实表述
- **消除静默跳过**——"config 传容器"用例全矩阵执行，不依赖任何具体容器包
- **运行时零改动**——`src/` 不动，对库使用者（suggest 指引）零影响
- **回归免疫**——即使未来有人误将 pimple 加回 require-dev，laravel 矩阵测试依然正确

## 2. 整体方案

**核心思路**：**require-dev 去掉 pimple + CI 各矩阵显式安装 + 测试用自定义 stub 容器替代对 pimple 的依赖 + FRAMEWORK 环境变量驱动 hyperf 分支覆盖**。

改后矩阵 ↔ 探测分支映射：

```
┌─────────────────────┬────────────────────────────────────────────────────┬──────────────────────────────┐
│ 矩阵/环境            │ 探测分支                                            │ 容器                          │
├─────────────────────┼────────────────────────────────────────────────────┼──────────────────────────────┤
│ laravel (CI)        │ laravelApplication()（稳定覆盖）                     │ Illuminate\Container ^12/^13  │
│ hyperf (CI+env)     │ hyperfApplication()（结构化覆盖）                    │ ApplicationContext 内 pimple   │
│ default (CI)        │ 首个探测测试→defaultApplication()；其余→             │ pimple                        │
│                     │ hyperfApplication()（工厂副作用，与现状分布一致）       │                               │
│ coverage.yml / 本地  │ 同 default 行为分布                                  │ pimple（本地需手动安装）        │
│ testConfig stub 用例 │ 显式传入，不走探测                                   │ ContainerStub（自定义）        │
└─────────────────────┴────────────────────────────────────────────────────┴──────────────────────────────┘
```

文件变更结构：

```
composer.json                        [修改] require-dev 移除 hyperf/pimple（suggest 不动）
.github/workflows/tester.yml         [修改] job 级 env FRAMEWORK=${{ matrix.framework }}（run 步骤零改动）
tests/TestCase.php                   [修改] setUp 门控加 FRAMEWORK 环境变量条件（1 行 + 注释）
tests/ArtfulTest.php                 [修改] testConfig 三段用例改用 ContainerStub，去 class_exists 门控
tests/Stubs/ContainerStub.php        [新增] implements Contract\ContainerInterface
tests/Stubs/NotFoundExceptionStub.php [新增] 极简 implements Psr\...\NotFoundExceptionInterface
phpstan.neon                         [修改] ignoreErrors 增加 2 条（Pimple/ApplicationContext）
.agents/skills/dev-guide/SKILL.md    [修改] 本地测试指引（L20）
```

明确**不动**：`src/`（运行时零改动）、`web/docs/v1/quick-start/install.md`（用户安装指引，suggest 语义不变）、`.github/workflows/coverage.yml`（已显式 require pimple，无 FRAMEWORK env 时行为分布同 default 矩阵）、`.github/workflows/style.yml`。

## 3. 详细设计

### 3.1 composer.json 与 CI 矩阵

- require-dev 移除 `"hyperf/pimple": "^2.2"`；仓库无 composer.lock（已验证），CI 每次全新解析，laravel 矩阵天然得到纯 illuminate 环境。**不加** `composer remove` 防御步骤（见第 5 节：本设计对误加回归免疫，无需双保险）。
- **job 级**增加 `env: FRAMEWORK: ${{ matrix.framework }}`（置于 strategy 块后、steps 前；初审曾误置于 Hyperf composer step——step 级 env 不跨 step 传播，`Run PHPUnit` step 不可见，门控在 CI 中必然失效。job 级对全部 step 生效，且 laravel/default job 的值天然不等于 `hyperf`，门控无副作用）。default/laravel 的 run 步骤零改动。
- CI 写法沿用现有 `composer require <pkg>`（无 lock 下行为正确，已验证 composer 契约）；不引入 spatie 式 `--no-update` + `update` 两段写法（主流但对本仓库属额外变更，无必要）。

### 3.2 TestCase 门控（hyperf 分支的开关）

现状门控条件"pimple 存在即注入"，导致 default 矩阵走错分支。改后伪代码：

```php
// 仅 hyperf 矩阵（CI job env FRAMEWORK=hyperf）模拟 "hyperf 环境有全局容器"，
// 结构化驱动 hyperfApplication() 分支覆盖；其余环境不预置注入——首个触发容器探测的
// 测试经 defaultApplication() 拿到 pimple 容器（ContainerFactory 工厂副作用会写入
// ApplicationContext，后续测试命中 hyperf 分支，行为分布与现状一致）
// 本地开发需安装 hyperf/pimple，否则测试全挂（异常信息自带安装指引）
if (getenv('FRAMEWORK') === 'hyperf'
    && class_exists(ApplicationContext::class)
    && class_exists(ContainerFactory::class)) {
    ApplicationContext::setContainer((new ContainerFactory())());
}
```

- PHP `use` 导入是惰性的，无 pimple 环境下 `class_exists` 前置检查保证不触发 autoload，`use` 语句保留不报错（已验证 PHP 语义）。

### 3.3 stub 容器（消除静默跳过）

新增 `tests/Stubs/ContainerStub.php`，**implements `Yansongda\Artful\Contract\ContainerInterface`**（已验证签名：extends PSR-11 + `make(string $name, array $parameters = []): mixed` + `set(string $name, mixed $entry): mixed`）。行为契约（已验证 Artful.php 各 API 分支）：

| 方法 | 行为 | 对应 Artful 侧依赖 |
|---|---|---|
| `get($id)` | 命中返回条目；未命中抛 `NotFoundExceptionStub` | `Artful::get()` 捕获 `NotFoundExceptionInterface` 转 `ServiceNotFoundException`（已验证 `ServiceNotFoundException implements NotFoundExceptionInterface`） |
| `has($id)` | `isset($this->entries[$id])`，精确反映 set 后状态 | `Artful::has()`、`testHas` 断言 |
| `set($name, $entry)` | 写入并返回 `$entry` | `Artful::set()` 走 `method_exists($container, 'set')` 分支（非 Laravel 容器） |
| `make($name, $params)` | `new $name(...array_values($params))` | 供 `Contract` 完整实现；`testMakeService` 断言两次结果不同，`new` 天然满足 |

`testConfig` 三段用例将 `(new ContainerFactory())()` 替换为 `new ContainerStub()`，删除外层 `class_exists` 门控，断言逻辑不变（assertSame 同一实例）；顶部 `use Hyperf\Pimple\ContainerFactory` 移除（已验证 testConfig 是该文件唯一使用处）。

### 3.4 phpstan（style 矩阵防护）

style.yml 为 `composer install` 全量 dev 依赖，移除 pimple 后 `src/Service/ContainerServiceProvider.php` 对 `Hyperf\Pimple\ContainerFactory`（L9）与 `Hyperf\Context\ApplicationContext`（L8）的静态引用将报 unknown class。仿照现有先例（`phpstan.neon` 已 ignore Illuminate/ThinkPHP/旧 Hyperf 命名空间）增加 2 条规则；`reportUnmatchedIgnoredErrors: false` 保证装着 pimple 的环境下多余规则无副作用。

### 3.5 本地开发指引

`dev-guide/SKILL.md:20` 改为：本地跑测试前 `composer require hyperf/pimple --dev`，并**显式提醒该命令会修改 composer.json、提交时勿包含此改动**。忘装时 `ContainerNotFoundException` 的错误信息已自带指引（"Maybe you should install hyperf/pimple first"，已验证 L99），失败反馈明确。

### 3.6 关键契约标注

| 契约 | 状态 |
|---|---|
| tests 仅 2 文件引用 pimple；Artful API 分支行为；Contract 容器接口签名；phpstan 先例；CI/文档行号；无 composer.lock；**ContainerFactory 工厂副作用污染 ApplicationContext**（vendor 源码） | **已验证（读过源码）** |
| pay 同款问题；spatie CI 模式；composer remove --no-update 契约；hyperf/container 包不存在（Packagist 404）；hyperf/pimple 2.2.2 依赖链；illuminate/container 12/13 可用性（PR #39 已引入 `^12.0 \|\| ^13.0`） | **已验证（外部抓取）** |
| 三矩阵改后全套件全绿；symfony ^7.4 × illuminate/container ^12/^13 composer 解析共存（PR #39 基线）；stub 下 testHas/testMakeService 行为 | **推断（未实测）→ plan Wave 0 spike 覆盖** |

## 4. 推进策略

- **Wave 0（spike，先行）**：容器环境（container-dev skill）复制项目副本验证三矩阵模拟场景：composer 解析成功 + 三种 env 组合下 phpunit 全绿。**不触碰仓库工作区**（副本操作）。发现行为差异则回对话调整方案。本地以进程级 env（`FRAMEWORK=hyperf composer test`）模拟，与 CI job 级 env 对 phpunit 进程等效。
- **执行顺序协调（M3，用户已裁决 2026-09-03：本 plan 先执行）**：仓库内另有未执行的 `docs/implementation/thinkphp8-container-support.md` 计划，与本 plan 改动同一批文件（tests/TestCase.php、tests/ArtfulTest.php、composer.json、tester.yml）且门控指令互不兼容。**本 plan 优先执行，thinkphp8 计划暂缓**；待本 plan 落地后，其门控相关 todo（Task 1）必须基于改造后的实际代码重写（ContainerFactory 门控将不存在：TestCase 门控为 FRAMEWORK env 前置条件、ArtfulTest 已用 ContainerStub），禁止按其文档行号盲改。
- **Wave 1（实施）**：8 处文件改动（第 2 节表格），单批次小任务。
- **Wave 2（验证）**：容器环境复验三矩阵 + phpstan + cs-fix；push 后观察 CI 矩阵 job 全绿。
- **回滚**：全部为配置/测试/文档层改动，按 commit 序列依次 `git revert`（或整 PR revert）；无运行时影响、无用户侧迁移。

## 5. 风险与对策

| 风险 | 严重度 | 对策 |
|---|---|---|
| 本地开发忘装 pimple，`composer test` 全挂 | 中 | dev-guide 指引更新 + 异常信息自带指引；spike 阶段实测该报错路径的可用性 |
| 本地临时 `--dev` require 误提交 composer.json | 低 | 方案本质免疫：env 门控 + stub 均不依赖 pimple，laravel 矩阵依然正确，仅包冗余；PR diff 一行可见 |
| default 分支触达点依赖测试执行顺序，无结构性保证（工厂副作用不可抑制，属 `不动 src/` 约束下的接受项） | 低 | reviewer 已核对 default/hyperf 两分支产出同一 pimple 容器、断言无差异，行为分布与现状一致；文档如实表述，不做过度设计 |
| 与 `docs/implementation/thinkphp8-container-support.md` 计划改同一批文件且门控指令互不兼容 | 中 | 用户已裁决：本 plan 先执行，thinkphp8 计划暂缓并按本 plan 产出适配（见推进策略协调条款） |
| hyperf 矩阵 env 漏配，hyperfApplication() 失覆盖且无失败信号 | 低 | env 提升至 job 级（与矩阵变量绑定，一处声明），review 可见；plan 验收含 grep 检查 |
| phpstan ignore 规则过宽/过窄 | 低 | `reportUnmatchedIgnoredErrors: false` 已保证宽松安全 |

## 6. 监控与可观测性

纯 dev 基建变更，无线上监控需求。指标即 CI：tester.yml 3 框架 × 2 PHP（8.2/8.3）= 6 job、coverage.yml、style.yml 全绿；其中 laravel 矩阵从"伪覆盖"变为真实覆盖是本次修复的核心验收信号。

## 附录 A：被否决的替代方案

| 方案 | 否决原因 |
|---|---|
| 仅 CI laravel 步骤加 `composer remove --dev`（最小改动） | 未落实 issue 建议（require-dev 仍脏）；testConfig 静默跳过依旧；default 分支覆盖脆弱性依旧 |
| 仅移除 require-dev（issue 原意） | testConfig 覆盖缩水且无信号；default 分支覆盖脆弱性依旧 |
| hyperf 矩阵用真 hyperf 容器 | `hyperf/container` 包不存在（已验证 404），实际是 `hyperf/di`，拖 10+ 依赖（php-parser、phpdotenv 等），成本收益不成比例 |

## 附录 B：外部调研来源

- yansongda/pay composer.json 与 tester.yml：https://raw.githubusercontent.com/yansongda/pay/master/composer.json 、https://raw.githubusercontent.com/yansongda/pay/master/.github/workflows/tester.yml
- spatie/laravel-permission CI 矩阵模式：https://github.com/spatie/laravel-permission/blob/main/.github/workflows/run-tests.yml
- composer CLI 契约：https://getcomposer.org/doc/03-cli.md
- hyperf/container 404：https://packagist.org/packages/hyperf/container
- hyperf/pimple 2.2.2 依赖与 hyperf/di 依赖链：https://repo.packagist.org/p2/hyperf/pimple.json 、https://repo.packagist.org/p2/hyperf/di.json
- illuminate/container 12/13 PHP 约束：https://github.com/laravel/framework/blob/12.x/src/Illuminate/Container/composer.json
- 仓库 PR #39（2026-09-02 合并，基线漂移来源）：composer.json/phpunit.xml/tester.yml 依赖全量升级
