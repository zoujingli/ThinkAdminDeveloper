<?php

declare(strict_types=1);
/**
 * // +----------------------------------------------------------------------
 * // | Payment Plugin for ThinkAdmin
 * // +----------------------------------------------------------------------
 * // | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * // +----------------------------------------------------------------------
 * // | 官方网站: https://thinkadmin.top
 * // +----------------------------------------------------------------------
 * // | 开源协议 ( https://mit-license.org )
 * // | 免责声明 ( https://thinkadmin.top/disclaimer )
 * // | 会员免费 ( https://thinkadmin.top/vip-introduce )
 * // +----------------------------------------------------------------------
 * // | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * // | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * // +----------------------------------------------------------------------
 */
if (is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
    return false;
}
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
require $_SERVER['SCRIPT_FILENAME'];
