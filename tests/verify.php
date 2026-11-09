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

if (!class_exists(\Youvision\ThinkStatic\StaticAssets::class)) {
    $candidates = [
        (string) getenv('THINK_STATIC_PATH'),
        $packageRoot . '/../think-static',
        $packageRoot . '/vendor/youvision/think-static',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === '' || !is_file($candidate . '/src/StaticAssets.php')) {
            continue;
        }

        require_once $candidate . '/src/Project.php';
        require_once $candidate . '/src/Scanner.php';
        require_once $candidate . '/src/Publisher.php';
        require_once $candidate . '/src/StaticAssets.php';

        break;
    }
}

$check(
    class_exists(\Youvision\ThinkStatic\StaticAssets::class),
    '未找到 youvision/think-static，请先执行 composer install 或设置 THINK_STATIC_PATH'
);

if (!class_exists(\Youvision\ThinkStore\Store::class)) {
    require_once $packageRoot . '/src/Store.php';
}

$composer = json_decode((string) file_get_contents($packageRoot . '/composer.json'), true);

$check(is_array($composer), 'composer.json 应为有效 JSON');
$check(($composer['name'] ?? '') === 'youvision/think-store', '包名应为 youvision/think-store');
$check(($composer['type'] ?? '') === 'library', 'composer.json 的 type 应为 library');
$check(
    ($composer['require']['youvision/think-static'] ?? '') === '^1.0',
    'composer.json 应依赖 youvision/think-static ^1.0'
);
$check(
    ($composer['require']['topthink/framework'] ?? '') === '^8.0',
    'composer.json 应依赖 topthink/framework ^8.0'
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

$configFile = $packageRoot . '/config/store.php';
$check(is_file($configFile), 'config/store.php 应存在');

$config = require $configFile;

$check(($config['route']['enabled'] ?? null) === true, '默认应启用内置路由');
$check(($config['route']['prefix'] ?? '') === 'store', '默认路由前缀应为 store');
$check(($config['route']['middleware'] ?? null) === [], '默认路由中间件应为空数组');
$check(($config['prefix'] ?? '') === 'youvision/think-store-', '默认包前缀应为 youvision/think-store-');
$check(($config['exclude'] ?? null) === [], '默认排除列表应为空数组');
$check(($config['root'] ?? '') === 'public/store', '默认部署根目录应为 public/store');
$check(($config['project_dir'] ?? '') === 'project', '默认资源目录应为 project');
$check(($config['manifest'] ?? '') === 'project.json', '默认部署配置应为 project.json');
$check(($config['force'] ?? null) === true, '默认应覆盖同名文件');
$check(($config['skip_dot_files'] ?? null) === true, '默认应跳过隐藏文件');

foreach ([
    'src/Store.php',
    'src/StoreController.php',
    'src/Service.php',
    'route/store.php',
    'config/store.php',
    'project/project.json',
    'project/index.html',
    'project/intro.html',
    'project/readme.md',
    'project/theme/css/index.css',
    'project/theme/layui/layui.js',
    'project/theme/layui/css/layui.css',
    'README.md',
    'LICENSE',
] as $file) {
    $check(is_file($packageRoot . '/' . $file), $file . ' 应存在');
}

$manifestFile = $packageRoot . '/project/project.json';
$manifest     = json_decode((string) file_get_contents($manifestFile), true);

$check(is_array($manifest), 'project/project.json 应为有效 JSON');
$check(($manifest['path'] ?? '') === 'think-store', 'project.json 的 path 应为 think-store');
$check(
    ($manifest['map'] ?? []) === [
        'theme'      => 'theme',
        'index.html' => 'index.html',
        'intro.html' => 'intro.html',
        'readme.md'  => 'readme.md',
    ],
    'project.json 的 map 应声明管理页、样式与 layui 资源'
);
$check(($manifest['exclude'] ?? []) === ['**/*.map'], 'project.json 的 exclude 应排除 **/*.map');

$indexSource = (string) file_get_contents($packageRoot . '/project/index.html');

$check(str_contains($indexSource, "layui.use(['table'"), '管理首页应加载 layui table 模块');
$check(str_contains($indexSource, 'table.render'), '管理首页应渲染数据表格');
$check(str_contains($indexSource, "table.on('tool(package-table)'"), '管理首页应处理表格行操作');
$check(str_contains($indexSource, "api + '/packages'"), '管理首页应请求查询接口');
$check(str_contains($indexSource, "api + '/packages/sync'"), '管理首页应请求同步接口');
$check(str_contains($indexSource, 'data-api="/store"'), '管理首页应声明接口前缀');
$check(str_contains($indexSource, 'id="sync-all"'), '管理首页应提供全量同步按钮');
$check(str_contains($indexSource, 'name="force"'), '管理首页应提供覆盖开关');
$check(str_contains($indexSource, 'id="package-table"'), '管理首页应包含数据表格容器');

$introSource = (string) file_get_contents($packageRoot . '/project/intro.html');

$check(str_contains($introSource, '使用说明'), '使用说明页应存在');
$check(str_contains($introSource, '/store/packages/sync'), '使用说明页应描述同步接口');
$check(str_contains($introSource, 'extra.think-static.project_dir'), '使用说明页应描述部署配置');

$routeSource = (string) file_get_contents($packageRoot . '/route/store.php');

$check(str_contains($routeSource, "Route::get('packages'"), '路由应注册 GET packages');
$check(str_contains($routeSource, "Route::post('packages/sync'"), '路由应注册 POST packages/sync');
$check(str_contains($routeSource, 'middleware($middleware)'), '路由应应用中间件配置');

$serviceSource = (string) file_get_contents($packageRoot . '/src/Service.php');

$check(str_contains($serviceSource, 'loadRoutesFrom'), '服务应在 boot 阶段加载路由');
$check(str_contains($serviceSource, "bind('store'"), '服务应绑定 store 容器');
$check(str_contains($serviceSource, 'getRootPath'), '服务应使用主项目根目录');

$controllerSource = (string) file_get_contents($packageRoot . '/src/StoreController.php');

$check(str_contains($controllerSource, "param('packages')"), '控制器应读取 packages 参数');
$check(str_contains($controllerSource, 'FILTER_VALIDATE_BOOLEAN'), '控制器应解析 force 参数');
$check(str_contains($controllerSource, 'InvalidArgumentException'), '控制器应处理非法包名');

$tmp = sys_get_temp_dir() . '/think-store-' . bin2hex(random_bytes(4));
mkdir($tmp . '/vendor/composer', 0755, true);
$tmp = rtrim(str_replace('\\', '/', (string) realpath($tmp)), '/');

$write = static function (string $file, string $content): void {
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }

    file_put_contents($file, $content);
};

$makePackage = static function (
    string $name,
    array $composer,
    string $manifest,
    array $files = []
) use ($tmp, $write): void {
    $dir = $tmp . '/vendor/' . $name;

    $write($dir . '/composer.json', (string) json_encode(
        ['name' => $name] + $composer,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    ));
    $write($dir . '/project/project.json', $manifest);

    foreach ($files as $file => $content) {
        $write($dir . '/project/' . $file, $content);
    }
};

$copy = static function (string $source, string $target) use (&$copy): void {
    if (is_dir($source)) {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $copy($source . '/' . $entry, $target . '/' . $entry);
        }

        return;
    }

    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }

    copy($source, $target);
};

$copy($packageRoot . '/project', $tmp . '/project');

$selfAssets  = new \Youvision\ThinkStatic\StaticAssets($tmp);
$selfProject = $selfAssets->scan($tmp);

$check($selfProject->targetPath === 'think-store', '管理页部署目标应为 think-store');
$check(count($selfProject->items) === 4, '管理页应扫描到 4 个部署项');

$selfResult = $selfAssets->publish($tmp, true);

$check(($selfResult['target'] ?? '') === 'public/store/think-store', '管理页应部署到 public/store/think-store');
$check(($selfResult['copied'] ?? 0) === 11, '管理页应部署 11 个文件，实际 ' . ($selfResult['copied'] ?? 0));
$check(is_file($tmp . '/public/store/think-store/index.html'), '管理页 index.html 应部署成功');
$check(is_file($tmp . '/public/store/think-store/intro.html'), '管理页 intro.html 应部署成功');
$check(is_file($tmp . '/public/store/think-store/readme.md'), '管理页 readme.md 应部署成功');
$check(is_file($tmp . '/public/store/think-store/theme/css/index.css'), '管理页样式应部署成功');
$check(is_file($tmp . '/public/store/think-store/theme/layui/layui.js'), 'layui 脚本应部署成功');
$check(is_file($tmp . '/public/store/think-store/theme/layui/css/layui.css'), 'layui 样式应部署成功');
$check(!is_file($tmp . '/public/store/think-store/project.json'), 'project.json 不应部署');

$deployedIndex = (string) file_get_contents($tmp . '/public/store/think-store/index.html');

$check(str_contains($deployedIndex, 'table.render'), '部署后的管理首页内容应完整');

$makePackage(
    'youvision/think-store-example',
    ['description' => '示例发布包'],
    (string) json_encode([
        'path'   => 'store-example',
        'map'    => ['theme' => 'theme', 'index.html' => 'index.html'],
        'exclude' => ['**/*.map'],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    [
        'theme/css/index.css'      => 'body{}',
        'theme/css/index.css.map'  => '{}',
        'theme/.hidden'            => 'hidden',
        'index.html'               => '<h1>Example</h1>',
    ]
);

$makePackage(
    'youvision/think-store-broken',
    ['description' => '损坏的发布包'],
    '{invalid json'
);

$makePackage(
    'youvision/think-store-empty',
    ['description' => '缺少部署项的发布包'],
    (string) json_encode(['path' => 'store-empty', 'map' => ['missing' => 'missing']]),
    ['index.html' => '<h1>Empty</h1>']
);

$makePackage(
    'youvision/think-store-api',
    ['description' => '需要排除的发布包'],
    (string) json_encode(['path' => 'store-api']),
    ['index.html' => '<h1>API</h1>']
);

$makePackage(
    'acme/store-other',
    ['description' => '无关的 Composer 包'],
    (string) json_encode(['path' => 'other']),
    ['index.html' => '<h1>Other</h1>']
);

$makePackage(
    'youvision/think-store',
    ['description' => '管理扩展自身'],
    (string) json_encode(['path' => 'store']),
    ['index.html' => '<h1>Store</h1>']
);

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
$write($tmp . '/composer.json', (string) json_encode([
    'extra' => ['think-static' => ['root' => 'public/store']],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if (!class_exists(\Youvision\ThinkStore\Store::class)) {
    foreach ($failures as $failure) {
        echo 'FAIL: ' . $failure . "\n";
    }

    exit(1);
}

$storeConfig = [
    'prefix'         => 'youvision/think-store-',
    'exclude'        => ['youvision/think-store-api'],
    'root'           => 'public/store',
    'project_dir'    => 'project',
    'manifest'       => 'project.json',
    'force'          => true,
    'skip_dot_files' => true,
];

$store    = new \Youvision\ThinkStore\Store($storeConfig, $tmp);
$packages = $store->packages();
$byName   = array_column($packages, null, 'package');

$check(count($packages) === 3, '应识别 3 个发布包，实际 ' . count($packages));
$check(isset($byName['youvision/think-store-example']), '应识别 youvision/think-store-example');
$check(isset($byName['youvision/think-store-broken']), '应识别损坏的发布包');
$check(isset($byName['youvision/think-store-empty']), '应识别缺少部署项的发布包');
$check(!isset($byName['youvision/think-store']), '管理扩展自身不应列入包列表');
$check(!isset($byName['youvision/think-store-api']), 'exclude 应排除 youvision/think-store-api');
$check(!isset($byName['acme/store-other']), '无关包不应列入包列表');

$example = $byName['youvision/think-store-example'] ?? [];

$check(($example['version'] ?? '') === '1.1.0', '示例包版本应为 1.1.0');
$check(($example['description'] ?? '') === '示例发布包', '示例包描述应读取自包内 composer.json');
$check(
    ($example['path'] ?? '') === $tmp . '/vendor/youvision/think-store-example',
    '示例包安装路径应正确'
);
$check(($example['target'] ?? '') === 'public/store/store-example', '示例包目标目录应为 public/store/store-example');
$check(($example['items'] ?? 0) === 2, '示例包应扫描到 2 个部署项');
$check(($example['has_project'] ?? false) === true, '示例包应存在有效 project');
$check(($example['synced'] ?? true) === false, '示例包首次查询时应未同步');
$check(($example['error'] ?? null) === '', '示例包不应有错误');

$broken = $byName['youvision/think-store-broken'] ?? [];

$check(($broken['has_project'] ?? true) === false, '损坏包的 project 应无效');
$check(($broken['target'] ?? 'x') === '', '损坏包不应解析出目标目录');
$check(str_contains((string) ($broken['error'] ?? ''), 'JSON'), '损坏包应报告 JSON 错误');

$empty = $byName['youvision/think-store-empty'] ?? [];

$check(($empty['has_project'] ?? true) === false, '缺少部署项的包 project 应无效');
$check(str_contains((string) ($empty['error'] ?? ''), '部署项不存在'), '缺少部署项的包应报告部署项错误');

$result = $store->sync();
$synced = array_column((array) ($result['packages'] ?? []), null, 'package');

$check(($result['total'] ?? 0) === 3, '同步应处理 3 个发布包，实际 ' . ($result['total'] ?? 0));
$check(($result['copied'] ?? 0) === 2, '同步应复制 2 个文件，实际 ' . ($result['copied'] ?? 0));
$check(($result['failed'] ?? -1) === 0, '同步不应有文件复制失败');
$check(($result['errors'] ?? 0) === 2, '同步应记录 2 个失败包，实际 ' . ($result['errors'] ?? 0));
$check(($result['force'] ?? false) === true, '默认同步应覆盖同名文件');
$check(($synced['youvision/think-store-example']['success'] ?? false) === true, '示例包同步应成功');
$check(($synced['youvision/think-store-broken']['success'] ?? true) === false, '损坏包同步应失败');
$check(($synced['youvision/think-store-empty']['success'] ?? true) === false, '缺少部署项的包同步应失败');

$target = $tmp . '/public/store/store-example';

$check(is_file($target . '/index.html'), 'index.html 应部署成功');
$check(is_file($target . '/theme/css/index.css'), 'theme/css/index.css 应部署成功');
$check(!is_file($target . '/theme/css/index.css.map'), '*.map 应被 exclude 排除');
$check(!is_file($target . '/theme/.hidden'), '隐藏文件应被跳过');
$check(!is_file($target . '/project.json'), 'project.json 不应部署');
$check(
    str_contains((string) file_get_contents($target . '/index.html'), 'Example'),
    '部署后的 index.html 内容应完整'
);

$packages = array_column($store->packages(), null, 'package');
$example  = $packages['youvision/think-store-example'] ?? [];

$check(($example['synced'] ?? false) === true, '同步后示例包应标记为已同步');
$check(($example['synced_at'] ?? '') !== '', '同步后应记录目标目录更新时间');

$again = $store->sync(null, false);

$check(($again['copied'] ?? -1) === 0, 'force=false 时不应复制文件');
$check(($again['skipped'] ?? 0) === 2, 'force=false 时应跳过 2 个文件，实际 ' . ($again['skipped'] ?? 0));
$check(($again['force'] ?? true) === false, 'force=false 应反映到同步结果');

$one = $store->sync('youvision/think-store-example');

$check(($one['total'] ?? 0) === 1, '指定包同步应只处理 1 个包');
$check(($one['copied'] ?? 0) === 2, '默认 force=true 时应覆盖 2 个文件');

$two = $store->sync('youvision/think-store-example, youvision/think-store-empty');

$check(($two['total'] ?? 0) === 2, '逗号分隔包名应处理 2 个包');
$check(($two['errors'] ?? 0) === 1, '逗号分隔同步应记录 1 个失败包');

$array = $store->sync(['youvision/think-store-example']);

$check(($array['total'] ?? 0) === 1, '数组形式的包名应只处理 1 个包');

$rejected = false;

try {
    $store->sync('youvision/think-store');
} catch (InvalidArgumentException $e) {
    $rejected = true;
}

$check($rejected, '同步未安装或不受管理的包应抛出 InvalidArgumentException');

$rejected = false;

try {
    $store->sync('youvision/think-store-missing');
} catch (InvalidArgumentException $e) {
    $rejected = true;
}

$check($rejected, '同步不存在的包应抛出 InvalidArgumentException');

$write($tmp . '/composer.json', (string) json_encode([
    'extra' => ['think-static' => ['root' => 'public/assets']],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$extraStore    = new \Youvision\ThinkStore\Store($storeConfig, $tmp);
$extraPackages = array_column($extraStore->packages(), null, 'package');

$check(
    ($extraPackages['youvision/think-store-example']['target'] ?? '') === 'public/assets/store-example',
    '主项目 extra.think-static.root 应覆盖默认部署根目录'
);

$extraResult = $extraStore->sync('youvision/think-store-example');

$check(($extraResult['copied'] ?? 0) === 2, '覆盖部署根目录后应复制 2 个文件');
$check(
    is_file($tmp . '/public/assets/store-example/index.html'),
    '覆盖部署根目录后文件应落到 public/assets'
);

$softConfig           = $storeConfig;
$softConfig['force']  = false;
$softStore            = new \Youvision\ThinkStore\Store($softConfig, $tmp);
$softResult           = $softStore->sync('youvision/think-store-example');

$check(($softResult['force'] ?? true) === false, 'force 配置应生效');
$check(($softResult['skipped'] ?? 0) === 2, 'force 配置为 false 时应跳过 2 个文件');

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