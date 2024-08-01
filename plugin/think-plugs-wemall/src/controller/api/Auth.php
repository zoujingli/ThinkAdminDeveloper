<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\account\controller\api\Auth as AccountAuth;
use plugin\wemall\model\PluginWemallUserRelation;
use think\exception\HttpResponseException;

/**
 * 基础授权控制器
 * @class Auth
 * @package plugin\wemall\controller\api
 */
abstract class Auth extends AccountAuth
{
    /**
     * 用户关系
     * @var PluginWemallUserRelation
     */
    protected $relation;

    /**
     * 等级序号
     * @var integer
     */
    protected $levelCode;

    /**
     * 控制器初始化
     * @return void
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
     * 初始化当前用户
     * @return static
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function withUserRelation(): Auth
    {
        $this->relation = PluginWemallUserRelation::withInit($this->unid);
        $this->levelCode = intval($this->relation->getAttr('level_code'));
        return $this;
    }
}