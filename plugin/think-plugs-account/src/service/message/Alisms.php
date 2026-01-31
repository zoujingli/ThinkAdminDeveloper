<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\account\service\message;

use plugin\account\service\contract\MessageInterface;
use plugin\account\service\contract\MessageUsageTrait;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\extend\HttpExtend;

/**
 * 阿里云短信服务
 * @class Alisms
 */
class Alisms implements MessageInterface
{
    use MessageUsageTrait;

    protected static $regions = [
        'cn-qingdao' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北1（青岛）'],
        'cn-beijing' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北2（北京）'],
        'cn-zhangjiakou' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北3（张家口）'],
        'cn-huhehaote' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北5（呼和浩特）'],
        'cn-wulanchabu' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北6（乌兰察布）'],
        'cn-hangzhou' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华东1（杭州）'],
        'cn-shanghai' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华东2（上海）'],
        'cn-shenzhen' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华南1（深圳）'],
        'cn-chengdu' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '西南1（成都）'],
        'cn-hongkong' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '中国（香港）'],
        'ap-northeast-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '日本（东京）'],
        'ap-southeast-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '新加坡'],
        'ap-southeast-2' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '澳大利亚（悉尼）'],
        'ap-southeast-3' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '马来西亚（吉隆坡）'],
        'ap-southeast-5' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '印度尼西亚（雅加达）'],
        'us-east-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '美国（弗吉尼亚）'],
        'us-west-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '美国（硅谷）'],
        'eu-west-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '英国（伦敦）'],
        'eu-central-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '德国（法兰克福）'],
        'ap-south-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '印度（孟买）'],
        'me-east-1' => ['host' => 'dysmsapi.ap-southeast-1.aliyuncs.com', 'name' => '阿联酋（迪拜）'],
        'cn-hangzhou-finance' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华东1 金融云'],
        'cn-shanghai-finance-1' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华东2 金融云'],
        'cn-shenzhen-finance-1' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华南1 金融云'],
        'cn-beijing-finance-1' => ['host' => 'dysmsapi.aliyuncs.com', 'name' => '华北2 金融云'],
    ];

    private $keyid;

    private $secret;

    private $region;

    private $getway;

    private $signtx;

    /**
     * 初始化短信通道.
     * @return $this
     * @throws Exception
     */
    public function init(array $config = []): MessageInterface
    {
        $options = array_merge(sysdata('plugin.account.smscfg'), $config);
        $this->keyid = $options['alisms_keyid'] ?? '';
        $this->secret = $options['alisms_secret'] ?? '';
        $this->signtx = $options['alisms_signtx'] ?? '';
        $this->region = $options['alisms_region'] ?? 'cn-shanghai';
        $this->scenes = $options['alisms_scenes'] ?? [];
        $this->getway = self::$regions[$this->region]['host'] ?? 'dysmsapi.aliyuncs.com';
        return $this;
    }

    /**
     * 发送短信内容.
     * @param string $code 短信模板CODE
     * @param string $phone 接收手机号码
     * @param array $params 短信模板变量
     * @param array $options 其他配置参数
     * @throws Exception
     */
    public function send(string $code, string $phone, array $params = [], array $options = []): array
    {
        $result = $this->request($params = array_merge([
            'SignName' => $this->signtx,
            'PhoneNumbers' => $phone,
            'TemplateCode' => $code,
            'TemplateParam' => json_encode((object)$params),
        ], $options));
        return ['smsid' => $result['BizId'], 'params' => $params, 'result' => $result];
    }

    /**
     * 生成接口请求 TOKEN.
     * @throws Exception
     */
    protected function request(array $params = [], string $action = 'SendSms', string $method = 'POST'): array
    {
        date_default_timezone_set('UTC');
        $querys = array_merge([
            'AccessKeyId' => $this->keyid,
            'Action' => $action,
            'Format' => 'JSON',
            'RegionId' => $this->region,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => CodeExtend::uuid(),
            'SignatureVersion' => '1.0',
            'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ], $params);
        $result = HttpExtend::request($method, "https://{$this->getway}", [
            'data' => $params, 'query' => ['Signature' => $this->buildSign($method, $querys)] + $querys,
        ]);
        if (is_string($result) && is_array($json = json_decode($result, true))) {
            if (isset($json['Code']) && $json['Code'] === 'OK') {
                return $json;
            }
            throw new Exception($json['Message'] ?? $result, 500, $json);
        }
        throw new Exception('接口调用失败！' . var_export($result, true), 500);
    }

    /**
     * 生成数据签名.
     */
    private function buildSign(string $method, array $querys): string
    {
        [$vars] = [[], ksort($querys)];
        foreach ($querys as $k => $v) {
            $vars[] = urlencode($k) . '=' . urlencode($v);
        }
        return base64_encode(hash_hmac('sha1', "{$method}&%2F&" . urlencode(join('&', $vars)), "{$this->secret}&", true));
    }
}
