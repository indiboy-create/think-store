## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
- 仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
- 在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# youvision/think-store

ThinkPHP 8 商店管理扩展，用于管理主项目中已安装的 `youvision/think-store-*` 发布包（例如 `youvision/think-store-example`）。

提供三项核心能力：

- **发布包查询**：列出已安装的 `think-store-*` 发布包及其版本、描述与安装目录。
- **管理首页**：基于 think-view 渲染的 layui 卡片式页面，可视化查看发布包列表与首页路由。
- **包内静态资源**：通过内置 assets 路由直接输出 `theme/assets`，无需发布到 `public`。

## 环境要求

- PHP >= 8.0
- ThinkPHP 8 主项目
- `topthink/think-view`（随本包自动安装）

## 安装

```bash
composer require youvision/think-store
```

ThinkPHP 8 项目通常在 `composer.json` 的 `post-autoload-dump` 中执行 `service:discover` 与 `vendor:publish`，安装后会自动完成服务注册与配置发布。若主项目未配置该脚本，请手动执行：

```bash
php think service:discover
php think vendor:publish
```

路由由 `Youvision\ThinkStore\Service` 在 `boot()` 阶段加载，宿主项目不需要复制路由文件。

## 路由

| 方法 | 地址 | 说明 |
| --- | --- | --- |
| GET | `/store/think-store/index` | 渲染发布包管理首页 |
| GET | `/store/think-store/intro` | 渲染使用说明页 |
| GET | `/store/think-store/packages` | 查询已安装的发布包 |
| GET | `/store/assets/<path>` | 直接输出包内 `theme/assets`，含 layui 与样式 |

路由前缀由 `config/store.php` 的 `route.prefix` 控制，默认 `store`；设为空字符串时地址变为 `/think-store/index`、`/think-store/packages` 等。

## 页面与静态资源

- 模板位于包内 `theme/view`，由包内 `View` 渲染器通过 think-view 的 `Think` 驱动渲染，不需要复制到主项目。
- 静态资源由 `AssetController` 读取 `theme/assets` 后输出，路径经过 `realpath` 校验并拒绝 `..`，避免目录穿越。
- 资源响应带 `Cache-Control: public, max-age=86400`，字体、CSS、JS、图片等类型会返回对应的 `Content-Type`。

访问地址：

```text
/store/think-store/index
/store/think-store/intro
/store/assets/layui/css/layui.css
```

模板内的接口地址与资源地址会跟随 `route.prefix` 自动变化。

## 查询接口

```http
GET /store/think-store/packages
```

响应示例：

```json
{
  "total": 1,
  "packages": [
    {
      "package": "youvision/think-store-example",
      "version": "1.1.0",
      "description": "ThinkPHP 8 商店实例包",
      "home": "/store/example",
      "path": "D:/project/vendor/youvision/think-store-example"
    }
  ]
}
```

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `package` | string | Composer 包名 |
| `version` | string | 已安装版本 |
| `description` | string | 包内 `composer.json` 的 `description` |
| `home` | string | 发布包首页路由，取自包内 `composer.json` 的 `extra.store.home` |
| `path` | string | 包安装目录绝对路径 |

命中异常时列表仍会正常返回；无法读取描述的包，`description` 为空字符串。

## 发布包首页路由

发布包在自身 `composer.json` 的 `extra.store.home` 中声明首页地址，`packages()` 会将其作为 `home` 字段返回：

```json
{
  "name": "youvision/think-store-example",
  "extra": {
    "store": {
      "home": "/store/example"
    }
  }
}
```

- 值可以是应用内路径（`/store/example`、`store/example`）或完整 URL（`https://example.com`）。
- 未以 `/` 或 `http(s)://` 开头的相对路径会自动补前导 `/`。
- 未声明或声明无效的包，`home` 为空字符串。

## PHP 调用

服务注册后，可通过容器获取 `Store`：

```php
use Youvision\ThinkStore\Store;

/** @var Store $store */
$store = app(Store::class);
// 也可使用别名：app('store')

// 查询已安装的发布包
$packages = $store->packages();
```

`Store` 构造函数支持脱离容器使用：

```php
$store = new Store((array) config('store'), app()->getRootPath());
```

## 配置 config/store.php

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否自动注册页面、查询与资源路由；关闭后由主项目自行注册 |
| `route.prefix` | `store` | 路由前缀，留空表示不添加前缀 |
| `route.middleware` | `[]` | 应用到整组路由的中间件，可填写类名或别名 |
| `prefix` | `youvision/think-store-` | 发布包包名前缀，只有匹配该前缀的包才会被识别 |
| `exclude` | `[]` | 需要排除的包名，支持 `*`、`?` 通配符 |

`Service` 会自动合并包内默认配置与主项目配置，项目侧配置优先。主项目可通过 `php think vendor:publish` 发布配置文件后修改，也可直接在 `config/store.php` 中覆盖；修改后清除 `runtime` 缓存即可生效。

## 目录结构

```text
think-store/
├─ composer.json
├─ config/
│  └─ store.php              # 商店配置
├─ route/
│  └─ store.php              # 页面、查询与资源路由
├─ src/
│  ├─ Service.php            # ThinkPHP 服务注册
│  ├─ Store.php              # 发布包查询
│  ├─ View.php               # 包内模板渲染器
│  ├─ StoreController.php    # 页面渲染与查询接口
│  └─ AssetController.php    # 包内静态资源输出
├─ theme/
│  ├─ view/
│  │  ├─ index.html          # 发布包管理首页模板
│  │  └─ intro.html          # 使用说明页模板
│  └─ assets/
│     ├─ css/index.css       # 管理页自定义样式
│     └─ layui/              # layui 本地资源
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

验证脚本会检查 `composer.json` 的依赖与服务注册、默认配置、路由、控制器与渲染器源码、think-view 模板、assets 路由与目录穿越防护，并在临时目录中模拟主项目与多个 `think-store-*` 包，覆盖包识别、排序、排除规则、通配符匹配、自定义前缀与失效 `composer.json` 的容错场景。

## 许可证

MIT，详见 `LICENSE`。