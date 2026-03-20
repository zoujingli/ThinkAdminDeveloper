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

namespace plugin\storage\service;

use think\admin\contract\StorageInterface;
use think\admin\contract\StorageUsageTrait;
use think\admin\Exception;
use think\admin\extend\HttpClient;
use think\admin\Storage;

/**
 * 腾讯云COS存储支持
 * @class TxcosStorage
 */
class TxcosStorage implements StorageInterface
{
    use StorageUsageTrait;

    /**
     * 数据中心.
     * @var string
     */
    private $point;

    /**
     * 存储空间名称.
     * @var string
     */
    private $bucket;

    /**
     * $secretId.
     * @var string
     */
    private $secretId;

    /**
     * secretKey.
     * @var string
     */
    private $secretKey;

    /**
     * 上传文件内容.
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function set(string $name, string $file, bool $safe = false, ?string $attname = null): array
    {
        $data = $this->token($name) + ['key' => $name];
        if (is_string($attname) && strlen($attname) > 0) {
            $data['Content-Disposition'] = urlencode($attname);
        }
        $data['success_action_status'] = '200';
        $file = ['field' => 'file', 'name' => $name, 'content' => $file];
        if (is_numeric(stripos(HttpClient::submit($this->upload(), $data, $file), '200 OK'))) {
            return ['file' => $this->path($name, $safe), 'url' => $this->url($name, $safe, $attname), 'key' => $name];
        }
        return [];
    }

    /**
     * 读取文件内容.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function get(string $name, bool $safe = false): string
    {
        return Storage::curlGet($this->url($name, $safe));
    }

    /**
     * 删除存储文件.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function del(string $name, bool $safe = false): bool
    {
        [$file] = explode('?', $name);
        $result = HttpClient::request('DELETE', "https://{$this->bucket}.{$this->point}/{$file}", [
            'returnHeader' => true, 'headers' => $this->_sign('DELETE', $file),
        ]);
        return is_numeric(stripos($result, '204 No Content'));
    }

    /**
     * 判断是否存在.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function has(string $name, bool $safe = false): bool
    {
        $file = $this->delSuffix($name);
        $result = HttpClient::request('HEAD', "https://{$this->bucket}.{$this->point}/{$file}", [
            'returnHeader' => true, 'headers' => $this->_sign('HEAD', $name),
        ]);
        return is_numeric(stripos($result, 'HTTP/1.1 200 OK'));
    }

    /**
     * 获取访问地址
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function url(string $name, bool $safe = false, ?string $attname = null): string
    {
        return "{$this->domain}/{$this->delSuffix($name)}{$this->getSuffix($attname, $name)}";
    }

    /**
     * 获取存储路径.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function path(string $name, bool $safe = false): string
    {
        return $this->url($name, $safe);
    }

    /**
     * 获取文件信息.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function info(string $name, bool $safe = false, ?string $attname = null): array
    {
        return $this->has($name, $safe) ? [
            'url' => $this->url($name, $safe, $attname),
            'key' => $name, 'file' => $this->path($name, $safe),
        ] : [];
    }

    /**
     * 获取上传地址
     */
    public function upload(): string
    {
        $proc = $this->app->request->isSsl() ? 'https' : 'http';
        return "{$proc}://{$this->bucket}.{$this->point}";
    }

    /**
     * 生成上传令牌.
     * @param string $name 文件名称
     * @param int $expires 有效时间
     * @param ?string $attname 下载名称
     */
    public function token(string $name, int $expires = 3600, ?string $attname = null): array
    {
        $startTimestamp = time();
        $endTimestamp = $startTimestamp + $expires;
        $keyTime = "{$startTimestamp};{$endTimestamp}";
        $siteurl = $this->url($name, false, $attname);
        $policy = json_encode([
            'expiration' => date('Y-m-d\TH:i:s.000\Z', $endTimestamp),
            'conditions' => [['q-ak' => $this->secretId], ['q-sign-time' => $keyTime], ['q-sign-algorithm' => 'sha1']],
        ]);
        return [
            'policy' => base64_encode($policy), 'q-ak' => $this->secretId,
            'siteurl' => $siteurl, 'q-key-time' => $keyTime, 'q-sign-algorithm' => 'sha1',
            'q-signature' => hash_hmac('sha1', sha1($policy), hash_hmac('sha1', $keyTime, $this->secretKey)),
        ];
    }

    /**
     * 获取存储区域
     */
    public static function region(): array
    {
        return [
            'cos.ap-beijing-1.myqcloud.com' => static::trans('中国大陆 公有云地域 北京一区'),
            'cos.ap-beijing.myqcloud.com' => static::trans('中国大陆 公有云地域 北京'),
            'cos.ap-nanjing.myqcloud.com' => static::trans('中国大陆 公有云地域 南京'),
            'cos.ap-shanghai.myqcloud.com' => static::trans('中国大陆 公有云地域 上海'),
            'cos.ap-guangzhou.myqcloud.com' => static::trans('中国大陆 公有云地域 广州'),
            'cos.ap-chengdu.myqcloud.com' => static::trans('中国大陆 公有云地域 成都'),
            'cos.ap-chongqing.myqcloud.com' => static::trans('中国大陆 公有云地域 重庆'),
            'cos.ap-shenzhen-fsi.myqcloud.com' => static::trans('中国大陆 金融云地域 深圳金融'),
            'cos.ap-shanghai-fsi.myqcloud.com' => static::trans('中国大陆 金融云地域 上海金融'),
            'cos.ap-beijing-fsi.myqcloud.com' => static::trans('中国大陆 金融云地域 北京金融'),
            'cos.ap-hongkong.myqcloud.com' => static::trans('亚太地区 公有云地域 中国香港'),
            'cos.ap-singapore.myqcloud.com' => static::trans('亚太地区 公有云地域 新加坡'),
            'cos.ap-mumbai.myqcloud.com' => static::trans('亚太地区 公有云地域 孟买'),
            'cos.ap-jakarta.myqcloud.com' => static::trans('亚太地区 公有云地域 雅加达'),
            'cos.ap-seoul.myqcloud.com' => static::trans('亚太地区 公有云地域 首尔'),
            'cos.ap-bangkok.myqcloud.com' => static::trans('亚太地区 公有云地域 曼谷'),
            'cos.ap-tokyo.myqcloud.com' => static::trans('亚太地区 公有云地域 东京'),
            'cos.na-siliconvalley.myqcloud.com' => static::trans('北美地区 公有云地域 硅谷'),
            'cos.na-ashburn.myqcloud.com' => static::trans('北美地区 公有云地域 弗吉尼亚'),
            'cos.na-toronto.myqcloud.com' => static::trans('北美地区 公有云地域 多伦多'),
            'cos.sa-saopaulo.myqcloud.com' => static::trans('北美地区 公有云地域 圣保罗'),
            'cos.eu-frankfurt.myqcloud.com' => static::trans('欧洲地区 公有云地域 法兰克福'),
            'cos.eu-moscow.myqcloud.com' => static::trans('欧洲地区 公有云地域 莫斯科'),
        ];
    }

    /**
     * 初始化入口.
     * @throws Exception
     */
    protected function init()
    {
        // 读取配置文件
        $this->point = (string)StorageConfig::driver('txcos', 'region', '');
        $this->bucket = (string)StorageConfig::driver('txcos', 'bucket', '');
        $this->secretId = (string)StorageConfig::driver('txcos', 'access_key', '');
        $this->secretKey = (string)StorageConfig::driver('txcos', 'secret_key', '');
        // 计算链接前缀
        $host = strtolower((string)StorageConfig::driver('txcos', 'domain', ''));
        $type = strtolower((string)StorageConfig::driver('txcos', 'protocol', 'http'));
        if ($type === 'auto') {
            $this->domain = "//{$host}";
        } elseif (in_array($type, ['http', 'https'])) {
            $this->domain = "{$type}://{$host}";
        } else {
            throw new Exception(static::trans('未配置腾讯云域名'));
        }
    }

    /**
     * 生成请求签名.
     * @param string $method 请求方式
     * @param string $soruce 资源名称
     */
    private function _sign(string $method, string $soruce): array
    {
        $header = [];
        // 1.生成 KeyTime
        $startTimestamp = time();
        $endTimestamp = $startTimestamp + 3600;
        $keyTime = "{$startTimestamp};{$endTimestamp}";
        // 2.生成 SignKey
        $signKey = hash_hmac('sha1', $keyTime, $this->secretKey);
        // 3.生成 UrlParamList, HttpParameters
        [$parse_url, $urlParamList, $httpParameters] = [parse_url($soruce), '', ''];
        if (!empty($parse_url['query'])) {
            parse_str($parse_url['query'], $params);
            uksort($params, 'strnatcasecmp');
            $urlParamList = join(';', array_keys($params));
            $httpParameters = http_build_query($params);
        }
        // 4.生成 HeaderList, HttpHeaders
        [$headerList, $httpHeaders] = ['', ''];
        // 5.生成 HttpString
        $httpString = strtolower($method) . "\n/{$parse_url['path']}\n{$httpParameters}\n{$httpHeaders}\n";
        // 6.生成 StringToSign
        $httpStringSha1 = sha1($httpString);
        $stringToSign = "sha1\n{$keyTime}\n{$httpStringSha1}\n";
        // 7.生成 Signature
        $signature = hash_hmac('sha1', $stringToSign, $signKey);
        // 8.生成签名
        $signArray = [
            'q-sign-algorithm' => 'sha1',
            'q-ak' => $this->secretId,
            'q-sign-time' => $keyTime,
            'q-key-time' => $keyTime,
            'q-header-list' => $headerList,
            'q-url-param-list' => $urlParamList,
            'q-signature' => $signature,
        ];
        $header['Authorization'] = urldecode(http_build_query($signArray));
        foreach ($header as $key => $value) {
            $header[$key] = ucfirst($key) . ": {$value}";
        }
        return array_values($header);
    }
}
