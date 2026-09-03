# 技术设计：兼容 ThinkPHP 8 容器（重做 #24）

> **时间**：2026-09-02
> **作者**：GLM-5.3-Flash + yansongda
> **状态**：已经 plan-reviewer 对抗审查（2026-09-02，0 BLOCKER / 0 MAJOR——原 M1 因 main 升级 `"php": ">=8.2"` 失效，MINOR 已消化）；已对齐最新 main（e416612：phpunit ^11.5、supports ~4.1.0、tester.yml 矩阵 8.2-8.5）

## 1. 背景与问题

**现状**：artful 通过 `ContainerServiceProvider` 在启动时探测运行环境容器（laravel → hyperf → default/pimple），`Artful::set()/get()/make()` 以 `instanceof`/`method_exists` 分支适配各容器。社区 PR #24（ken678）尝试增加 ThinkPHP 8 支持。

**困境**（均已在 Docker + think-container 3.x 实测复现）：

1. **插件管道全挂**：`supports\Pipeline::carry()` 解析字符串插件时**直连容器** `$this->container->get($name)`，不经 `Artful::get()`；而 think 容器的 `get()` 只认显式绑定、不做自动解析（pimple 会回退 `make()`、Laravel 会 `build()`，think 是唯一例外）→ PR 只改 `Artful::get()` 覆盖不到，所有 `artful()/shortcut()` 调用抛 `ClassNotFoundException`
2. **`make()` 语义偏差**：think 的 `make()` 默认缓存实例，其余容器均为"每次新建" → `testMakeService`（assertNotSame）失败
3. **测试假象**：`tests/TestCase.php` 预置 hyperf `ApplicationContext`，导致检测时 pimple 永远抢占 think——套件全绿但根本没测到 think；单纯给 CI 加 thinkphp 任务是**无效覆盖**（实测同一套件换执行顺序即 2 错 1 败）
4. **工程缺口**：PR 分支基于 2024-12 旧 main；`thinkphpApplication()` 缺 `: bool`、cs-fixer 不通过；无 suggest、无 CI 任务、无测试

**目标**：**ThinkPHP 8 项目零配置接入**（随框架自动检测）；**既有三种模式零回归**；**CI 真实覆盖 think 容器**；**不引入 require 依赖**（think-container 保持可选）。

## 2. 整体方案

**核心思路**：**保留 PR 的分支结构（与既有 Laravel 分支先例一致），在 Service 层新增一个 PSR-11 适配器补齐 think 容器缺失的"自动解析"语义，并通过测试基建修正让 CI 真实覆盖**。

```
容器检测（ContainerServiceProvider::register）
  laravel 检出 ──────→ LaravelContainer::getInstance()（现状不动）
  hyperf 检出 ──────→ ApplicationContext（现状不动）
  thinkphp 检出（新增）
        ├─ Artful::setContainer(think\Container::getInstance())   ← 主容器
        ├─ Contract\ContainerInterface ──→ 裸 think 容器（与 laravel/hyperf 分支一致）
        └─ Psr\Container\ContainerInterface ──→ ThinkPHPContainerAdapter（新增）
                                                  │ get(): bound ? get : make（自动解析兜底）
                                                  ↓
                              Pipeline / HttpClientFactory 构造注入的 PSR 容器
```

文件结构（新增 2、修改 6，均基于当前 main）：

```
src/
├── Artful.php                                [修改] set/get/make 各加 think 分支
└── Service/
    ├── ContainerServiceProvider.php          [修改] 检测项 + thinkphpApplication(): bool
    └── ThinkPHPContainerAdapter.php          [新增] PSR-11 自动解析适配器
tests/
├── ArtfulTest.php                            [修改] testConfig 的 pimple 容器工厂分支增加 think 门控（1 行）
├── TestCase.php                              [修改] think 存在时不预置 hyperf 上下文
└── ThinkPHPContainerTest.php                 [新增] 7 个 think 独立测试
composer.json                                 [修改] suggest 增加 think-container
.github/workflows/tester.yml                  [修改] matrix 增加 thinkphp 任务
```

## 3. 详细设计

### 3.1 `Artful` 三个方法的 think 分支

| 方法 | think 语义 | 边界 |
|---|---|---|
| `set($name, $value)` | `$container->delete($name)` 清缓存后 `bind($name, 闭包包装值)` | 闭包包装**保留 PR 写法**：标量（`set('age', 28)`）依赖它；class-string 返回字符串与 pimple/laravel 既有行为一致，由 `get_direction()/get_packer()` 的二次 get 兜底；`delete()` 必须保留（清除已缓存实例，否则重绑定不生效） |
| `get($service)` | `$container->make($service)`（think 原生 get 对未绑定类直接抛异常） | 未绑定的 shortcut/plugin 类依赖此路径；think 的 `ClassNotFoundException` 已实现 PSR `NotFoundExceptionInterface`，异常映射不变 |
| `make($service, $parameters)` | `$container->make($service, $parameters, true)` 强制新建 | 修正单例缓存偏差；instance-bound 场景 think 与 pimple 的 make 均不解析绑定，行为对齐 |

分支位置：`set`/`make` 插在 Laravel 分支与通用 `method_exists` 回退之间；`get` 在取容器后、通用 `get` 前。

### 3.2 `ThinkPHPContainerAdapter`（PSR-11 适配器）

- `get($id)`：`has($id) ? 容器->get($id) : 容器->make($id)` —— 已绑定走原语义，未绑定回退自动解析（对齐 pimple）
- `has($id)`：透传 think 的 `has()`（bound 语义，与 Laravel/pimple 一致）
- 仅绑定到 `Psr\Container\ContainerInterface`，**不**改变 `Artful::getContainer()` 的返回（主容器仍是裸 think 容器，用户可 `instanceof` 判断、取 think 服务）；`HttpClientFactory` 只做显式绑定查询，裸容器够用，实测无影响
- 代码遵循 PHP 8.2 语法基线（composer.json 声明 `"php": ">=8.2"`）：`readonly` 属性（8.1）可用，**禁用 8.3+ 语法**（如 typed class constants）；CI matrix（8.2-8.5）含 8.2 job，可拦截兼容性问题
- 已知边界：用户显式传容器（`Artful::config($config, $container)`）时 `register()` 直接 `setContainer` 返回、不绑 PSR 接口，think 分支下 `artful()/shortcut()` 的管道解析不可用——与 laravel 显式容器路径行为一致（laravel 分支仅在自动检测路径绑 Psr 接口），属既有生态局限，不扩大改动范围

### 3.3 检测与接线

- `$detectApplication` 追加 `'thinkphp' => think\Container::class`（优先级最低：laravel > hyperf > thinkphp，既有设计限制不变）
- `thinkphpApplication(): bool` 补返回类型，绑定时用适配器替换 PR 中的裸容器

### 3.4 测试基建（CI 真实覆盖的关键）

- **thinkphp 任务下让全套餐件真实走 think 容器需要堵住两个污染源**（已验证：`Hyperf\Pimple\ContainerFactory::__invoke()` 末行 `return ApplicationContext::setContainer($container)`——调用工厂即污染全局检测上下文）：
  - `tests/TestCase.php::setUp`：预置 hyperf 上下文的条件追加 `&& !class_exists(think\Container::class)`；default/laravel/hyperf 任务条件不满足、行为不变
  - `tests/ArtfulTest.php::testConfig`：pimple 容器工厂分支（`if (class_exists(ContainerFactory::class))`）追加同一门控——否则该分支执行后全局 ApplicationContext 残留 pimple 容器，检测被 hyperf 抢占，后续既有测试全部跑在 pimple 上（实测复现：不加此门控时 think 任务仅 6 个新测试 + 头部少数测试真实走 think）
- 新增 `tests/ThinkPHPContainerTest.php`（不继承项目 TestCase，think-container 未装时 `markTestSkipped`）：容器检测、标量 set/get、对象 set/get、显式传容器（补偿 testConfig 门控后跳过的能力覆盖）、make 每次新建、**字符串插件管道**（复现 #24 缺陷的回归测试）、shortcut 端到端
- CI：`tester.yml` matrix 增加 `thinkphp`，安装 `composer require "topthink/think-container:^3.0"`

### 3.5 契约核实清单

| 契约 | 状态 |
|---|---|
| think-container 3.x：PSR-11 实现、`get/has/make/delete/bind` 语义、`ClassNotFoundException` 实现 `NotFoundExceptionInterface` | 已验证（读过源码 + 实测） |
| `supports\Pipeline::carry()` 直连容器 `get()` 解析插件 | 已验证（读过源码 + 实测复现/修复后通过；v4.1.0 复核不变，2026-09-02） |
| hyperf/pimple：`get()` 回退 `make()`、存在 `set()`；`ApplicationContext`：`setContainer` 不收 null、`hasContainer()` 存在、属性名 `container` | 已验证（读过源码；hyperf/context 3.1 复核，2026-09-02） |
| 标量/class-string/对象/闭包 四类 `set()` 值的实际使用场景 | 已验证（读过测试与 src 全部 `Artful::set` 调用点 + 实测） |
| PR 补丁可干净应用于当前 main（仅 2 文件 36 行） | 已验证（3-way apply 实测） |
| TP8 完整框架 `think\App extends think\Container` 且构造时 `setInstance($this)`（framework v8.1.4 `src/think/App.php`:40,:189，注意路径为 `src/think/` 非 `src/`） | 已验证（读过上游源码 + GitHub API 复核目录结构；Task 0 spike 运行时复确认） |
| `Hyperf\Pimple\ContainerFactory::__invoke()` 末行 `return ApplicationContext::setContainer($container)`（调用即污染全局检测上下文） | 已验证（读过源码：vendor/hyperf/pimple/src/ContainerFactory.php:32） |
| laravel/hyperf/default 三模式零回归 | 已验证 default（实测 76 过 + 6 skip）；laravel/hyperf 为**推断**（改动均被 `class_exists(think\Container)` / think 分支门控，CI 矩阵最终确认） |

## 4. 推进策略

- **阶段**：单一分支 `feat/thinkphp8-container` 一次成稿 → 本地四项门禁（think 模式全套件 / default 回归 / phpstan / cs-fix）→ commit（注明基于 ken678 PR 重做）→ 经授权后 push + 开 PR（原 #24 建议关闭或由作者 rebase 参考）
- **验证点**：提交前四项门禁全绿即通过；push/PR 属 Git 授权操作，需明确确认后执行
- **回滚**：分支未合入前 main 零影响；回滚 = 删除本地分支。合入后若出问题，revert 对应 commit 即可（改动全部为新增文件/分支内聚，无数据迁移）
- 质量门禁即 CI（本库无线上监控环节）

## 5. 风险与对策

| 风险 | 严重度 | 对策 |
|---|---|---|
| laravel/hyperf 模式回归（TestCase 条件改动波及） | 中 | 条件以 `class_exists(think\Container::class)` 门控，未装 think-container 时逐字节等价于原逻辑；CI 矩阵四任务兜底；实测 default 无回归 |
| `make(…, true)` 对 instance-bound 服务的语义（返回新建而非绑定实例） | 低 | pimple 的 make 同样无视绑定，行为对齐既有生态；核心调用点仅 `make(Pipeline::class)`；写入适配器注释说明 |
| 标量/class-string `set()` 语义再混淆 | 低 | `ThinkPHPContainerTest` 固化四类值的期望行为，回归即红灯 |
| 同时安装 illuminate + think 时检测选错容器 | 低 | 既有优先级限制（laravel 先于 think），维持现状不扩大改动范围；文档建议真实场景二选一 |
| TP8 应用若自行把 PSR 容器绑入裸 think 容器，`!has()` 守卫会跳过适配器，Pipeline 插件解析回到 #24 失败模式 | 低 | 现实中 TP8 App 仅绑 `think\Container::class` 不绑 Psr 接口；守卫语义与 laravel 分支保持一致；留痕待真实场景出现再评估无条件覆盖 |
| TP8 完整框架行为与独立 think-container 不符（契约项为推断） | 中 | Task 0 spike 实测 `think\App` 与容器关系；若与假设不符按设计偏差流程停下确认 |
| coverage.yml 在 default 模式下适配器 0 覆盖，总覆盖率被稀释 | 低 | codecov-action v3 未配置 fail 阈值，预计不红灯；属真实覆盖情况（default 无 think-container），不为此调整 include/exclude |
