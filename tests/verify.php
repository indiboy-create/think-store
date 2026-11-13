<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$failures    = [];

$check = static function (bool $ok, string $message) use (&$failures): void {
    if (!$ok) {
        $failures[] = $message;
    }
};

$autoload = $packageRoot . '/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
}

if (!class_exists(\Youvision\ThinkStore\Store::class)) {
    require_once $packageRoot . '/src/Store.php';
}

$composer = json_decode((string) file_get_contents($packageRoot . '/composer.json'), true);

$check(is_array($composer), 'composer.json 应为有效 JSON');
$check(($composer['name'] ?? '') === 'youvision/think-store', '包名应为 youvision/think-store');
$check(($composer['type'] ?? '') === 'library', 'composer.json 的 type 应为 library');
$check(
    is_string($composer['version'] ?? null)
    && preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $composer['version']) === 1,
    'composer.json 应声明合法的 version 字段'
);
$check(
    ($composer['require']['topthink/framework'] ?? '') === '^8.0',
    'composer.json 应依赖 topthink/framework ^8.0'
);
$check(
    ($composer['require']['topthink/think-view'] ?? '') === '^2.0',
    'composer.json 应依赖 topthink/think-view ^2.0'
);
$check(
    ($composer['autoload']['psr-4']['Youvision\\ThinkStore\\'] ?? '') === 'src/',
    'composer.json 应配置 Youvision\\ThinkStore 的 PSR-4 自动加载'
);
$check(
    in_array('Youvision\\ThinkStore\\Service', (array) ($composer['extra']['think']['services'] ?? []), true),
    'composer.json 应注册 ThinkPHP 服务'
);
$check(
    ($composer['extra']['think']['config']['store'] ?? '') === 'config/store.php',
    'composer.json 应声明 store 配置'
);
$check(
    ($composer['scripts']['test'] ?? '') === '@php tests/verify.php',
    'composer.json 应提供 test 脚本'
);
$check(
    !in_array('static', (array) ($composer['keywords'] ?? []), true),
    'composer.json 的关键字不应包含 static'
);

$configFile = $packageRoot . '/config/store.php';
$check(is_file($configFile), 'config/store.php 应存在');

$config = require $configFile;

$check(($config['route']['enabled'] ?? null) === true, '默认应启用内置路由');
$check(($config['route']['prefix'] ?? '') === 'store', '默认路由前缀应为 store');
$check(($config['route']['middleware'] ?? null) === [], '默认路由中间件应为空数组');
$check(($config['prefix'] ?? '') === 'youvision/think-store-', '默认包前缀应为 youvision/think-store-');
$check(($config['exclude'] ?? null) === [], '默认排除列表应为空数组');

foreach (['root', 'project_dir', 'manifest', 'force', 'skip_dot_files'] as $key) {
    $check(!array_key_exists($key, $config), 'config/store.php 不应包含静态部署配置 ' . $key);
}

foreach ([
    'src/Store.php',
    'src/StoreController.php',
    'src/AssetController.php',
    'src/Service.php',
    'route/store.php',
    'config/store.php',
    'theme/view/index.html',
    'theme/view/intro.html',
    'theme/readme.md',
    'theme/assets/css/index.css',
    'theme/assets/js/store.js',
    'theme/assets/layui/layui.js',
    'theme/assets/layui/css/layui.css',
    'README.md',
    'LICENSE',
] as $file) {
    $check(is_file($packageRoot . '/' . $file), $file . ' 应存在');
}

$check(!is_dir($packageRoot . '/project'), 'project 目录应已迁移为 theme');
$check(!is_dir($packageRoot . '/theme/theme'), 'theme 目录内不应再嵌套 theme');

$indexSource = (string) file_get_contents($packageRoot . '/theme/view/index.html');

$check(str_contains($indexSource, 'id="st-package-table"'), '管理首页模板应包含发布包表格容器');
$check(str_contains($indexSource, 'id="st-json-wrap"'), '管理首页模板应包含数据面板');
$check(str_contains($indexSource, 'data-api="{$api}"'), '管理首页模板应使用视图变量声明接口前缀');
$check(str_contains($indexSource, '{$api}/assets/layui/css/layui.css'), '管理首页模板应引用 assets 路由的 layui 样式');
$check(str_contains($indexSource, '{$api}/assets/layui/layui.js'), '管理首页模板应引用 assets 路由的 layui 脚本');
$check(str_contains($indexSource, '{$api}/assets/js/store.js'), '管理首页模板应引用页面脚本');
$check(str_contains($indexSource, 'window.STORE_CONFIG'), '管理首页模板应注入接口配置');
$check(str_contains($indexSource, '{$api}/think-store/intro'), '管理首页模板应链接使用说明路由');
$check(str_contains($indexSource, 'v{$version}'), '管理首页模板应使用视图变量输出版本号');
$check(str_contains($indexSource, 'class="st-hero"'), '管理首页模板应包含 hero 区域');
$check(!str_contains($indexSource, '/packages/sync'), '管理首页模板不应包含同步接口调用');
$check(!str_contains($indexSource, 'cdn'), '管理首页模板不应使用 CDN 资源');

$scriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/store.js');

$check(str_contains($scriptSource, "layui.use(['table', 'layer']"), '页面脚本应加载 layui table 与 layer');
$check(str_contains($scriptSource, 'table.render'), '页面脚本应渲染数据表格');
$check(str_contains($scriptSource, "api + '/packages'"), '页面脚本应请求查询接口');
$check(str_contains($scriptSource, 'fetch('), '页面脚本应使用 fetch 读取数据');
$check(str_contains($scriptSource, 'st-json-toggle'), '页面脚本应支持展开原始 JSON');
$check(str_contains($scriptSource, 'st-nav-toggle'), '页面脚本应支持移动端导航');

$introSource = (string) file_get_contents($packageRoot . '/theme/view/intro.html');

$check(str_contains($introSource, '使用说明'), '使用说明模板应存在');
$check(str_contains($introSource, '/store/packages'), '使用说明模板应描述查询接口');
$check(str_contains($introSource, '/store/think-store'), '使用说明模板应描述页面路由');
$check(str_contains($introSource, '/store/assets/'), '使用说明模板应描述 assets 路由');
$check(str_contains($introSource, '{$api}/assets/layui/css/layui.css'), '使用说明模板应引用 assets 路由的 layui 样式');
$check(str_contains($introSource, '{$api}/assets/js/store.js'), '使用说明模板应引用页面脚本');
$check(str_contains($introSource, 'v{$version}'), '使用说明模板应使用视图变量输出版本号');
$check(str_contains($introSource, 'class="st-config"'), '使用说明模板应包含配置说明');
$check(!str_contains($introSource, 'packages/sync'), '使用说明模板不应描述同步接口');

$styleSource = (string) file_get_contents($packageRoot . '/theme/assets/css/index.css');

$check(str_contains($styleSource, '--st-brand'), '样式应定义商店主题变量');
$check(str_contains($styleSource, '.st-hero'), '样式应包含 hero 区域');
$check(str_contains($styleSource, '.st-nav-toggle'), '样式应包含移动端导航');
$check(str_contains($styleSource, '@media (max-width: 820px)'), '样式应包含响应式断点');
$check(!str_contains($styleSource, 'cdn'), '样式不应依赖 CDN 资源');

$routeSource = (string) file_get_contents($packageRoot . '/route/store.php');

$check(str_contains($routeSource, "Route::get('packages'"), '路由应注册 GET packages');
$check(str_contains($routeSource, "Route::get('think-store'"), '路由应注册 GET think-store');
$check(str_contains($routeSource, "Route::get('think-store/intro'"), '路由应注册 GET think-store/intro');
$check(str_contains($routeSource, "Route::get('assets/<path>'"), '路由应注册 GET assets/<path>');
$check(str_contains($routeSource, 'AssetController'), '资源路由应使用 AssetController');
$check(str_contains($routeSource, "pattern(['path' => '.*'])"), '资源路由应匹配多级路径');
$check(str_contains($routeSource, 'middleware($middleware)'), '路由应应用中间件配置');
$check(!str_contains($routeSource, 'packages/sync'), '路由不应注册同步接口');

if (!class_exists(\Youvision\ThinkStore\AssetController::class)) {
    require_once $packageRoot . '/src/AssetController.php';
}

$check(\Youvision\ThinkStore\AssetController::resolve('layui/css/layui.css') !== null, '应能解析包内静态资源');
$check(\Youvision\ThinkStore\AssetController::resolve('../composer.json') === null, '应拒绝目录穿越');
$check(\Youvision\ThinkStore\AssetController::resolve('..') === null, '应拒绝上级目录');
$check(\Youvision\ThinkStore\AssetController::resolve('') === null, '空路径应返回 null');
$check(\Youvision\ThinkStore\AssetController::resolve('missing.css') === null, '不存在的资源应返回 null');
$check(\Youvision\ThinkStore\AssetController::mime('a.css') === 'text/css; charset=utf-8', 'CSS MIME 应正确');
$check(\Youvision\ThinkStore\AssetController::mime('a.js') === 'application/javascript; charset=utf-8', 'JS MIME 应正确');
$check(\Youvision\ThinkStore\AssetController::mime('a.woff2') === 'font/woff2', '字体 MIME 应正确');
$check(\Youvision\ThinkStore\AssetController::mime('a.unknown') === 'application/octet-stream', '未知类型应回退为 octet-stream');

$serviceSource = (string) file_get_contents($packageRoot . '/src/Service.php');

$check(str_contains($serviceSource, 'loadRoutesFrom'), '服务应在 boot 阶段加载路由');
$check(str_contains($serviceSource, "bind('store'"), '服务应绑定 store 容器');
$check(str_contains($serviceSource, 'getRootPath'), '服务应使用主项目根目录');

$controllerSource = (string) file_get_contents($packageRoot . '/src/StoreController.php');

$check(str_contains($controllerSource, 'use think\\view\\driver\\Think'), '控制器应使用 think-view 模板驱动');
$check(str_contains($controllerSource, 'function index'), '控制器应提供 index 页面');
$check(str_contains($controllerSource, 'function intro'), '控制器应提供 intro 页面');
$check(str_contains($controllerSource, 'function packages'), '控制器应提供 packages 接口');
$check(str_contains($controllerSource, 'view_path'), '控制器应指定模板目录');
$check(str_contains($controllerSource, 'fetch($template'), '控制器应渲染模板');
$check(str_contains($controllerSource, 'ob_start'), '控制器应捕获模板输出');
$check(str_contains($controllerSource, 'function sync') === false, '控制器不应提供 sync 接口');

$storeSource = (string) file_get_contents($packageRoot . '/src/Store.php');

$check(!str_contains($storeSource, 'function sync'), 'Store 不应提供同步方法');

$tmp = sys_get_temp_dir() . '/think-store-' . bin2hex(random_bytes(4));
mkdir($tmp . '/vendor/composer', 0755, true);
$tmp = rtrim(str_replace('\\', '/', (string) realpath($tmp)), '/');

$write = static function (string $file, string $content): void {
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }

    file_put_contents($file, $content);
};

$makePackage = static function (string $name, array $composer) use ($tmp, $write): void {
    $dir = $tmp . '/vendor/' . $name;

    $write($dir . '/composer.json', (string) json_encode(
        ['name' => $name] + $composer,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    ));
};

$makePackage('youvision/think-store-example', ['description' => '示例发布包']);
$makePackage('youvision/think-store-broken', ['description' => '损坏的发布包']);
$makePackage('youvision/think-store-api', ['description' => '需要排除的发布包']);
$makePackage('acme/store-other', ['description' => '无关的 Composer 包']);
$makePackage('youvision/think-store', ['description' => '管理扩展自身']);

$write($tmp . '/vendor/youvision/think-store-empty/composer.json', '{invalid json');

$installed = [
    'packages' => [
        ['name' => 'youvision/think-store', 'version' => '1.0.0', 'pretty_version' => '1.0.0', 'install-path' => '../youvision/think-store'],
        ['name' => 'youvision/think-store-example', 'version' => '1.1.0.0', 'pretty_version' => '1.1.0', 'install-path' => '../youvision/think-store-example'],
        ['name' => 'youvision/think-store-broken', 'version' => '1.0.0.0', 'pretty_version' => '1.0.0', 'install-path' => '../youvision/think-store-broken'],
        ['name' => 'youvision/think-store-empty', 'version' => '1.0.0.0', 'pretty_version' => '1.0.0', 'install-path' => '../youvision/think-store-empty'],
        ['name' => 'youvision/think-store-api', 'version' => '1.0.0.0', 'pretty_version' => '1.0.0', 'install-path' => '../youvision/think-store-api'],
        ['name' => 'acme/store-other', 'version' => '1.0.0.0', 'pretty_version' => '1.0.0', 'install-path' => '../acme/store-other'],
    ],
];

$write($tmp . '/vendor/composer/installed.json', (string) json_encode($installed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$write($tmp . '/composer.json', (string) json_encode(['name' => 'demo/app'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if (!class_exists(\Youvision\ThinkStore\Store::class)) {
    foreach ($failures as $failure) {
        echo 'FAIL: ' . $failure . "\n";
    }

    exit(1);
}

$storeConfig = [
    'prefix'  => 'youvision/think-store-',
    'exclude' => ['youvision/think-store-api'],
];

$store    = new \Youvision\ThinkStore\Store($storeConfig, $tmp);
$packages = $store->packages();
$byName   = array_column($packages, null, 'package');

$check(count($packages) === 3, '应识别 3 个发布包，实际 ' . count($packages));
$check(isset($byName['youvision/think-store-example']), '应识别 youvision/think-store-example');
$check(isset($byName['youvision/think-store-broken']), '应识别损坏的发布包');
$check(isset($byName['youvision/think-store-empty']), '应识别缺少描述的发布包');
$check(!isset($byName['youvision/think-store']), '管理扩展自身不应列入包列表');
$check(!isset($byName['youvision/think-store-api']), 'exclude 应排除 youvision/think-store-api');
$check(!isset($byName['acme/store-other']), '无关包不应列入包列表');
$check(array_column($packages, 'package') === [
    'youvision/think-store-broken',
    'youvision/think-store-empty',
    'youvision/think-store-example',
], '包列表应按包名排序');

$example = $byName['youvision/think-store-example'] ?? [];

$check(($example['version'] ?? '') === '1.1.0', '示例包版本应为 1.1.0');
$check(($example['description'] ?? '') === '示例发布包', '示例包描述应读取自包内 composer.json');
$check(
    ($example['path'] ?? '') === $tmp . '/vendor/youvision/think-store-example',
    '示例包安装路径应正确'
);

$empty = $byName['youvision/think-store-empty'] ?? [];

$check(($empty['description'] ?? 'x') === '', '无效 composer.json 的描述应为空字符串');

$check(method_exists($store, 'sync') === false, 'Store 不应保留 sync 方法');
$check($store->projectRoot() === $tmp, 'projectRoot 应返回主项目根目录');

$prefixStore    = new \Youvision\ThinkStore\Store(['prefix' => 'acme/'], $tmp);
$prefixPackages = $prefixStore->packages();

$check(count($prefixPackages) === 1, '自定义前缀应只识别 1 个包');
$check(($prefixPackages[0]['package'] ?? '') === 'acme/store-other', '自定义前缀应识别 acme/store-other');

$noneStore = new \Youvision\ThinkStore\Store([
    'prefix'  => 'youvision/think-store-',
    'exclude' => ['youvision/think-store-*'],
], $tmp);

$check($noneStore->packages() === [], '通配符 exclude 应排除全部发布包');

$remove = static function (string $dir) use (&$remove): void {
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
};

$remove($tmp);

if ($failures === []) {
    echo "PASS\n";
    exit(0);
}

foreach ($failures as $failure) {
    echo 'FAIL: ' . $failure . "\n";
}

exit(1);