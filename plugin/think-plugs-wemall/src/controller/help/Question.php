<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wemall\controller\help;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallHelpQuestion;
use plugin\wemall\model\PluginWemallHelpQuestionX;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;

/**
 * 工单提问管理
 * class Question
 * @package app\data\controller\news
 */
class Question extends Controller
{
    /**
     * 工单提问管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallHelpQuestion::mQuery()->layTable(function () {
            $this->title = '工单提问管理';
            $this->types = PluginWemallHelpQuestion::tStatus;
        }, function (QueryHelper $helper) {
            $helper->with(['bindUser'])->where(['deleted' => 0]);
            $helper->like('name,content')->equal('status')->dateBetween('create_time');
            // 提交用户搜索
            $db = PluginAccountUser::mQuery()->like('username')->field('id')->db();
            if ($db->getOptions('where')) $helper->whereRaw("unid in {$db->buildSql()}");
        });
    }

    /**
     * 编辑工单内容
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑工单内容';
        PluginWemallHelpQuestion::mQuery()->with(['bindUser', 'comments'])->mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            if (empty($data['content'])) {
                $this->error('回复内容不能为空！');
            }
            $data['status'] = 2;
            PluginWemallHelpQuestionX::mk()->save([
                'ccid'     => $data['id'],
                'content'  => $data['content'],
                'reply_by' => AdminService::getUserId()
            ]);
            unset($data['content']);
        }

    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('内容保存成功！', 'javascript:history.back()');
        }
    }

    /**
     * 修改工单状态
     * @auth true
     */
    public function state()
    {
        PluginWemallHelpQuestion::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除工单数据
     * @auth true
     */
    public function remove()
    {
        PluginWemallHelpQuestion::mDelete();
    }
}