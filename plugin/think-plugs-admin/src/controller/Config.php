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

namespace plugin\admin\controller;

use plugin\storage\StorageConfig;
use think\admin\Controller;
use think\admin\auth\AdminService;
use think\admin\module\ModuleService;
use think\admin\runtime\PluginService;
use think\admin\system\SystemService;
use think\admin\Storage;

/**
 * 系统参数配置.
 * @class Config
 */
class Config extends Controller
{
    public const themes = [
        'default' => '默认色0',
        'white' => '简约白0',
        'red-1' => '玫瑰红1',
        'blue-1' => '深空蓝1',
        'green-1' => '小草绿1',
        'black-1' => '经典黑1',
        'red-2' => '玫瑰红2',
        'blue-2' => '深空蓝2',
        'green-2' => '小草绿2',
        'black-2' => '经典黑2',
    ];

    /**
     * 系统参数配置.
     * @auth true
     * @menu true
     */
    public function index()
    {
        StorageConfig::initialize();
        $this->title = '系统参数配置';
        $this->files = Storage::types();
        $this->storageDriver = strtolower((string) StorageConfig::global('driver', 'local'));
        $this->storageName = $this->files[$this->storageDriver] ?? $this->storageDriver;
        $this->storageEditable = AdminService::isSuper()
            || AdminService::check('storage/config/index')
            || AdminService::check('storage/config/storage');
        $this->plugins = PluginService::all(true);
        $this->issuper = AdminService::isSuper();
        $this->systemid = ModuleService::getRunVar('uni');
        $this->framework = ModuleService::getLibrarys('topthink/framework');
        $this->thinkadmin = ModuleService::getLibrarys('zoujingli/think-library');
        if (AdminService::isSuper() && AdminService::getUser('password') === md5('admin')) {
            $url = url('admin/index/pass', ['id' => AdminService::getUserId()]);
            $this->showErrorMessage = lang("超级管理员账号的密码未修改，建议立即<a data-modal='%s'>修改密码</a>！", [$url]);
        }
        uasort($this->plugins, static function ($a, $b) {
            if ($a['space'] === $b['space']) {
                return 0;
            }
            return $a['space'] > $b['space'] ? 1 : -1;
        });
        $this->fetch();
    }

    /**
     * 修改系统参数.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function system()
    {
        if ($this->request->isGet()) {
            $this->title = '修改系统参数';
            $this->themes = static::themes;
            $this->fetch();
        } else {
            $post = $this->request->post();
            unset($post['xpath']);
            // 修改网站 ICON 图标，替换 public/favicon.ico
            if (preg_match('#^https?://#', $post['site_icon'] ?? '')) {
                try {
                    SystemService::setFavicon($post['site_icon'] ?? '');
                } catch (\Exception $exception) {
                    trace_file($exception);
                }
            }
            // 数据数据到系统配置表
            foreach ($post as $k => $v) {
                sysconf($k, $v);
            }
            sysoplog('系统配置管理', '修改系统参数成功');
            $this->success('数据保存成功！', admuri('admin/config/index'));
        }
    }

    /**
     * 修改文件存储.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function storage()
    {
        app(\plugin\storage\controller\Config::class)->storage();
    }
}
