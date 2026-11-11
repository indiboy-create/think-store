## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# youvision/think-store

ThinkPHP 8 商店管理扩展。依赖 [`youvision/think-static`](https://github.com/indiboy-create/think-static) 提供静态资源部署能力，用于管理主项目中已安装的 `youvision/think-store-*` 发布包（例如 `youvision/think-store-example`）。

提供三项核心能力：

- **查询**：列出已安装的 `think-store-*` 发布包及其版本、部署目标、同步状态。
- **重新同步**：批量或按包重新部署各发布包 `project` 目录下的静态资源。
- **管理首页**：内置 layui 数据表格页面，可视化查看发布包列表并执行同步操作。

## 环境要求

- PHP >= 8.0
- Composer >= 2.2
- ThinkPHP 8 主项目
- `youvision/think-static`（随本包自动安装）

## 安装

```bash
composer require youvision/think-store
```

`youvision/think-static` 是 Composer 插件，Composer 2.2 及以上版本默认拦截插件安装。主项目需要提前允许：

```json
{
    "config": {
        "allow-plugins": {
            "youvision/think-static": true
        }
    }
}
```

ThinkPHP 8 项目通常在 `composer.json` 的 `post-autoload-dump` 中执行 `service:discover` 与 `vendor:publish`，安装后会自动完成服务注册与配置发布。若主项目未配置该脚本，请手动执行：

```bash
php think service:discover
php think vendor:publish
```

### 部署管理首页

`project` 目录是管理首页的静态资源，部署目标为 `public/store/think-store`。在主项目 `composer.json` 中把 think-static 的资源目录指向本包：

```json
{
    "extra": {
        "think-static": {
            "project_dir": "vendor/youvision/think-store/project"
        }
    }
}
```

执行 `composer dump-autoload` 后访问：

```text
/store/think-store/index.html
```

管理首页使用 layui 数据表格展示已安装发布包，支持单包同步与批量同步。若主项目自身也有 `project` 目录，两者不能同时使用同一个 `project_dir`，可改为手动调用 think-static 完成管理页部署。

## 发布包识别规则

满足以下条件的 Composer 包会被识别为商店发布包：

1. 包名以配置项 `prefix` 开头，默认 `youvision/think-store-`；
2. 不在配置项 `exclude` 排除列表中（支持 `*`、`?` 通配符）；
3. 包已安装且存在安装目录。

`youvision/think-store` 自身不以 `youvision/think-store-` 开头，因此不会被列入包列表。每个发布包通过包内 `project/project.json` 声明静态资源部署方式，规则与 `youvision/think-static` 完全一致。

## 查询接口

```http
GET /store/packages
```

响应示例：

```json
{
  "total": 1,
  "packages": [
    {
      "package": "youvision/think-store-example",
      "version": "1.1.0",
      "description": "ThinkPHP 8 静态站点实例包",
      "path": "D:/project/vendor/youvision/think-store-example",
      "target": "public/store/store-example",
      "items": 8,
      "has_project": true,
      "synced": true,
      "synced_at": "2026-11-10 12:00:00",
      "error": ""
    }
  ]
}
```

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `package` | string | Composer 包名 |
| `version` | string | 已安装版本 |
| `description` | string | 包内 `composer.json` 的 `description` |
| `path` | string | 包安装目录绝对路径 |
| `target` | string | 静态资源部署目标，相对主项目根目录；无法解析时为空字符串 |
| `items` | int | `project.json` 中声明的部署项数量 |
| `has_project` | bool | `project/project.json` 是否有效 |
| `synced` | bool | 目标目录是否已存在 |
| `synced_at` | string | 目标目录最后更新时间；未同步时为空字符串 |
| `error` | string | 解析失败原因；正常时为空字符串 |

单个包的 `project.json` 无效时不会中断列表查询，错误会写入该包的 `error` 字段。

## 同步接口

```http
POST /store/packages/sync
```

请求参数：

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `packages` | string / array | 否 | 包名、逗号分隔的包名或包名数组；省略时同步全部已识别发布包 |
| `force` | bool | 否 | 是否覆盖已存在的同名文件；省略时使用配置项 `force` |

```bash
# 同步全部发布包
curl -X POST http://127.0.0.1:8000/store/packages/sync

# 同步指定包
curl -X POST http://127.0.0.1:8000/store/packages/sync \
    -d "packages=youvision/think-store-example"

# 只补齐缺失文件，不覆盖已有文件
curl -X POST http://127.0.0.1:8000/store/packages/sync \
    -d "packages=youvision/think-store-example" \
    -d "force=false"
```

响应示例：

```json
{
  "packages": [
    {
      "package": "youvision/think-store-example",
      "target": "public/store/store-example",
      "items": {
        "theme": {
          "target": "public/store/store-example/theme",
          "copied": 8,
          "skipped": 0,
          "failed": 0
        }
      },
      "copied": 9,
      "skipped": 0,
      "failed": 0,
      "success": true,
      "error": ""
    }
  ],
  "total": 1,
  "copied": 9,
  "skipped": 0,
  "failed": 0,
  "errors": 0,
  "force": true
}
```

| 字段 | 说明 |
| --- | --- |
| `packages` | 每个包的部署明细，包含 `target`、`items`、`copied`、`skipped`、`failed`、`success`、`error` |
| `total` | 本次处理的包数量 |
| `copied` | 成功复制的文件总数 |
| `skipped` | 因已存在而跳过的文件总数 |
| `failed` | 复制失败的文件总数 |
| `errors` | 部署失败的包数量 |
| `force` | 本次是否覆盖同名文件 |

单个包部署失败不会中断其他包，失败原因记录在该包的 `error` 字段。请求的包未安装或不受管理时返回 HTTP 400：

```json
{
  "message": "未安装或不受管理的 think-store 发布包：youvision/think-store-unknown"
}
```

## PHP 调用

服务注册后，可通过容器获取 `Store`：

```php
use Youvision\ThinkStore\Store;

/** @var Store $store */
$store = app(Store::class);
// 也可使用别名：app('store')

// 查询已安装的发布包
$packages = $store->packages();

// 同步全部发布包
$result = $store->sync();

// 同步指定包
$result = $store->sync('youvision/think-store-example');
$result = $store->sync(['youvision/think-store-example']);

// 指定包，且只补齐缺失文件
$result = $store->sync('youvision/think-store-example', false);
```

`Store` 构造函数同样支持脱离容器使用：

```php
$store = new Store((array) config('store'), app()->getRootPath());
```

## 配置 config/store.php

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否自动注册查询与同步接口；关闭后可自行定义路由 |
| `route.prefix` | `store` | 路由前缀，留空表示不添加前缀；接口地址为 `/{prefix}/packages`、`/{prefix}/packages/sync` |
| `route.middleware` | `[]` | 应用到路由上的中间件，可填写类名或别名 |
| `prefix` | `youvision/think-store-` | 发布包包名前缀，只有匹配该前缀的包才会被识别 |
| `exclude` | `[]` | 需要排除的包名，支持 `*`、`?` 通配符 |
| `root` | `public/store` | 静态资源部署根目录，相对主项目根目录；主项目 `extra.think-static.root` 优先 |
| `project_dir` | `project` | 发布包内的资源目录，相对各包安装目录 |
| `manifest` | `project.json` | 资源目录内的部署配置文件名；主项目 `extra.think-static.manifest` 优先 |
| `force` | `true` | 重新同步时是否覆盖已存在的同名文件 |
| `skip_dot_files` | `true` | 是否跳过以点开头的隐藏文件；主项目 `extra.think-static.skip_dot_files` 优先 |

`Service` 会自动合并包内默认配置与主项目配置。主项目可通过 `php think vendor:publish` 发布配置文件后修改，也可直接在 `config/store.php` 中覆盖；修改后清除 `runtime` 缓存即可生效。

## 与 think-static 的关系

- `think-static` 在 Composer 安装、更新或执行 `composer dump-autoload` 时，自动部署主项目自身的 `project` 目录。
- 本包自身的 `project` 目录同样由 think-static 部署；需要在主项目 `extra.think-static.project_dir` 中指向 `vendor/youvision/think-store/project`。
- `think-store` 不参与 Composer 自动部署，只在调用 `sync()` 或同步接口时，逐个扫描并部署已安装的 `think-store-*` 发布包。
- 部署根目录、配置文件名和隐藏文件策略优先沿用主项目 `composer.json` 中的 `extra.think-static`，避免两套配置不一致：

```json
{
    "extra": {
        "think-static": {
            "root": "public/store",
            "manifest": "project.json",
            "skip_dot_files": true
        }
    }
}
```

- `project_dir` 由 `config/store.php` 独立控制，因为它相对每个发布包的安装目录，而不是主项目根目录。
- 每个包最终的部署位置为 `{root}/{project.json 中的 path}`，例如 `public/store/store-example`。

## 目录结构

```text
think-store/
├─ composer.json
├─ config/
│  └─ store.php              # 商店配置
├─ route/
│  └─ store.php              # 查询与同步路由
├─ src/
│  ├─ Service.php            # ThinkPHP 服务注册
│  ├─ Store.php              # 包查询与静态资源同步
│  └─ StoreController.php    # HTTP 接口
├─ project/
│  ├─ project.json           # 管理页部署配置
│  ├─ index.html             # 发布包管理首页（layui 数据表格）
│  ├─ intro.html             # 使用说明
│  ├─ readme.md
│  └─ theme/
│     ├─ css/index.css       # 管理页自定义样式
│     └─ layui/              # layui 框架资源
├─ tests/
│  └─ verify.php
├─ LICENSE
└─ README.md
```

## 验证

```bash
composer test
```

或直接运行：

```bash
php tests/verify.php
```

验证脚本会检查 `composer.json` 的依赖、服务注册与配置声明，校验默认配置、路由与服务源码，校验管理页的 `project.json`、layui 数据表格与接口调用，并在临时目录中模拟主项目与多个 `think-store-*` 包，覆盖包识别、排除规则、损坏包容错、目标目录计算、全量与指定包同步、`force` 行为、主项目部署根目录覆盖以及管理页自身部署等场景。

## 许可证

MIT，详见 `LICENSE`。