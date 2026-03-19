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

namespace plugin\wechat\client\controller;

use plugin\system\service\SystemService;
use plugin\wechat\client\model\WechatAuto;
use think\admin\Controller;
use think\admin\extend\CodeToolkit;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 关注自动回复.
 * @class Auto
 */
class Auto extends Controller
{
    /**
     * 消息类型.
     * @var array
     */
    public $types = [
        'text' => '文字', 'news' => '图文',
        'image' => '图片', 'music' => '音乐',
        'video' => '视频', 'voice' => '语音',
    ];

    /**
     * 关注自动回复.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        WechatAuto::mQuery()->layTable(function () {
            $this->title = '关注自动回复';
        }, function (QueryHelper $query) {
            $query->like('code,type#mtype')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加自动回复.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加自动回复';
        WechatAuto::mForm('form');
    }

    /**
     * 编辑自动回复.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑自动回复';
        WechatAuto::mForm('form');
    }

    /**
     * 修改规则状态
     * @auth true
     */
    public function state()
    {
        WechatAuto::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除自动回复.
     * @auth true
     */
    public function remove()
    {
        WechatAuto::mDelete();
    }

    /**
     * 列表数据处理.
     */
    protected function _index_page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            $vo['type'] = $this->types[$vo['type']] ?? $vo['type'];
        }
    }

    /**
     * 添加数据处理.
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeToolkit::uniqidNumber(18, 'AM');
        }
        if ($this->request->isGet()) {
            $this->defaultImage = SystemService::uri('/static/theme/img/image.png', '__FULL__');
        } else {
            $data['content'] = strip_tags($data['content'] ?? '', '<a>');
        }
    }
}
