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

if (!function_exists('test_reset_model_makers')) {
    function test_reset_model_makers(): void
    {
        $reflection = new ReflectionProperty(\think\Model::class, '_maker');
        $reflection->setAccessible(true);
        $reflection->setValue([]);
    }
}
