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

namespace plugin\wemall\controller\api\auth;

use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallUserCheckin;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 用户签到接口.
 * @class Checkin
 */
class Checkin extends Auth
{
    /**
     * 当前日期
     * @var string
     */
    protected $today = '';

    /**
     * 创建签到活动.
     */
    public function add()
    {
        try {
            $conf = sysdata(PluginWemallUserCheckin::$ckcfg);
            if (empty($conf['status'])) {
                $this->error('活动未开始！');
            }
            $last = PluginWemallUserCheckin::mk()->where(['unid' => $this->unid])->order('id desc')->findOrEmpty();
            if ($last->isExists() && $last->getAttr('date') === $this->today) {
                $this->success('已签到！', $last->toArray());
            }
            // 计算连续天数
            $yesterday = date('Y-m-d', strtotime('-1day', strtotime($this->today)));
            if ($last->isEmpty() || ($last->isExists() && $last->getAttr('date') !== $yesterday)) {
                $timed = $times = 1;
            } else {
                $times = $last->getAttr('times') + 1;
                $timed = $times % $conf['days'];
                if ($timed <= 0) {
                    $timed = intval($conf['days']);
                }
            }
            // 写入签到数据
            $item = $conf['items'][$timed - 1] ?? [];
            ($checkin = PluginWemallUserCheckin::mk())->save([
                'unid' => $this->unid,
                'date' => $this->today,
                'times' => $times,
                'timed' => $timed,
                'balance' => $item['balance'] ?? 0,
                'integral' => $item['integral'] ?? 0,
            ]);
            // 发放余额及积分奖励
            [$balance, $integral] = [strval($checkin->getAttr('balance')), strval($checkin->getAttr('integral'))];
            if (bccomp($balance, '0.00', 2) > 0 || bccomp($integral, '0.00', 2) > 0) {
                $this->app->db->transaction(function () use ($balance, $integral) {
                    $code = CodeExtend::uniqidNumber(16, 'CK');
                    bccomp($balance, '0.00', 2) > 0 && Balance::create($this->unid, $code, '签到奖励余额', $balance, '通过签到活动获得的奖励', true);
                    bccomp($integral, '0.00', 2) > 0 && Integral::create($this->unid, $code, '签到奖励积分', $integral, '通过签到活动获得的奖励', true);
                });
            }
            $this->success('签到成功！', $checkin->refresh()->toArray());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取签到记录.
     */
    public function get()
    {
        $data = $this->_vali(['date.default' => '']);
        if (empty($data['date'])) {
            $data['date'] = date('Y-m');
        }
        $date = date('Y-m', strtotime($data['date']));
        PluginWemallUserCheckin::mQuery(null, function (QueryHelper $query) use ($date) {
            $query->where(['unid' => $this->unid])->whereLike('create_time', "{$date}%");
            $this->success('获取签到记录！', $query->order('id desc')->page(false, false, false, 90));
        });
    }

    /**
     * 获取签到配置.
     * @throws \think\admin\Exception
     */
    public function config()
    {
        $data = sysdata(PluginWemallUserCheckin::$ckcfg);
        unset($data['days'], $data['items']);
        $this->success('获取签到配置', $data);
    }

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->today = date('Y-m-d');
    }

    /**
     * 数据列表处理.
     * @throws \think\admin\Exception
     */
    protected function _page_filter(array &$data, array &$result)
    {
        $conf = sysdata(PluginWemallUserCheckin::$ckcfg);
        $result['date'] = $this->today;
        $result['tips'] = str2arr($conf['tips'], "\n");
    }
}
