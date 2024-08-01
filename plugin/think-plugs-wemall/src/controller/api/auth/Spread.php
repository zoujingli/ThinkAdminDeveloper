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

namespace plugin\wemall\controller\api\auth;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallConfigPoster;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\PosterService;
use plugin\wemall\service\UserUpgrade;
use think\admin\helper\QueryHelper;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 推广用户管理
 * @class Spread
 * @package plugin\wemall\controller\api\auth
 */
class Spread extends Auth
{
    /**
     * 获取我推广的用户
     * @return void
     */
    public function get()
    {
        PluginWemallUserRelation::mQuery(null, function (QueryHelper $query) {
            // 用户搜索查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            // 数据条件查询
            $query->with(['bindUser'])->where(['puid1' => $this->unid])->order('id desc');
            $this->success('获取数据成功！', $query->page(intval(input('page', 1)), false, false, 10));
        });
    }

    /**
     * 临时绑定推荐人
     * @return void
     */
    public function bind()
    {
        try {
            $input = $this->_vali(['from.require' => '推荐人不能为空！']);
            $relation = UserUpgrade::bindAgent($this->relation, intval($input['from']), 0);
            $this->success('绑定推荐人成功！', $relation->toArray());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取我的海报
     * @return void
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function poster()
    {
        $account = $this->account->get();
        $extra = [
            'user.spreat'   => "/pages/home/index?from=UNID&fuser=CODE",
            'user.headimg'  => $account['user']['headimg'] ?? '',
            'user.nickname' => $account['user']['nickname'] ?? '',
            'user.rolename' => $this->relation->getAttr('level_name'),
        ];
        $items = PluginWemallConfigPoster::items($this->levelCode, $this->type);
        foreach ($items as &$item) {
            $item['image'] = PosterService::create($item['image'], $item['content'], $extra);
            unset($item['content']);
        }
        $this->success('获取海报成功！', $items);
    }
}