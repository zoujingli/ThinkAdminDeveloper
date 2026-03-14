<?php

declare(strict_types=1);

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
