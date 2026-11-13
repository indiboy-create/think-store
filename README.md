# youvision/think-store

面向 ThinkPHP 8 的商店管理扩展，用于查询主项目中已安装的 `youvision/think-store-*` 发布包，例如 `youvision/think-store-example`。

- **发布包查询**：列出已安装的 `think-store-*` 发布包及其版本、描述、首页路由与安装目录。
- **管理首页**：基于 think-view 渲染的 layui 数据表格页面，内置安装、配置与安全文档。
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
| GET | `/store/think-store/index` | 渲染发布包管理首页（含使用文档） |
| GET | `/store/think-store/packages` | 查询已安装的发布包 |
| GET | `/store/assets/<path>` | 直接输出包内 `theme/assets`，含 layui 与样式 |

路由前缀由 `config/store.php` 的 `route.prefix` 控制，默认 `store`；设为空字符串时地址变为 `/think-store/index`、`/think-store/packages` 等。

## 页面与静态资源

- 模板位于包内 `theme/view`，由包内 `View` 渲染器通过 think-view 的 `Think` 驱动渲染，不需要复制到主项目。
- 静态资源由 `AssetController` 读取 `theme/assets` 后输出，路径经过 `realpath` 校验并拒绝 `..`，避免目录穿越。
- 资源响应带 `Cache-Control: public, max-age=86400`，字体、CSS、JS、图片等类型会返回对应的 `Content-Type`。

```text
/store/think-store/index
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

## 配置

配置文件位于 `config/store.php`，发布后可在主项目中覆盖：

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否自动注册页面、查询与资源路由；关闭后由主项目自行注册 |
| `route.prefix` | `store` | 路由前缀，留空表示不添加前缀 |
| `route.middleware` | `[]` | 应用到整组路由的中间件，可填写类名或别名 |
| `prefix` | `youvision/think-store-` | 发布包包名前缀，只有匹配该前缀的包才会被识别 |
| `exclude` | `[]` | 需要排除的包名，支持 `*`、`?` 通配符 |

`Service` 会自动合并包内默认配置与主项目配置，项目侧配置优先。主项目可通过 `php think vendor:publish` 发布配置文件后修改，也可直接在 `config/store.php` 中覆盖；修改后清除 `runtime` 缓存即可生效。

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
│  │  └─ index.html          # 管理首页模板（含使用文档）
│  └─ assets/
│     ├─ css/index.css       # 管理页自定义样式
│     └─ layui/              # layui 本地资源
├─ tests/
│  └─ verify.php             # 独立验证脚本
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

## 相关项目

ThinkPHP-PanGu 是一个面向 ThinkPHP 的开源工程索引，收录组件、框架、服务与发布工具：<https://github.com/indiboy-create/ThinkPHP-PanGu/>。