<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaSalesUser;
use plugin\wuma\model\PluginWumaSalesUserLevel;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理用户管理.
 * @class User
 */
class User extends Controller
{
    /**
     * 代理用户管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSalesUser::mQuery()->layTable(function () {
            $this->title = '代理用户管理';
            $this->levels = PluginWumaSalesUserLevel::lists();
        }, static function (QueryHelper $query) {
            $map = ['deleted' => 0];
            $query->where($map)->withCount(['subAgent' => 'subAgentCount'])->with(['supAgent', 'levelinfo']);
            // 上级代理查询
            $db = PluginWumaSalesUser::mQuery()->like('username|phone#super')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("auid in {$db->field('id')->where($map)->buildSql()}");
            }
            // 当前代理查询
            $query->equal('level')->like('code,phone|username#username,code|phone|username#keys')->dateBetween('create_time');
        });
    }

    /**
     * 添加代理用户.
     * @auth true
     */
    public function add()
    {
        PluginWumaSalesUser::mForm('form');
    }

    /**
     * 编辑代理用户.
     * @auth true
     */
    public function edit()
    {
        $this->add();
    }

    /**
     * 代理选择器.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->index();
    }

    /**
     * 删除代理用户.
     * @auth true
     */
    public function remove()
    {
        PluginWumaSalesUser::mDelete();
    }

    /**
     * 表单数据处理.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(16, 'M');
        }
        if ($this->request->isGet()) {
            $this->levels = PluginWumaSalesUserLevel::lists();
            if (empty($this->levels)) {
                $this->error('请先添加代理等级！');
            }
            // 有效时间范围处理
            if (empty($data['super_phone'])) {
                $data['super_phone'] = $this->get['from'] ?? '';
            }
            if (empty($data['date'])) {
                [$data['date_start'], $data['date_after']] = [date('Y-m-d'), date('Y-m-d', strtotime('+1year'))];
            }
        } elseif ($this->request->isPost()) {
            // 检查手机号是否出现重复
            $model = PluginWumaSalesUser::mk()->where(['phone' => $data['phone'], 'deleted' => 0]);
            if (isset($data['id'])) {
                $model->whereRaw("id<>{$data['id']}");
            }
            if ($model->count() > 0) {
                $this->error('手机号已经存在，请使用其它手机！');
            }

            // 代理密码处理，首次必须输入密码
            if (!empty($data['id']) && empty($data['password'])) {
                unset($data['password']);
            } elseif (empty($data['password'])) {
                $this->error('登录密码不能为空！');
            }

            // 有效时间范围处理
            if (empty($data['date'])) {
                $this->error('授权时间不能为空！');
            }
            [$data['date_start'], $data['date_after']] = explode(' - ', $data['date']);

            if (!empty($data['super_phone'])) {
                //  邀请人手机号检查
                $user = PluginWumaSalesUser::mk()->where(['phone' => $data['super_phone']])->find();
                if (empty($user)) {
                    $this->error('邀请人手机号异常！');
                }
                $data['auid'] = $user['id'];
                $data['super_auid'] = $user['id'];
            }
        }
    }
}
