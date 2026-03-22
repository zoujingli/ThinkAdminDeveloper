<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

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

namespace think\admin\contract;

use plugin\system\storage\StorageConfig;
use think\admin\Exception;
use think\App;
use think\Container;

/**
 * 文件存储公共属性.
 * @class StorageUsageTrait
 */
trait StorageUsageTrait
{
    protected App $app;

    /**
     * 链接类型.
     */
    protected string $link;

    /**
     * 链接前缀
     */
    protected string $domain;

    /**
     * 存储器构造方法.
     * @throws Exception
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->link = class_exists(StorageConfig::class) ? strval(StorageConfig::global('link_mode', 'none')) : 'none';
        $this->init();
    }

    /**
     * 获取对象实例.
     */
    public static function instance(): static|StorageInterface
    {
        /* @var \think\admin\contract\StorageInterface */
        return Container::getInstance()->make(static::class);
    }

    /**
     * 自定义初始化方法.
     */
    protected function init() {}

    /**
     * 兼容无全局语言函数的上下文.
     */
    protected static function trans(string $text): string
    {
        return function_exists('lang') ? lang($text) : $text;
    }

    /**
     * 获取下载链接后缀
     * @param null|string $attname 下载名称
     * @param null|string $filename 文件名称
     */
    protected function getSuffix(?string $attname = null, ?string $filename = null): string
    {
        [$class, $suffix] = [class_basename(get_class($this)), ''];
        if (is_string($filename) && stripos($this->link, 'compress') !== false) {
            $compress = [
                'LocalStorage' => '',
                'QiniuStorage' => '?imageslim',
                'UpyunStorage' => '!/format/webp',
                'TxcosStorage' => '?imageMogr2/format/webp',
                'AliossStorage' => '?x-oss-process=image/format,webp',
            ];
            $extens = strtolower(pathinfo($this->delSuffix($filename), PATHINFO_EXTENSION));
            $suffix = in_array($extens, ['png', 'jpg', 'jpeg']) ? ($compress[$class] ?? '') : '';
        }
        if (is_string($attname) && strlen($attname) > 0 && stripos($this->link, 'full') !== false) {
            if ($class === 'UpyunStorage') {
                $suffix .= ($suffix ? '&' : '?') . '_upd=' . urlencode($attname);
            } else {
                $suffix .= ($suffix ? '&' : '?') . 'attname=' . urlencode($attname);
            }
        }
        return $suffix;
    }

    /**
     * 获取文件基础名称.
     * @param string $name 文件名称
     */
    protected function delSuffix(string $name): string
    {
        if (strpos($name, '?') !== false) {
            return strstr($name, '?', true);
        }
        if (stripos($name, '!') !== false) {
            return strstr($name, '!', true);
        }
        return $name;
    }
}
