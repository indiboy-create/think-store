# think-store

> **ThinkPHP 应用商店 —— 让优秀的扩展被看见，让每一次安装都值得信任。**

`PHP >= 8.0` · `ThinkPHP 8` · `Layui` · `MySQL 8` · `Composer`

---

> **项目状态**：早期设计与初始化阶段。本文描述 think-store 的定位、能力边界与目标架构，具体接口、目录和命令以实际代码为准。

## 这是什么

think-store 是一个基于 **ThinkPHP 8** 构建的应用商店，面向 ThinkPHP 生态提供应用发现、版本管理、兼容性校验与一键分发能力。

它不只做一个“下载站”，而是想把散落在 GitHub、Packagist、论坛、网盘里的 ThinkPHP 组件、模块、主题和整站脚手架，收拢成一个**可搜索、可比较、可信任、可安装**的统一入口。

一句话概括：

> 像逛应用商店一样逛 ThinkPHP 生态。

## 为什么需要它

ThinkPHP 生态并不缺优秀的扩展，缺的是一个稳定的“最后一公里”：

- **入口分散**：包在 Packagist，说明在 GitHub，教程在论坛，下载在网盘。
- **兼容性模糊**：是否支持 ThinkPHP 8？PHP 8.2 能不能跑？依赖会不会冲突？
- **安装门槛高**：复制配置、改目录、建数据表、处理静态资源，每一步都可能劝退。
- **质量参差**：缺少统一的审核、评分、维护状态和版本记录。

think-store 试图把这些问题收敛成三件事：

1. **聚合**：统一收录应用、版本、分类、标签与作者信息。
2. **验证**：展示 PHP / ThinkPHP 版本矩阵、依赖树、安全审计与维护状态。
3. **分发**：给出一行安装命令，或直接生成可部署的应用包。

## 核心能力

| 模块 | 能力 |
| --- | --- |
| 应用市场 | 首页推荐、分类导航、排行榜、关键词搜索、标签筛选、应用详情 |
| 版本中心 | 版本历史、更新日志、依赖树、PHP / ThinkPHP 兼容矩阵 |
| 开发者中心 | 应用上架、版本发布、元数据同步、下载与评分统计 |
| 管理中心 | 应用审核、分类与标签维护、推荐位、敏感词、操作日志 |
| 开放接口 | RESTful API、Webhook 同步、CLI 命令 |
| 多站点 | 基于 ThinkPHP 多应用模式，按站点、栏目、内容隔离数据 |

### 支持的应用类型

| 类型 | 说明 | 典型形态 |
| --- | --- | --- |
| `component` | 可复用的 Composer 组件 | 身份认证、支付、存储、二维码 |
| `module` | 可直接挂载的多应用模块 | 商城、CMS、工单、会员中心 |
| `theme` | 前台模板与静态资源包 | 企业站、博客、门户主题 |
| `skeleton` | 整站脚手架 | 后台管理系统、SaaS 起始项目 |
| `tool` | 开发与运维工具 | CLI 扩展、代码生成器、部署脚本 |

## 技术架构

```text
┌─────────────────────────────────────────────┐
│                  接入层                      │
│   Web 门户  ·  开发者中心  ·  管理后台  ·  API │
└─────────────────────┬───────────────────────┘
                      │
┌─────────────────────▼───────────────────────┐
│              ThinkPHP 8 多应用层             │
│  portal  ·  developer  ·  admin  ·  api     │
└─────────────────────┬───────────────────────┘
                      │
┌─────────────────────▼───────────────────────┐
│                领域服务层                    │
│  应用  ·  版本  ·  分类  ·  审核  ·  统计     │
└─────────────────────┬───────────────────────┘
                      │
┌─────────────────────▼───────────────────────┐
│              基础设施与数据层                │
│  Model / MySQL  ·  Cache  ·  Queue  ·  Sync  │
└─────────────────────────────────────────────┘
```

- **后端**：ThinkPHP 8，遵循多应用模式，模块边界清晰。
- **数据**：优先使用 Model（ORM）组织查询，表名统一使用英文复数。
- **前端**：Layui，静态资源统一放在 `public/static/ui/{library}`。
- **同步**：从 Packagist / GitHub 拉取元数据，支持定时任务与 Webhook。
- **部署**：应用包统一落到可配置的 `dist_path`，不硬编码路径。

## 快速开始

### 环境要求

- PHP >= 8.0（推荐 8.2）
- Composer 2.x
- MySQL >= 5.7（推荐 8.0）或 PostgreSQL
- 可选的 Redis，用于缓存与队列

### 安装

```bash
git clone https://github.com/indiboy-create/think-store.git
cd think-store

composer install

cp .env.example .env
# 编辑 .env，配置数据库连接与应用地址

php think migrate:run
php think store:apps:sync
```

### 启动

```bash
php think run
```

浏览器访问 `http://127.0.0.1:8000`，即可进入应用市场首页。

## 目录结构

```text
think-store/
├─ app/
│  ├─ admin/              # 管理后台
│  ├─ api/                # 开放接口
│  ├─ common/             # 公共模型、服务与门面
│  ├─ developer/          # 开发者中心
│  └─ portal/             # 应用市场门户
├─ config/
│  └─ store.php           # 商店配置
├─ public/
│  ├─ index.php
│  └─ static/
│     ├─ dist/            # 应用包部署目录（可配置）
│     └─ ui/
│        └─ layui/        # 前端组件库
├─ route/
│  └─ app.php
├─ composer.json
└─ README.md
```

## 配置说明

核心配置位于 `config/store.php`，所有路径与开关均可在部署时覆盖：

```php
<?php

return [
    // 商店基础信息
    'site_name' => 'ThinkPHP 应用商店',
    'site_url'  => 'https://store.example.com',
    'site_logo' => '/static/ui/store/logo.svg',

    // 应用包部署根目录，相对 public，支持自定义，避免硬编码
    'dist_path' => 'static/dist',

    // 元数据同步
    'sync' => [
        'driver'   => 'packagist',      // 数据源驱动：packagist / github / local
        'endpoint' => 'https://packagist.org',
        'token'    => '',               // 私有源或提高限额时使用
        'timeout'  => 15,               // 单次请求超时时间（秒）
        'interval' => 3600,             // 自动同步间隔（秒），0 表示关闭
    ],

    // 应用包上传
    'upload' => [
        'max_size'  => 20971520,        // 单个应用包最大体积（字节），默认 20MB
        'allow_ext' => ['zip', 'tar', 'gz'],
        'path'      => 'static/uploads',// 上传文件存放目录，相对 public
    ],

    // 缓存
    'cache' => [
        'store' => 'file',              // 缓存驱动：file / redis
        'ttl'   => 300,                 // 列表与详情缓存时间（秒）
    ],

    // 应用审核
    'review' => [
        'auto'      => false,           // 是否开启自动审核
        'sensitive' => [],              // 敏感词列表
    ],

    // 多站点数据隔离
    'multi_site' => [
        'enable'     => true,           // 是否启用多站点模式
        'default_id' => 1,              // 默认站点 ID
    ],
];
```

## 命令行

```bash
# 同步应用元数据（全部或指定包）
php think store:apps:sync
php think store:apps:sync --package=indiboy/think-example

# 审核应用
php think store:apps:review

# 生成下载与评分统计
php think store:apps:stats

# 安装指定应用到本地项目
php think store:install indiboy/think-example

# 检查依赖安全与版本兼容性
php think store:apps:audit
```

## 开放接口

| 方法 | 路径 | 说明 |
| --- | --- | --- |
| `GET` | `/api/apps` | 应用列表，支持分类、标签、关键词与排序 |
| `GET` | `/api/apps/{name}` | 应用详情 |
| `GET` | `/api/apps/{name}/versions` | 版本历史与兼容信息 |
| `GET` | `/api/categories` | 分类树 |
| `GET` | `/api/tags` | 标签列表 |
| `GET` | `/api/rankings` | 下载榜、评分榜、更新榜 |
| `POST` | `/api/webhooks/packagist` | 接收 Packagist 更新事件 |

接口统一返回 JSON，字段使用英文小写下划线命名，例如 `package_name`、`thinkphp_version`、`download_count`。

## 应用包规范

每个应用通过根目录的 `manifest.json` 描述自身能力与边界：

```json
{
  "name": "indiboy/think-example",
  "title": "示例应用",
  "type": "module",
  "version": "1.0.0",
  "description": "一句话描述应用能力",
  "php": ">=8.0",
  "thinkphp": "^8.0",
  "dependencies": {},
  "permissions": ["database", "cache"],
  "author": {
    "name": "indiboy",
    "homepage": "https://github.com/indiboy-create"
  }
}
```

字段约定：

- `name`：Composer 包名，全小写加连字符；
- `type`：应用类型，取值见上文；
- `php` / `thinkphp`：兼容版本约束；
- `permissions`：应用运行所需的系统能力，安装前向用户明示。

## 数据模型

| 表 | 说明 |
| --- | --- |
| `apps` | 应用主表，记录包名、标题、类型、作者与状态 |
| `app_versions` | 版本表，记录版本号、更新时间、依赖与兼容范围 |
| `categories` | 分类表，支持树形结构 |
| `tags` | 标签表 |
| `developers` | 开发者与团队信息 |
| `downloads` | 下载记录，用于统计与趋势分析 |
| `reviews` | 评分与评论 |
| `sites` | 站点表，用于多应用模式下的数据隔离 |

### 应用生命周期

```text
draft → pending → published → offline → archived
```

- `draft`：开发者草稿；
- `pending`：等待审核；
- `published`：已上架，可被搜索与安装；
- `offline`：主动下架，保留历史数据；
- `archived`：归档，不再接收新版本。

## 设计原则

- **配置优先**：路径、数据源、缓存、审核策略全部可配置。
- **显式兼容**：版本兼容信息必须可查，不做模糊承诺。
- **最小依赖**：保持安装包精简，只引入必要组件。
- **数据隔离**：多站点场景下，站点、栏目、内容各归其位。
- **可审计**：上架、审核、下架、安装都有记录。

## 路线图

- [x] 项目定位与信息架构设计
- [ ] 应用与版本数据模型
- [ ] 门户首页、分类与搜索
- [ ] 应用详情与版本兼容矩阵
- [ ] 开发者中心与上架流程
- [ ] 管理后台与审核流
- [ ] RESTful API 与 Webhook 同步
- [ ] CLI 安装与安全审计
- [ ] 多站点数据隔离
- [ ] 静态化与 SEO 优化

## 参与贡献

欢迎提交 Issue 与 Pull Request。参与前请先确认：

1. 代码风格与现有模块保持一致；
2. 新增配置项需附带完整的中文说明；
3. 数据表、路由与命令命名使用英文复数；
4. 提交前确保相关检查通过。

## 许可证

本项目采用 MIT 许可证，详见后续补充的 `LICENSE` 文件。