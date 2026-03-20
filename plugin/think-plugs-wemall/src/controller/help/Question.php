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

namespace plugin\wemall\controller\help;

use plugin\account\model\PluginAccountUser;
use plugin\system\service\SystemAuthService;
use plugin\wemall\model\PluginWemallHelpQuestion;
use plugin\wemall\model\PluginWemallHelpQuestionX;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 工单提问管理
 * class Question.
 */
class Question extends Controller
{
    /**
     * 工单提问管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallHelpQuestion::mQuery()->layTable(function () {
            $this->title = '工单提问管理';
            $this->types = PluginWemallHelpQuestion::tStatus;
        }, function (QueryHelper $helper) {
            $helper->with(['bindUser']);
            $helper->like('name,content')->equal('status')->dateBetween('create_time');
            // 提交用户搜索
            $db = PluginAccountUser::mQuery();
            $db->like('username');
            if (!empty($db->getOptions()['where'] ?? [])) {
                $helper->whereRaw("unid in {$db->db()->field('id')->buildSql()}");
            }
        });
    }

    /**
     * 编辑工单内容.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑工单内容';
        $query = PluginWemallHelpQuestion::mQuery();
        $query->with(['bindUser', 'comments']);
        $query->mForm('form');
    }

    /**
     * 修改工单状态
     * @auth true
     */
    public function state()
    {
        PluginWemallHelpQuestion::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除工单数据.
     * @auth true
     */
    public function remove()
    {
        PluginWemallHelpQuestion::mDelete();
    }

    /**
     * 表单数据处理.
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            if (empty($data['content'])) {
                $this->error('回复内容不能为空！');
            }
            $data['status'] = 2;
            PluginWemallHelpQuestionX::mk()->save([
                'ccid' => $data['id'],
                'content' => $data['content'],
                'reply_by' => SystemAuthService::getUserId(),
            ]);
            unset($data['content']);
        }
    }

    /**
     * 表单结果处理.
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('内容保存成功！', 'javascript:history.back()');
        }
    }
}
