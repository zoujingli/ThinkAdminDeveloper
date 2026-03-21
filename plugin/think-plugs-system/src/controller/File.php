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

namespace plugin\system\controller;

use plugin\storage\controller\File as StorageFileController;
use think\admin\Controller;

/**
 * 文件管理兼容入口.
 * @class File
 */
class File extends Controller
{
    /**
     * 文件管理列表兼容入口.
     * @auth true
     */
    public function index()
    {
        $this->storage()->index();
    }

    /**
     * 编辑文件兼容入口.
     * @auth true
     */
    public function edit()
    {
        $this->storage()->edit();
    }

    /**
     * 删除文件兼容入口.
     * @auth true
     */
    public function remove()
    {
        $this->storage()->remove();
    }

    /**
     * 清理重复文件兼容入口.
     * @auth true
     */
    public function distinct()
    {
        $this->storage()->distinct();
    }

    private function storage(): StorageFileController
    {
        return app(StorageFileController::class);
    }
}
