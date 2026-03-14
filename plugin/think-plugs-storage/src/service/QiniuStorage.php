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

namespace plugin\storage\service;

use think\admin\contract\StorageInterface;
use think\admin\contract\StorageUsageTrait;
use think\admin\Exception;
use think\admin\extend\HttpClient;
use think\admin\service\Storage;

/**
 * 七牛云存储支持
 * @class QiniuStorage
 */
class QiniuStorage implements StorageInterface
{
    use StorageUsageTrait;

    private $bucket;

    private $accessKey;

    private $secretKey;

    /**
     * 上传文件内容.
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     * @throws Exception
     */
    public function set(string $name, string $file, bool $safe = false, ?string $attname = null): array
    {
        $token = $this->token($name, 3600, $attname);
        $data = ['key' => $name, 'token' => $token, 'fileName' => $name];
        $file = ['field' => 'file', 'name' => $name, 'content' => $file];
        $result = HttpClient::submit($this->upload(), $data, $file, [], 'POST', false);
        return json_decode($result, true);
    }

    /**
     * 读取文件内容.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function get(string $name, bool $safe = false): string
    {
        $url = $this->url($name, $safe) . '?e=' . time();
        $token = "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $url, $this->secretKey, true))}";
        return Storage::curlGet("{$url}&token={$token}");
    }

    /**
     * 删除存储文件.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function del(string $name, bool $safe = false): bool
    {
        [$EncodedEntryURI, $AccessToken] = $this->getAccessToken($name, 'delete');
        $data = json_decode(HttpClient::post("https://rs.qiniu.com/delete/{$EncodedEntryURI}", [], [
            'headers' => ["Authorization:QBox {$AccessToken}"],
        ]), true);
        return empty($data['error']);
    }

    /**
     * 判断是否存在.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function has(string $name, bool $safe = false): bool
    {
        return !empty($this->info($name, $safe));
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
        [$entry, $token] = $this->getAccessToken($name);
        $data = json_decode(HttpClient::get("https://rs.qiniu.com/stat/{$entry}", [], ['headers' => ["Authorization: QBox {$token}"]]), true);
        return isset($data['md5']) ? ['file' => $name, 'url' => $this->url($name, $safe, $attname), 'key' => $name] : [];
    }

    /**
     * 获取上传地址
     * @throws Exception
     */
    public function upload(): string
    {
        try {
            $proc = $this->app->request->isSsl() ? 'https' : 'http';
            $region = (string)StorageConfig::driver('qiniu', 'region', '');
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        // 注：汉字为兼容旧版本区域配置
        switch ($region) {
            case '华东':
            case 'up.qiniup.com':
                return "{$proc}://up.qiniup.com";
            case 'up-cn-east-2.qiniup.com':
                return "{$proc}://up-cn-east-2.qiniup.com";
            case '华北':
            case 'up-z1.qiniup.com':
                return "{$proc}://up-z1.qiniup.com";
            case '华南':
            case 'up-z2.qiniup.com':
                return "{$proc}://up-z2.qiniup.com";
            case '北美':
            case 'up-na0.qiniup.com':
                return "{$proc}://up-na0.qiniup.com";
            case '东南亚':
            case 'up-as0.qiniup.com':
                return "{$proc}://up-as0.qiniup.com";
            case 'up-ap-northeast-1.qiniup.com':
                return "{$proc}://up-ap-northeast-1.qiniup.com";
            default:
                throw new Exception(static::trans('未配置七牛云空间区域哦'));
        }
    }

    /**
     * 生成上传令牌.
     * @param ?string $name 文件名称
     * @param int $expires 有效时间
     * @param ?string $attname 下载名称
     */
    public function token(?string $name = null, int $expires = 3600, ?string $attname = null): string
    {
        $key = is_null($name) ? '$(key)' : $name;
        $url = "{$this->domain}/$(key){$this->getSuffix($attname, $name)}";
        $policy = $this->safeBase64(json_encode([
            'deadline' => time() + $expires, 'scope' => is_null($name) ? $this->bucket : "{$this->bucket}:{$name}",
            'returnBody' => json_encode(['uploaded' => true, 'filename' => '$(key)', 'url' => $url, 'key' => $key, 'file' => $key], JSON_UNESCAPED_UNICODE),
        ]));
        return "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $policy, $this->secretKey, true))}:{$policy}";
    }

    /**
     * 获取存储区域
     */
    public static function region(): array
    {
        return [
            'up.qiniup.com' => static::trans('华东-浙江'),
            'up-cn-east-2.qiniup.com' => static::trans('华东-浙江2'),
            'up-z1.qiniup.com' => static::trans('华北-河北'),
            'up-z2.qiniup.com' => static::trans('华南-广东'),
            'up-na0.qiniup.com' => static::trans('北美-洛杉矶'),
            'up-as0.qiniup.com' => static::trans('亚太-新加坡'),
            'up-ap-northeast-1.qiniup.com' => static::trans('亚太-首尔'),
        ];
    }

    /**
     * 初始化入口.
     * @throws Exception
     */
    protected function init()
    {
        // 读取配置文件
        $this->bucket = (string)StorageConfig::driver('qiniu', 'bucket', '');
        $this->accessKey = (string)StorageConfig::driver('qiniu', 'access_key', '');
        $this->secretKey = (string)StorageConfig::driver('qiniu', 'secret_key', '');
        // 计算链接前缀
        $host = strtolower((string)StorageConfig::driver('qiniu', 'domain', ''));
        $type = strtolower((string)StorageConfig::driver('qiniu', 'protocol', 'http'));
        if ($type === 'auto') {
            $this->domain = "//{$host}";
        } elseif (in_array($type, ['http', 'https'])) {
            $this->domain = "{$type}://{$host}";
        } else {
            throw new Exception(static::trans('未配置七牛云域名'));
        }
    }

    /**
     * 安全BASE64编码
     */
    private function safeBase64(string $content): string
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($content));
    }

    /**
     * 生成管理凭证
     * @param string $name 文件名称
     * @param string $type 操作类型
     */
    private function getAccessToken(string $name, string $type = 'stat'): array
    {
        $entry = $this->safeBase64("{$this->bucket}:{$name}");
        $sign = hash_hmac('sha1', "/{$type}/{$entry}\n", $this->secretKey, true);
        return [$entry, "{$this->accessKey}:{$this->safeBase64($sign)}"];
    }
}
