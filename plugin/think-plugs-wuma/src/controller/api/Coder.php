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

namespace plugin\wuma\controller\api;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use think\admin\Controller;

/**
 * 物码批次接口.
 * @class Coder
 */
class Coder extends Controller
{
    /**
     * 查询物码批次
     */
    public function batch()
    {
        $data = $this->_vali([
            'token.require' => '令牌不能为空！',
            'batch.require' => '批次号不能为空！',
        ]);

        // 检查接口验证
        if ($this->app->cache->get("create_auth_{$data['batch']}") !== $data['token']) {
            $this->error('无效的请求令牌！');
        }

        // 获取批次数据
        $rule = PluginWumaCodeRule::mk()->where(['batch' => $data['batch']])->findOrEmpty()->toArray();
        if (empty($rule)) {
            $this->error('批次查询失败！');
        }

        // 读取范围数据
        $range = PluginWumaCodeRuleRange::mk()->where(['batch' => $data['batch']])->column('*', 'code_type');
        if (empty($range)) {
            $this->error('批次范围异常！');
        }

        // 返回接口数据
        $this->success('获取批次数据', ['rule' => $rule, 'range' => $range]);
    }

    /**
     * 查询物码标签.
     */
    public function query()
    {
        $data = $this->_vali([
            'code.require' => '物码不能为空！',
            'type.require' => '类型不能为空！',
            'token.require' => '令牌不能为空！',
        ]);

        // 批次物码区间
        $range = PluginWumaCodeRuleRange::mk()->where([
            ['code_type', '=', $data['type']],
            ['range_start', '<=', $data['code']],
            ['range_after', '>=', $data['code']],
        ])->findOrEmpty();

        if ($range->isEmpty()) {
            $this->error('物码查询失败，区间不存在！');
        }

        // 检查接口验证
        if ($this->app->cache->get("create_auth_{$range['batch']}") !== $range['token']) {
            $this->error('无效的请求令牌！');
        }

        // 批次规则查询
        $batch = PluginWumaCodeRule::mk()->where(['batch' => $range['batch']])->findOrEmpty();
        if ($batch->isEmpty()) {
            $this->error('物码查询失败，规则不存在！');
        }

        // 返回查询结果
        $this->success('物码查询成功', array_merge($data, [
            'batch' => $range['batch'], 'remark' => $batch['remark'],
        ]));
    }
}
