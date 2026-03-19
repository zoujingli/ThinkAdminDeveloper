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

namespace plugin\wemall\controller\api;

use plugin\account\controller\api\Auth as AccountAuth;
use plugin\wemall\model\PluginWemallUserRelation;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 基础授权控制器.
 * @class Auth
 */
abstract class Auth extends AccountAuth
{
    /**
     * 用户关系.
     * @var PluginWemallUserRelation
     */
    protected $relation;

    /**
     * 等级序号.
     * @var int
     */
    protected $levelCode;

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        try {
            parent::initialize();
            $this->checkUserStatus()->withUserRelation();
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 初始化当前用户.
     * @return static
     * @throws \think\admin\Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function withUserRelation(): Auth
    {
        $this->relation = PluginWemallUserRelation::withInit($this->unid);
        $this->levelCode = intval($this->relation->getAttr('level_code'));
        return $this;
    }
}
