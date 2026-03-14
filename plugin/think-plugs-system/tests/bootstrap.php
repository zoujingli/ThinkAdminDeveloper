<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$projectRoot = is_file($packageRoot . '/vendor/autoload.php') ? $packageRoot : dirname($packageRoot, 2);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/vendor/topthink/framework/src/helper.php';

defined('SYSTEM_TEST_PACKAGE_ROOT') || define('SYSTEM_TEST_PACKAGE_ROOT', $packageRoot);
defined('SYSTEM_TEST_PROJECT_ROOT') || define('SYSTEM_TEST_PROJECT_ROOT', $projectRoot);
