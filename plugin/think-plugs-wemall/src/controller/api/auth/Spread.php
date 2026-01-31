<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wemall\controller\api\auth;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallConfigPoster;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\PosterService;
use plugin\wemall\service\UserUpgrade;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 推广用户管理.
 * @class Spread
 */
class Spread extends Auth
{
    /**
     * 获取我推广的用户.
     */
    public function get()
    {
        PluginWemallUserRelation::mQuery(null, function (QueryHelper $query) {
            // 用户搜索查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#keys')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
            // 数据条件查询
            $query->with(['bindUser'])->where(['puid1' => $this->unid])->order('id desc');
            $this->success('获取数据成功！', $query->page(intval(input('page', 1)), false, false, 10));
        });
    }

    /**
     * 临时绑定推荐人.
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
     * 获取我的海报.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws \think\admin\Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function poster()
    {
        $account = $this->account->get();
        $extra = [
            'user.spreat' => '/pages/home/index?from=UNID&fuser=CODE',
            'user.headimg' => $account['user']['headimg'] ?? '',
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
