# Think Store 管理页

本目录是 `youvision/think-store` 的静态资源，部署到主项目的 `public/store/think-store`。

- `index.html`：发布包管理首页，使用 layui 数据表格展示已安装的 `youvision/think-store-*` 发布包，并提供单个同步与批量同步操作
- `intro.html`：使用说明，包含功能说明、部署流程、接口配置与安全建议
- `theme/layui/`：layui 框架目录，保持原样，不新增或修改文件
- `theme/css/index.css`：管理页自定义样式

## 接口

- `GET /store/packages`：查询已安装的发布包
- `POST /store/packages/sync`：重新同步静态资源

页面通过 `<main data-api="/store">` 指定接口前缀。修改 `config/store.php` 中的 `route.prefix` 后，请同步调整该属性。

## 部署

在主项目 `composer.json` 中把 think-static 的资源目录指向本包：

```json
{
  "extra": {
    "think-static": {
      "project_dir": "vendor/youvision/think-store/project"
    }
  }
}
```

执行 `composer dump-autoload` 后访问 `/store/think-store/index.html`。发布包的静态资源由管理页的同步操作部署，不依赖本目录。