<?php

use think\admin\extend\PhinxExtend;
use think\admin\model\SystemConfig;
use think\admin\model\SystemMenu;
use think\admin\model\SystemUser;
use think\admin\service\ProcessService;
use think\helper\Str;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

/**
 * 数据安装包
 * @class InstallPackage
 */
class InstallPackage extends Migrator
{
    /**
     * 数据库初始化
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function change()
    {
        $this->inserData();
        $this->insertConf();
        $this->insertUser();
        $this->insertMenu();
    }

    /**
     * 安装扩展数据
     * @return void
     * @throws \think\db\exception\DbException
     */
    private function inserData()
    {
        // 待解析处理数据
        $json = '[]';
        // 解析并写入扩展数据
        if (is_array($tables = json_decode($json, true)) && count($tables) > 0) {
            foreach ($tables as $table => $path) if (($model = m($table))->count() < 1) {
                $name = Str::studly($table);
                ProcessService::message(" -- Starting write {$table} table ..." . PHP_EOL);
                [$ls, $rs, $fp] = [0, [], fopen(__DIR__ . DIRECTORY_SEPARATOR . $path, 'r+')];
                while (!feof($fp)) {
                    if (empty($text = trim(fgets($fp)))) continue; else $ls++;
                    if (is_array($rw = json_decode($text, true))) $rs[] = $rw;
                    if (count($rs) > 100) [, $rs] = [$model->strict(false)->insertAll($rs), []];
                    ProcessService::message(" -- -- {$name}:{$ls}", 1);
                }
                count($rs) > 0 && $model->strict(false)->insertAll($rs);
                ProcessService::message(" -- Finished write {$table} table, Total {$ls} rows.", 2);
            }
        }
    }

    /**
     * 初始化配置参数
     * @return void
     */
    private function insertConf()
    {
        $modal = SystemConfig::mk()->whereRaw('1=1')->findOrEmpty();
        $modal->isEmpty() && $modal->insertAll([
            ['type' => 'base', 'name' => 'app_name', 'value' => 'ThinkAdmin'],
            ['type' => 'base', 'name' => 'app_version', 'value' => 'v6'],
            ['type' => 'base', 'name' => 'editor', 'value' => 'ckeditor5'],
            ['type' => 'base', 'name' => 'login_name', 'value' => '系统管理'],
            ['type' => 'base', 'name' => 'site_copy', 'value' => '©版权所有 2014-' . date('Y') . ' ThinkAdmin'],
            ['type' => 'base', 'name' => 'site_icon', 'value' => 'https://thinkadmin.top/static/img/logo.png'],
            ['type' => 'base', 'name' => 'site_name', 'value' => 'ThinkAdmin'],
            ['type' => 'base', 'name' => 'site_theme', 'value' => 'default'],
            ['type' => 'wechat', 'name' => 'type', 'value' => 'api'],
            ['type' => 'storage', 'name' => 'type', 'value' => 'local'],
            ['type' => 'storage', 'name' => 'allow_exts', 'value' => 'doc,gif,ico,jpg,mp3,mp4,p12,pem,png,zip,rar,xls,xlsx'],
        ]);
    }

    /**
     * 初始化用户数据
     * @return void
     */
    private function insertUser()
    {
        $modal = SystemUser::mk()->whereRaw('1=1')->findOrEmpty();
        $modal->isEmpty() && $modal->insert([
            'id'       => '10000',
            'username' => 'admin',
            'nickname' => '超级管理员',
            'password' => '21232f297a57a5a743894a0e4a801fc3',
            'headimg'  => 'https://thinkadmin.top/static/img/head.png',
        ]);
    }

    /**
     * 初始化系统菜单
     * @return void
     */
    private function insertMenu()
    {
        if (SystemMenu::mk()->whereRaw('1=1')->findOrEmpty()->isEmpty()) {
            // 解析并初始化菜单数据
            $json = '[
    {
        "name": "插件中心",
        "icon": "",
        "url": "plugin-center/index/index",
        "node": "plugin-center/index/index",
        "params": ""
    },
    {
        "name": "微信管理",
        "icon": "",
        "url": "#",
        "node": "",
        "params": "",
        "subs": [
            {
                "name": "微信管理",
                "icon": "",
                "url": "#",
                "node": "",
                "params": "",
                "subs": [
                    {
                        "name": "微信接口配置",
                        "url": "wechat/config/options",
                        "node": "wechat/config/options",
                        "icon": "layui-icon layui-icon-set",
                        "params": ""
                    },
                    {
                        "name": "微信支付配置",
                        "url": "wechat/config/payment",
                        "node": "wechat/config/payment",
                        "icon": "layui-icon layui-icon-rmb",
                        "params": ""
                    }
                ]
            },
            {
                "name": "微信定制",
                "icon": "",
                "url": "#",
                "node": "",
                "params": "",
                "subs": [
                    {
                        "name": "微信粉丝管理",
                        "url": "wechat/fans/index",
                        "node": "wechat/fans/index",
                        "icon": "layui-icon layui-icon-username",
                        "params": ""
                    },
                    {
                        "name": "微信图文管理",
                        "url": "wechat/news/index",
                        "node": "wechat/news/index",
                        "icon": "layui-icon layui-icon-template-1",
                        "params": ""
                    },
                    {
                        "name": "微信菜单配置",
                        "url": "wechat/menu/index",
                        "node": "wechat/menu/index",
                        "icon": "layui-icon layui-icon-cellphone",
                        "params": ""
                    },
                    {
                        "name": "回复规则管理",
                        "url": "wechat/keys/index",
                        "node": "wechat/keys/index",
                        "icon": "layui-icon layui-icon-engine",
                        "params": ""
                    },
                    {
                        "name": "关注自动回复",
                        "url": "wechat/auto/index",
                        "node": "wechat/auto/index",
                        "icon": "layui-icon layui-icon-release",
                        "params": ""
                    }
                ]
            },
            {
                "name": "微信支付",
                "icon": "",
                "url": "#",
                "node": "",
                "params": "",
                "subs": [
                    {
                        "name": "支付行为管理",
                        "url": "wechat/payment.record/index",
                        "node": "wechat/payment.record/index",
                        "icon": "layui-icon layui-icon-rmb",
                        "params": ""
                    },
                    {
                        "name": "支付退款管理",
                        "url": "wechat/payment.refund/index",
                        "node": "wechat/payment.refund/index",
                        "icon": "layui-icon layui-icon-engine",
                        "params": ""
                    }
                ]
            }
        ]
    },
    {
        "name": "系统管理",
        "icon": "",
        "url": "#",
        "node": "",
        "params": "",
        "subs": [
            {
                "name": "系统配置",
                "icon": "",
                "url": "#",
                "node": "",
                "params": "",
                "subs": [
                    {
                        "name": "系统参数配置",
                        "url": "admin/config/index",
                        "node": "admin/config/index",
                        "icon": "layui-icon layui-icon-set",
                        "params": ""
                    },
                    {
                        "name": "系统任务管理",
                        "url": "admin/queue/index",
                        "node": "admin/queue/index",
                        "icon": "layui-icon layui-icon-log",
                        "params": ""
                    },
                    {
                        "name": "系统日志管理",
                        "url": "admin/oplog/index",
                        "node": "admin/oplog/index",
                        "icon": "layui-icon layui-icon-form",
                        "params": ""
                    },
                    {
                        "name": "数据字典管理",
                        "url": "admin/base/index",
                        "node": "admin/base/index",
                        "icon": "layui-icon layui-icon-code-circle",
                        "params": ""
                    },
                    {
                        "name": "系统文件管理",
                        "url": "admin/file/index",
                        "node": "admin/file/index",
                        "icon": "layui-icon layui-icon-carousel",
                        "params": ""
                    },
                    {
                        "name": "系统菜单管理",
                        "url": "admin/menu/index",
                        "node": "admin/menu/index",
                        "icon": "layui-icon layui-icon-layouts",
                        "params": ""
                    }
                ]
            },
            {
                "name": "权限管理",
                "icon": "",
                "url": "#",
                "node": "",
                "params": "",
                "subs": [
                    {
                        "name": "访问权限管理",
                        "url": "admin/auth/index",
                        "node": "admin/auth/index",
                        "icon": "layui-icon layui-icon-vercode",
                        "params": ""
                    },
                    {
                        "name": "系统用户管理",
                        "url": "admin/user/index",
                        "node": "admin/user/index",
                        "icon": "layui-icon layui-icon-username",
                        "params": ""
                    }
                ]
            }
        ]
    }
]';
            PhinxExtend::write2menu(json_decode($json, true) ?: []);
        }
    }
}