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

namespace plugin\center\controller;

use plugin\center\Service;
use plugin\center\service\Plugin;
use think\admin\Controller;
use think\admin\Exception;
use think\Response;

/**
 * Plugin center controller.
 * @class Index
 */
class Index extends Controller
{
    /**
     * Show plugin list or jump to default plugin.
     * @menu true
     * @login true
     * @return Response|void
     * @throws Exception
     */
    public function index()
    {
        $this->items = Plugin::getLocalPlugs(true);
        $this->codes = array_column($this->items, 'code');
        $this->default = sysdata('plugin.center.config')['default'] ?? '';
        if ($this->request->get('from') !== 'force') {
            if (in_array($this->default, $this->codes, true)) {
                return $this->openPlugin($this->default, '打开默认插件');
            }
            if (count($this->codes) === 1) {
                return $this->openPlugin(array_pop($this->codes), '打开指定插件');
            }
        }
        $this->fetch();
    }

    /**
     * Jump to the target plugin layout page.
     */
    private function openPlugin(string $code, string $name = '打开指定插件'): Response
    {
        $href = '#' . sysuri('/' . Service::getAppCode() . '/layout', ['encode' => encode($code)], false);
        return json(['code' => 1, 'info' => $name, 'data' => $href, 'wait' => 'false']);
    }
}
