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
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

namespace plugin\wuma\controller\source;

use plugin\wuma\model\PluginWumaSourceBlockchain;
use plugin\wuma\model\PluginWumaSourceCertificate;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 区块链流程管理.
 * @class Blockchain
 */
class Blockchain extends Controller
{
    /**
     * 区块链流程管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaSourceBlockchain::mQuery()->layTable(function () {
            $this->title = '区块链流程管理';
        }, function (QueryHelper $query) {
            $query->like('code,name')->dateBetween('create_time');
            $query->where(['deleted' => intval($this->type !== 'index')]);
        });
    }

    /**
     * 添加区块链流程.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加区块链流程';
        PluginWumaSourceBlockchain::mForm('form');
    }

    /**
     * 编辑区块链流程.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑区块链流程';
        PluginWumaSourceBlockchain::mForm('form');
    }

    /**
     * 流程上链接操作.
     * @auth true
     */
    public function hash()
    {
        PluginWumaSourceBlockchain::mForm('hash');
    }

    /**
     * 查看流程详情.
     * @auth true
     */
    public function view()
    {
        PluginWumaSourceBlockchain::mForm('view');
    }

    /**
     * 修改区块链流程状态
     * @auth true
     */
    public function state()
    {
        PluginWumaSourceBlockchain::mSave();
    }

    /**
     * 删除区块链流程.
     * @auth true
     */
    public function remove()
    {
        PluginWumaSourceBlockchain::mDelete();
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
            $data['code'] = CodeExtend::uniqidNumber(16, 'BC');
        }
        if ($this->request->isGet()) {
            $this->certs = PluginWumaSourceCertificate::lists();
        }
    }

    /**
     * 表单结果处理.
     */
    protected function _form_result(bool $state)
    {
        $state && $this->success('内容修改成功！', 'javascript:history.back()');
    }

    /**
     * 表单数据处理.
     */
    protected function _hash_form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $data['data'] = json_decode($data['data'] ?? '[]', true);
        } else {
            $data['hash'] = strtoupper(md5($data['code']));
            $data['hash_time'] = date('Y-m-d H:i:s');
            $data['status'] = 2;
        }
    }

    /**
     * 表单结果处理.
     */
    protected function _hash_form_result(bool $state)
    {
        if ($state) {
            $this->success('流程上链成功！');
        }
    }

    /**
     * 表单数据处理.
     */
    protected function _view_form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $data['data'] = json_decode($data['data'] ?? '[]', true);
        }
    }
}
