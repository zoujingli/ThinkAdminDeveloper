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

namespace plugin\helper\service;

use think\admin\extend\CodeToolkit;
use think\admin\extend\HttpClient;
use think\admin\service\Service;

/**
 * 百度快递 100 物流查询辅助服务。
 * @class ExpressService
 */
class ExpressService extends Service
{
    /**
     * 网络请求参数。
     * @var array
     */
    protected $options = [];

    /**
     * 公司编码别名。
     * @var array
     */
    protected $codes = [
        'YD' => 'yunda',
        'SF' => 'shunfeng',
        'UC' => 'youshuwuliu',
        'YTO' => 'yuantong',
        'STO' => 'shentong',
        'ZTO' => 'zhongtong',
        'ZJS' => 'zhaijisong',
        'DBL' => 'debangwuliu',
        'HHTT' => 'tiantian',
        'HTKY' => 'huitongkuaidi',
        'YZPY' => 'youzhengguonei',
    ];

    /**
     * 通过百度快递 100 查询物流轨迹。
     * @param string $code 快递公司编码
     * @param string $number 快递单号
     * @param array $list 快递路径列表
     */
    public function express(string $code, string $number, array $list = []): array
    {
        // 新状态：1-新订单，2-在途中，3-签收，4-问题件
        // 原状态：0-在途，1-揽收，2-疑难，3-签收，4-退签，5-派件，6-退回，7-转投，8-清关，14-拒签
        $ckey = md5("{$code}{$number}");
        $cache = $this->app->cache->get($ckey, []);
        $message = [1 => '新订单', 2 => '在途中', 3 => '签收', 4 => '问题件'];
        if (!empty($cache)) {
            return $cache;
        }
        for ($i = 0; $i < 6; ++$i) {
            if (is_array($result = $this->doExpress($code, $number))) {
                if (isset($result['data']['info']['context'], $result['data']['info']['state'])) {
                    $state = intval($result['data']['info']['state']);
                    $status = in_array($state, [0, 1, 5, 7, 8]) ? 2 : ($state === 3 ? 3 : 4);
                    foreach ($result['data']['info']['context'] as $vo) {
                        $list[] = ['time' => date('Y-m-d H:i:s', intval($vo['time'])), 'context' => $vo['desc']];
                    }
                    $result = ['message' => lang($message[$status] ?? $result['msg']), 'status' => $status, 'express' => $code, 'number' => $number, 'data' => $list];
                    $this->app->cache->set($ckey, $result, 30);
                    return $result;
                }
            }
        }
        return ['message' => lang('暂无轨迹信息~'), 'status' => 1, 'express' => $code, 'number' => $number, 'data' => $list];
    }

    /**
     * 获取快递公司列表。
     */
    public function getExpressList(): array
    {
        return $this->getQueryData(2);
    }

    /**
     * 服务初始化。
     * @return $this
     */
    protected function initialize(): self
    {
        $clientIp = $this->app->request->ip();
        if (empty($clientIp) || $clientIp === '0.0.0.0') {
            $clientIp = join('.', [rand(1, 254), rand(1, 254), rand(1, 254), rand(1, 254)]);
        }
        // 通过固定请求头和 cookie 文件模拟真实浏览器访问。
        $this->options['cookie_file'] = syspath('runtime/.cok');
        $this->options['headers'] = ['Host:express.baidu.com', "CLIENT-IP:{$clientIp}", "X-FORWARDED-FOR:{$clientIp}"];
        return $this;
    }

    /**
     * 执行百度快递 100 查询。
     * @param string $code 快递公司编码
     * @param string $number 快递单号
     * @return mixed
     */
    private function doExpress(string $code, string $number)
    {
        [$code, $qqid] = [$this->codes[$code] ?? $code, CodeToolkit::uniqidNumber(19, '7740')];
        $url = "{$this->getQueryData(1)}&appid=4001&nu={$number}&com={$code}&qid={$qqid}&new_need_di=1&source_xcx=0&vcode=&token=&sourceId=4155";
        $result = json_decode(trim(HttpClient::get($url, [], $this->options)), true);
        if (!empty($result['status']) || !empty($result['error_code'])) {
            @unlink($this->options['cookie_file']);
            $this->app->cache->delete('express_kuaidi_uri');
            $this->app->cache->delete('express_kuaidi_com');
        }
        return $result;
    }

    /**
     * 获取查询接口或快递公司映射。
     * @param int $type 数据类型
     * @return array|string
     */
    private function getQueryData(int $type)
    {
        $times = 0;
        $expressUri = $this->app->cache->get('express_kuaidi_uri', '');
        if ($type === 1 && !empty($expressUri)) {
            return $expressUri;
        }
        $expressCom = $this->app->cache->get('express_kuaidi_com', []);
        if ($type === 2 && !empty($expressCom)) {
            return $expressCom;
        }
        while (true) {
            if ($times++ >= 10) {
                $times = 0;
                @unlink($this->options['cookie_file']);
            }
            [$ts, $input] = [mt_rand(2000000, 2900000), CodeToolkit::random(5)];
            $content = HttpClient::get("https://m.baidu.com/s?word=快递查询&ts={$ts}&t_kt=0&ie=utf-8&rsv_iqid=&rsv_t=&sa=&rsv_pq=&rsv_sug4=&tj=1&inputT={$input}&sugid=&ss=", [], $this->options);
            if (preg_match('#\"(expSearchApi|checkExpUrl)\":\"(.*?)\"#i', $content, $matches)) {
                $this->app->cache->set('express_kuaidi_uri', $expressUri = $matches[2], 3600);
                if (preg_match('#\"text\":\"快递查询\",\"option\":.*?(\[.*?]).*?#i', $content, $items)) {
                    $attr = json_decode($items[1], true);
                    $expressCom = array_combine(array_column($attr, 'value'), array_column($attr, 'text'));
                    $this->app->cache->set('express_kuaidi_com', $expressCom, 3600);
                    if ($type === 2) {
                        return $expressCom;
                    }
                }
                if ($type === 1) {
                    return $expressUri;
                }
            } else {
                usleep(100000);
            }
        }
    }
}
