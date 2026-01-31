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

namespace plugin\wemall\controller\base\express;

use plugin\wemall\model\PluginWemallExpressCompany;
use plugin\wemall\service\ExpressService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 快递公司管理.
 * @class Company
 */
class Company extends Controller
{
    /**
     * 快递公司管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallExpressCompany::mQuery()->layTable(function () {
            $this->title = '快递公司管理';
        }, function (QueryHelper $query) {
            $query->like('name,code')->equal('status')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加快递公司.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加快递公司';
        PluginWemallExpressCompany::mForm('form');
    }

    /**
     * 编辑快递公司.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑快递公司';
        PluginWemallExpressCompany::mForm('form');
    }

    /**
     * 修改快递公司状态
     * @auth true
     */
    public function state()
    {
        PluginWemallExpressCompany::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除快递公司.
     * @auth true
     */
    public function remove()
    {
        PluginWemallExpressCompany::mDelete();
    }

    /**
     * 同步快递公司.
     * @auth true
     */
    public function sync()
    {
        try {
            $result = ExpressService::company();
            if (empty($result['code'])) {
                $this->error($result['info']);
            }
            foreach ($result['data'] as $vo) {
                PluginWemallExpressCompany::mUpdate([
                    'name' => $vo['title'], 'code' => $vo['code_2'], 'deleted' => 0,
                ], 'code');
            }
            $this->success('同步快递公司成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error('同步快递公司数据失败！');
        }
    }
}
