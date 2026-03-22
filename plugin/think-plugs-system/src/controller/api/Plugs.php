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

namespace plugin\system\controller\api;

use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\service\AppService;
use think\Response;

/**
 * 扩展插件管理.
 * @class Plugs
 */
class Plugs extends Controller
{
    /**
     * 图标选择器.
     * @login true
     */
    public function icon()
    {
        $this->title = '图标选择器';
        // 读取 layui 字体图标
        if (empty($this->layuiIcons = $this->app->cache->get('LayuiIcons', []))) {
            $style = file_get_contents(syspath('public/static/plugs/layui/css/layui.css'));
            if (preg_match_all('#\.(layui-icon-[\w-]+):#', $style, $matches)) {
                if (count($this->layuiIcons = $matches[1]) > 0) {
                    $this->app->cache->set('LayuiIcons', $this->layuiIcons, 60);
                }
            }
        }
        // 读取 ThinkAdmin 字体图标
        if (empty($this->thinkIcons = $this->app->cache->get('ThinkAdminSelfIcons', []))) {
            $style = file_get_contents(syspath('public/static/theme/css/iconfont.css'));
            if (preg_match_all('#\.(iconfont-[\w-]+):#', $style, $matches)) {
                if (count($this->thinkIcons = $matches[1]) > 0) {
                    $this->app->cache->set('ThinkAdminSelfIcons', $this->thinkIcons, 60);
                }
            }
        }
        // 读取 extra 自定义字体图标
        if (empty($this->extraIcons = $this->app->cache->get('ThinkAdminExtraIcons', []))) {
            $extraIconPath = syspath('public/static/extra/icon/iconfont.css');
            if (file_exists($extraIconPath)) {
                $style = file_get_contents($extraIconPath);
                if (preg_match_all('#\.(iconfont-[\w-]+):#', $style, $matches)) {
                    if (count($this->extraIcons = $matches[1]) > 0) {
                        $this->app->cache->set('ThinkAdminExtraIcons', $this->extraIcons, 60);
                    }
                }
            }
        }
        $this->field = $this->app->request->get('field', 'icon');
        $this->fetch(dirname(__DIR__, 2) . '/view/api/icon.html');
    }

    /**
     * 前端脚本变量.
     * @throws Exception
     */
    public function script(): Response
    {
        $token = $this->request->get('uptoken', '');
        [$unid] = SystemAuthService::withUploadUnid($token);
        $domain = $unid > 0;
        return response(join("\r\n", [
            sprintf('window.taDebug = %s;', $this->app->isDebug() ? 'true' : 'false'),
            sprintf("window.taApiPrefix = '%s';", AppService::pluginApiPrefix()),
            sprintf("window.taSystem = '%s';", sysuri('system/index/index', [], false, $domain)),
            sprintf("window.taStorage = '%s';", sysuri('system/config/storage', [], false, $domain)),
            sprintf("window.taSystemApi = '%s';", $this->buildApiRoot('system', $domain)),
            sprintf("window.taStorageApi = '%s';", $this->buildApiRoot('system', $domain)),
            sprintf("window.taTokenHeader = '%s';", SystemAuthService::getTokenHeader()),
            sprintf("window.taTokenScheme = '%s';", SystemAuthService::getTokenScheme()),
            sprintf('window.taTokenExpire = %d;', SystemAuthService::getTokenExpire()),
            sprintf("window.taEditor = '%s';", strval(sysdata('system.runtime.editor_driver') ?: 'ckeditor5')),
        ]))->contentType('application/javascript');
    }

    /**
     * 优化数据库.
     * @login true
     */
    public function optimize()
    {
        if (SystemAuthService::isSuper()) {
            sysoplog('系统运维管理', '创建数据库优化任务');
            $this->_queue('优化数据库所有数据表', 'xadmin:database optimize');
        } else {
            $this->error('请使用超管账号操作！');
        }
    }

    private function buildApiRoot(string $plugin, bool $domain): string
    {
        $prefix = AppService::pluginPrefix($plugin) ?: $plugin;
        $path = '/' . trim(AppService::pluginApiPrefix() . '/' . $prefix, '/');
        return ($domain ? $this->request->domain(true) : '') . $path;
    }
}
