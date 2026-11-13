# Think Store 主题资源

本目录是 `youvision/think-store` 的视图与静态资源：

- `view/index.html`：发布包管理首页模板，由 think-view 渲染
- `view/intro.html`：使用说明页模板，由 think-view 渲染
- `assets/layui/`：layui 框架本地资源，保持原样，不新增或修改文件
- `assets/css/index.css`：管理页自定义样式

## 模板变量

| 变量 | 说明 |
| --- | --- |
| `$api` | 路由前缀，例如 `/store`；由 `config/store.php` 的 `route.prefix` 计算 |
| `$version` | 当前包版本，读取自 `composer.json` |

## 静态资源

模板与静态资源均无需发布到宿主 `public`：

- 模板由 think-view 直接读取 `theme/view`
- 静态资源由 `GET /store/assets/<path>` 路由读取 `theme/assets` 输出

路由由 `route/store.php` 注册，`AssetController` 会校验路径并拒绝目录穿越。

访问地址：

```text
/store/think-store
/store/think-store/intro
/store/assets/layui/css/layui.css
```