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
$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/vendor/topthink/framework/src/helper.php';
require $projectRoot . '/tests/support/TestSystemContext.php';
require $projectRoot . '/tests/support/SqliteIntegrationTestCase.php';

defined('TEST_PROJECT_ROOT') || define('TEST_PROJECT_ROOT', $projectRoot);

defined('HELPER_TEST_PACKAGE_ROOT') || define('HELPER_TEST_PACKAGE_ROOT', $projectRoot . '/plugin/think-plugs-helper');
defined('HELPER_TEST_PROJECT_ROOT') || define('HELPER_TEST_PROJECT_ROOT', $projectRoot);

defined('SYSTEM_TEST_PACKAGE_ROOT') || define('SYSTEM_TEST_PACKAGE_ROOT', $projectRoot . '/plugin/think-plugs-system');
defined('SYSTEM_TEST_PROJECT_ROOT') || define('SYSTEM_TEST_PROJECT_ROOT', $projectRoot);

defined('WORKER_TEST_PACKAGE_ROOT') || define('WORKER_TEST_PACKAGE_ROOT', $projectRoot . '/plugin/think-plugs-worker');
defined('WORKER_TEST_PROJECT_ROOT') || define('WORKER_TEST_PROJECT_ROOT', $projectRoot);
