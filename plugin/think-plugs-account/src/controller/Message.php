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

namespace plugin\account\controller;

use plugin\account\model\PluginAccountMsms;
use plugin\account\service\message\Alisms;
use plugin\account\service\Message as AccountMessage;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\builder\form\FormBuilder;
use think\admin\builder\page\PageBuilder;
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
     * 修改短信配置.
     * @auth true
     * @throws Exception
     */
    public function config()
    {
        $builder = $this->buildConfigForm();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadConfigData()]);
        }

        $data = $builder->validate();
        $payload = [
            'alisms_region' => strval($data['alisms_region'] ?? ''),
            'alisms_keyid' => strval($data['alisms_keyid'] ?? ''),
            'alisms_secret' => strval($data['alisms_secret'] ?? ''),
            'alisms_signtx' => strval($data['alisms_signtx'] ?? ''),
            'alisms_scenes' => [],
        ];
        foreach (AccountMessage::$scenes as $code => $name) {
            $payload['alisms_scenes'][$code] = strval($data[$this->sceneFieldName((string)$code)] ?? '');
        }
        sysdata($this->smskey, $payload);
        $this->success('修改配置成功！');
    }

    /**
     * 初始化控制器.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->smskey = 'plugin.account.smscfg';
    }

    /**
     * 构建短信列表页.
     */
    private function buildIndexPage(): PageBuilder
    {
        return PageBuilder::make()
            ->define(function ($page) {
                $page->title('手机短信管理')
                    ->searchAttrs(['action' => $this->request->url()])
                    ->buttons(function ($buttons) {
                        $buttons->modal('短信配置', url('config')->build(), '', [], 'config');
                    })
                    ->bootScript("let scenes = JSON.parse(document.getElementById('ScenesData').value || '{}');")
                    ->search(function ($search) {
                        $search->input('smsid', '消息编号', '请输入消息编号')
                            ->input('phone', '发送手机', '请输入发送手机')
                            ->select('scene', '业务场景', [], [], 'scenes')
                            ->select('status', '执行结果', [0 => '发送失败', 1 => '发送成功'])
                            ->dateRange('create_time', '发送时间', '请选择发送时间');
                    })
                    ->table('MessageData', $this->request->url(), function ($table) {
                        $table->options([
                            'loading' => true,
                            'sort' => ['field' => 'id', 'type' => 'desc'],
                        ])->column(['field' => 'id', 'hide' => true])
                            ->column(['field' => 'smsid', 'title' => '消息编号', 'sort' => true, 'minWidth' => 100, 'width' => '12%', 'align' => 'center'])
                            ->column(['field' => 'type', 'title' => '短信类型', 'sort' => true, 'minWidth' => 90, 'width' => '8%', 'align' => 'center'])
                            ->column(['field' => 'phone', 'title' => '发送手机', 'sort' => true, 'minWidth' => 100, 'width' => '10%', 'align' => 'center'])
                            ->column([
                                'field' => 'scene',
                                'title' => '业务场景',
                                'align' => 'center',
                                'minWidth' => 100,
                                'width' => '8%',
                                'templet' => PageBuilder::js('function(d){ return scenes[d.scene] || d.scene_name; }'),
                            ])
                            ->column(['field' => 'params', 'title' => '短信内容', 'align' => 'center'])
                            ->column(['field' => 'result', 'title' => '返回结果', 'align' => 'center'])
                            ->column([
                                'field' => 'status',
                                'title' => '执行结果',
                                'minWidth' => 80,
                                'width' => '8%',
                                'sort' => true,
                                'align' => 'center',
                                'templet' => PageBuilder::js("function(d){ return ['<b class=\"color-red\">失败</b>', '<b class=\"color-green\">成功</b>'][d.status]; }"),
                            ])
                            ->column(['field' => 'create_time', 'title' => '发送时间', 'width' => 170, 'align' => 'center', 'sort' => true]);
                    });

                $page->node('label')
                    ->class('layui-hide')
                    ->node('textarea')
                    ->id('ScenesData')
                    ->html('{$scenes|default=\'\'|json_encode}');
            })
            ->build();
    }

    private function buildConfigForm(): FormBuilder
    {
        $regionOptions = [];
        foreach (Alisms::regions() as $code => $region) {
            $regionOptions[$code] = sprintf('[ %s ] %s', $code, strval($region['name'] ?? $code));
        }

        return FormBuilder::make()
            ->define(function ($form) use ($regionOptions) {
                $form->action(url('config')->build())
                    ->fields(function ($fields) use ($regionOptions) {
                        $fields->select('alisms_region', '服务区域', 'Region', true, '', $regionOptions)
                            ->text('alisms_keyid', '阿里云账号', 'AccessKeyId', true)
                            ->text('alisms_secret', '阿里云密钥', 'AccessKeySecret', true)
                            ->text('alisms_signtx', '短信签名', 'SignName', true);
                        foreach (AccountMessage::$scenes as $code => $name) {
                            $fields->text($this->sceneFieldName((string)$code), (string)$name, ucfirst(strtolower((string)$code)) . ' Code', true);
                        }
                    })->actions(function ($actions) {
                        $actions->submit('保存配置')->cancel('取消修改', '确定要取消修改吗？');
                    });
            })
            ->build();
    }

    private function loadConfigData(): array
    {
        $data = (array)sysdata($this->smskey);
        foreach (AccountMessage::$scenes as $code => $name) {
            $data[$this->sceneFieldName((string)$code)] = strval($data['alisms_scenes'][$code] ?? '');
        }
        return $data;
    }

    private function sceneFieldName(string $code): string
    {
        return 'scene_' . strtolower($code);
    }
}
