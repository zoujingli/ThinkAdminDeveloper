<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\command;

use plugin\wemall\model\PluginWemallUserTransfer;
use plugin\wemall\service\UserRebate;
use think\admin\Command;
use think\admin\Exception;
use think\admin\storage\LocalStorage;
use think\console\Input;
use think\console\Output;
use WePay\Transfers;
use WePay\TransfersBank;

/**
 * 用户提现处理
 * @class Trans
 * @package app\data\command
 */
class Trans extends Command
{
    /**
     * 用户提现配置
     * @return void
     */
    protected function configure()
    {
        $this->setName('xdata:mall:trans');
        $this->setDescription('执行提现打款操作');
    }

    /**
     * 执行微信提现操作
     * @param Input $input
     * @param Output $output
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    protected function execute(Input $input, Output $output)
    {
        $model = PluginWemallUserTransfer::mk()->where(['type' => ['wechat_banks', 'wechat_wallet'], 'status' => [3, 4]]);
        [$total, $count, $error, $changeNow] = [(clone $model)->count(), 0, 0, date('Y-m-d H:i:s')];
        /** @var PluginWemallUserTransfer $item */
        foreach ((clone $model)->cursor() as $model) try {
            $this->queue->message($total, ++$count, sprintf('开始处理订单 %s 提现', $model->getAttr('code')));
            if ($model->getAttr('status') === 3) {
                $this->queue->message($total, $count, sprintf('尝试处理订单 %s 打款', $model->getAttr('code')), 1);
                if ($model->getAttr('type') === 'wechat_banks') {
                    $result = $this->createTransferBank($model);
                } else {
                    $result = $this->createTransferWallet($model);
                }
                if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                    $model->save([
                        'status'      => 4,
                        'trade_no'    => $result['partner_trade_no'],
                        'trade_time'  => $result['payment_time'] ?? $changeNow,
                        'change_time' => $changeNow,
                        'change_desc' => '创建微信提现成功',
                    ]);
                } else {
                    $model->save([
                        'change_time' => $changeNow,
                        'change_desc' => $result['err_code_des'] ?? '线上提现失败'
                    ]);
                }
            } elseif ($model->getAttr('status') === 4) {
                $this->queue->message($total, $count, sprintf('刷新提现订单 %s 状态', $model->getAttr('code')), 1);
                $model->getAttr('type') === 'wechat_banks' ? $this->queryTransferBank($model) : $this->queryTransferWallet($model);
            }
        } catch (\Exception $exception) {
            $error++;
            $this->queue->message($total, $count, sprintf('处理提现订单 %s 失败, %s', $model->getAttr('code'), $exception->getMessage()), 1);
            $model->save(['change_time' => $changeNow, 'change_desc' => $exception->getMessage()]);
        }
        $this->setQueueSuccess(sprintf('此次共处理 %d 笔提现操作, 其中有 %d 笔处理失败。', $total, $error));
    }

    /**
     * 尝试提现转账到银行卡
     * @param PluginWemallUserTransfer $model
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    private function createTransferBank(PluginWemallUserTransfer $model): array
    {
        return TransfersBank::instance($this->getConfig($model))->create([
            'partner_trade_no' => $model->getAttr('code'),
            'enc_bank_no'      => $model->getAttr('bank_code'),
            'enc_true_name'    => $model->getAttr('bank_user'),
            'bank_code'        => $model->getAttr('bank_wseq'),
            'amount'           => intval($model->getAttr('amount') - $model->getAttr('charge_amount')) * 100,
            'desc'             => '微信银行卡提现',
        ]);
    }

    /**
     * 尝试提现转账到微信钱包
     * @param PluginWemallUserTransfer $model
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    private function createTransferWallet(PluginWemallUserTransfer $model): array
    {
        return Transfers::instance($this->getConfig($model))->create([
            'openid'           => $model->getAttr('openid'),
            'amount'           => intval($model->getAttr('amount') - $model->getAttr('charge_amount')) * 100,
            'partner_trade_no' => $model->getAttr('code'),
            'spbill_create_ip' => '127.0.0.1',
            'check_name'       => 'NO_CHECK',
            'desc'             => '微信余额提现！',
        ]);
    }

    /**
     * 查询更新提现打款状态
     * @param PluginWemallUserTransfer $model
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    private function queryTransferBank(PluginWemallUserTransfer $model)
    {
        $result = TransfersBank::instance($this->getConfig($model))->query($model->getAttr('trade_no'));
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            if ($result['status'] === 'SUCCESS') {
                $model->save([
                    'status'      => 5,
                    'trade_time'  => $result['pay_succ_time'] ?: date('Y-m-d H:i:s'),
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_desc' => '微信提现打款成功',
                ]);
            } elseif (in_array($result['status'], ['FAILED', 'BANK_FAIL'])) {
                $model->save([
                    'status'      => 0,
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_desc' => '微信提现打款失败',
                ]);
                // 刷新用户可提现余额
                UserRebate::recount($model->getAttr('unid'));
            }
        }
    }

    /**
     * 查询更新提现打款状态
     * @param PluginWemallUserTransfer $model
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    private function queryTransferWallet(PluginWemallUserTransfer $model)
    {
        $result = Transfers::instance($this->getConfig($model))->query($model->getAttr('trade_no'));
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            $model->save([
                'status'      => 5,
                'trade_time'  => $result['payment_time'],
                'change_time' => date('Y-m-d H:i:s'),
                'change_desc' => '微信提现打款成功！',
            ]);
        }
    }

    /**
     * 获取微信提现参数
     * @param PluginWemallUserTransfer $model
     * @return array
     * @throws \think\admin\Exception
     */
    private function getConfig(PluginWemallUserTransfer $model): array
    {
        $data = sysdata('plugin.wemall.transfer.wxpay');
        if (empty($data)) throw new Exception('未配置微信提现商户！');
        // 商户证书文件处理
        $local = LocalStorage::instance();
        if (!$local->has($file1 = "{$data['wechat_mch_id']}_key.pem", true)) {
            $local->set($file1, $data['wechat_mch_key_text'], true);
        }
        if (!$local->has($file2 = "{$data['wechat_mch_id']}_cert.pem", true)) {
            $local->set($file2, $data['wechat_mch_cert_text'], true);
        }
        // 获取商户参数
        return [
            'appid'      => $model->getAttr('appid'),
            'mch_id'     => $data['wechat_mch_id'],
            'mch_key'    => $data['wechat_mch_key'],
            'ssl_key'    => $local->path($file1, true),
            'ssl_cer'    => $local->path($file2, true),
            'cache_path' => syspath('runtime/wechat'),
        ];
    }
}