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
$packageRoot = dirname(__DIR__);
$projectRoot = is_file($packageRoot . '/vendor/autoload.php') ? $packageRoot : dirname($packageRoot, 2);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/vendor/topthink/framework/src/helper.php';
if (is_file($projectRoot . '/tests/support/TestSystemContext.php')) {
    require_once $projectRoot . '/tests/support/TestSystemContext.php';
}
if (is_file($projectRoot . '/tests/support/SqliteIntegrationTestCase.php')) {
    require_once $projectRoot . '/tests/support/SqliteIntegrationTestCase.php';
}

defined('WORKER_TEST_PACKAGE_ROOT') || define('WORKER_TEST_PACKAGE_ROOT', $packageRoot);
defined('WORKER_TEST_PROJECT_ROOT') || define('WORKER_TEST_PROJECT_ROOT', $projectRoot);
