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

namespace plugin\account\controller;

use plugin\account\model\PluginAccountMsms;
use plugin\account\service\message\Alisms;
use plugin\account\service\Message as AccountMessage;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\PageBuilder;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 手机短信管理.
 * @class Message
 */
class Message extends Controller
{
    /**
     * 缓存配置名称.
     * @var string
     */
    protected $smskey;

    /**
     * 手机短信管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginAccountMsms::mQuery()->layTable(function () {
            $this->buildIndexPage()->fetch(['scenes' => AccountMessage::$scenes]);
        }, static function (QueryHelper $query) {
            $query->equal('status')->like('smsid,scene,phone')->dateBetween('create_time');
        });
    }

    /**
     * 构建短信列表页.
     */
    private function buildIndexPage(): PageBuilder
    {
        return PageBuilder::mk()
            ->setTitle('手机短信管理')
            ->setTable('MessageData', $this->request->url())
            ->setSearchAttrs(['action' => $this->request->url()])
            ->addModalButton('短信配置', url('config')->build(), '', [], 'config')
            ->addBeforeTableHtml('<label class="layui-hide"><textarea id="ScenesData">{$scenes|default=\'\'|json_encode}</textarea></label>')
            ->addBootScript("let scenes = JSON.parse(document.getElementById('ScenesData').value || '{}');")
            ->setTableOptions([
                'loading' => true,
                'sort'    => ['field' => 'id', 'type' => 'desc'],
            ])
            ->addSearchInput('smsid', '消息编号', '请输入消息编号')
            ->addSearchInput('phone', '发送手机', '请输入发送手机')
            ->addSearchSelect('scene', '业务场景', [], [], 'scenes')
            ->addSearchSelect('status', '执行结果', [0 => '发送失败', 1 => '发送成功'])
            ->addSearchDateRange('create_time', '发送时间', '请选择发送时间')
            ->addColumn(['field' => 'id', 'hide' => true])
            ->addColumn(['field' => 'smsid', 'title' => '消息编号', 'sort' => true, 'minWidth' => 100, 'width' => '12%', 'align' => 'center'])
            ->addColumn(['field' => 'type', 'title' => '短信类型', 'sort' => true, 'minWidth' => 90, 'width' => '8%', 'align' => 'center'])
            ->addColumn(['field' => 'phone', 'title' => '发送手机', 'sort' => true, 'minWidth' => 100, 'width' => '10%', 'align' => 'center'])
            ->addColumn([
                'field'    => 'scene',
                'title'    => '业务场景',
                'align'    => 'center',
                'minWidth' => 100,
                'width'    => '8%',
                'templet'  => PageBuilder::raw('function(d){ return scenes[d.scene] || d.scene_name; }'),
            ])
            ->addColumn(['field' => 'params', 'title' => '短信内容', 'align' => 'center'])
            ->addColumn(['field' => 'result', 'title' => '返回结果', 'align' => 'center'])
            ->addColumn([
                'field'    => 'status',
                'title'    => '执行结果',
                'minWidth' => 80,
                'width'    => '8%',
                'sort'     => true,
                'align'    => 'center',
                'templet'  => PageBuilder::raw("function(d){ return ['<b class=\"color-red\">失败</b>', '<b class=\"color-green\">成功</b>'][d.status]; }"),
            ])
            ->addColumn(['field' => 'create_time', 'title' => '发送时间', 'width' => 170, 'align' => 'center', 'sort' => true]);
    }

    /**
     * 修改短信配置.
     * @auth true
     * @throws Exception
     */
    public function config()
    {
        if ($this->request->isGet()) {
            $this->vo = sysdata($this->smskey);
            $this->scenes = AccountMessage::$scenes;
            $this->regions = Alisms::regions();
            $this->fetch();
        } else {
            sysdata($this->smskey, $this->request->post());
            $this->success('修改配置成功！');
        }
    }

    /**
     * 初始化控制器.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->smskey = 'plugin.account.smscfg';
    }
}
