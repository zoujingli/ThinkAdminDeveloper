<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */
if (getenv('THINKADMIN_ACCEPTANCE_CONTAINER') !== '1' || getcwd() !== '/work/project') {
    throw new RuntimeException('只能在隔离验收容器中准备依赖');
}
$config = json_decode(file_get_contents('composer.json'), true);
$legacy = PHP_VERSION_ID < 80000;
if ($legacy) {
    unset($config['require-dev']['zoujingli/think-plugs-helper'], $config['require-dev']['friendsofphp/php-cs-fixer']);
}
$config['require']['topthink/framework'] = $legacy ? '^6.1' : '^8.1';
$config['require']['topthink/think-migration'] = $legacy ? '3.0.0' : '^3.1';
foreach ($config['repositories'] as &$repository) {
    if ($repository['type'] === 'path') {
        $repository['options']['symlink'] = true;
    }
}
unset($repository);
file_put_contents('composer.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
// 依赖准备阶段禁用安装器和脚本，真实安装器在后续专用站点中运行。
file_put_contents('.env', "DB_TYPE=sqlite\nCACHE_TYPE=file\n");
// 原 PHPUnit 引导依赖安装后的基础配置；只补齐源码快照缺少的模板文件。
is_dir('config') || mkdir('config');
foreach (glob('plugin/think-plugs-static/stc/config/*.php') as $template) {
    $target = 'config/' . basename($template);
    is_file($target) || copy($template, $target);
}
