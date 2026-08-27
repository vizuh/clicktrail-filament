[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**vizuh/filament-clicktrail**

面向 Filament 3 的 ClickTrail 设置、只读归因记录、抑制诊断和事件映射界面。

</div>

[![CI](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## 目录

- [为什么](#为什么)
- [安装](#安装)
- [快速上手](#快速上手)
- [事件映射](#事件映射)
- [归因记录](#归因记录)
- [设置与配置](#设置与配置)
- [诊断](#诊断)
- [同意契约](#同意契约)
- [差异对比](#差异对比)
- [测试](#测试)
- [许可证](#许可证)

## 为什么

当运营人员需要在现有 Filament 面板中检查 ClickTrail 记录时，请使用此插件。它显示已存储的首次触点和末次触点记录、同意快照及抑制诊断。归因记录保持只读，因为该状态由采集管道管理。

需要 PHP >= 8.1、Laravel 12.60+ 或 13.10+、Filament 3.3.55+ 以及 [`clicktrail/php-sdk`](https://github.com/vizuh/clicktrail-php)。

## 安装

```bash
composer require vizuh/filament-clicktrail
php artisan vendor:publish --tag=clicktrail-filament
php artisan migrate
```

## 快速上手

在任意 panel 上注册插件：

```php
use ClickTrail\Filament\ClickTrailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(ClickTrailPlugin::make());
}
// 面板随即出现 "ClickTrail" 导航分组：设置页、只读的 Attribution Records
// 表格（每 60 秒自动刷新）以及诊断统计 widget。无需其他接线。
```

## 事件映射

`Support\EventMap` 将 Eloquent 模型事件映射为规范的 `Stable::EVENT_*` 名称，让 lead 和 order 模型直接使用 ClickTrail 的统一词汇，不需要胶水代码：

```php
use App\Models\Lead;
use ClickTrail\Filament\Support\EventMap;

EventMap::resolve(new Lead(), 'created');  // 'lead_created'；规范事件名
EventMap::resolve(new Lead(), 'deleted');  // 'refund'；删除映射为退款
```

常见模型 basename 有默认映射（`lead`、`appointment`、`sale`、`order`）。可通过配置键 `clicktrail-filament.event_map` 按模型扩展或覆盖；以 `refund` 结尾的 basename 会解析为 `refund`，包含 `attended` 的事件会解析为 `booking_completed`。

## 归因记录

`AttributionRecordResource` 表格展示已存储的首触/末触记录，带渠道过滤器和紧凑的同意快照列：

```php
TextColumn::make('first_channel')->badge();          // paid_search | organic_search | ...
SelectFilter::make('first_channel');                 // 按规范渠道名过滤
TextColumn::make('consent_snapshot_summary');        // "analytics_storage=granted, ad_user_data=denied"
```

没有创建/编辑/删除页面，也没有对应路由：`canCreate()`、`canEdit()`、`canDelete()` 全部返回 false。表格每 60 秒轮询一次。

## 设置与配置

设置页编辑发布出来的 `clicktrail-filament.php` 配置文件：

```php
'site_id'           => env('CLICKTRAIL_SITE_ID', ''),   // 由采集端下发
'endpoint'          => env('CLICKTRAIL_ENDPOINT', 'https://collect.clicktrail.dev/v1/events/batch'),
'consent_resolver'  => env('CLICKTRAIL_CONSENT_RESOLVER', ''), // 为空 => NullConsentResolver
'capability_gates'  => ['analytics' => true, 'advertising' => true, 'ad_user_data' => true],
```

某个能力开关关闭表示该用途不需要 CMP 同意（gate-toggle 语义）。注意：当前保存操作是在运行时写回 config repository；持久化存储将随进行中的 settings-storage 工作落地。

## 诊断

统计组件读取 `clicktrail_diagnostics` 计数器；每个抑制原因一条指标，非零时显示为警告色：

```php
Stat::make('adUserDataUnknownAtCapture', '12') // 该原因下被抑制的发送次数
    ->description('最近出现于 2 小时前')
```

没有任何抑制记录时会显示绿色的 `Suppressions = 0`。队列深度显示 `-`，直到排队投递任务上线（推迟到完成线上验证）。

## 同意契约

本插件是同意的消费方，不是 CMP。它通过 Laravel adapter 的 `ConsentResolverInterface` 读取规范化的 `ConsentSnapshot`（granted / denied / unknown / not_applicable）。所有未解析的信号一律按**拒绝**处理：被抑制的发送会记入诊断表，而不是发出。

```php
if (! ConsentBehavior::can($snapshot, 'ad_user_data')) {
    // 发送被抑制；原因写入 clicktrail_diagnostics，
    // 立即显示在 Diagnostics 组件中
}
```

## 差异对比

| | 本插件 | 通用后台 CRUD |
|---|---|---|
| 归因记录 | 只读展示管道持有的状态 | 可编辑行容易造成静默数据污染 |
| 同意 | unknown = denied，在发送之前强制执行 | 往往只是展示用的标志位 |
| 事件 | 与所有 ClickTrail adapter 共享的 `Stable::EVENT_*` 规范名称 | 各项目自造的事件名 |

它本身不采集 touch，也不批量投递；这由 Laravel adapter 管道负责；本插件只是让结果可见、可审计。

## 测试

目前尚未附带 PHPUnit 测试套件。CI 在 PHP 8.1–8.3 下对所有 PHP 文件做 lint：

```bash
composer install --prefer-dist --no-interaction || echo "no deps"
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l   # 成功时干净退出
```

## 许可证

MIT.
