<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
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
