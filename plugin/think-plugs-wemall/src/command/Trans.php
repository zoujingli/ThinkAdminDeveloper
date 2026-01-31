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

namespace plugin\wemall\command;

use plugin\wemall\model\PluginWemallUserTransfer;
use plugin\wemall\service\UserRebate;
use think\admin\Command;
use think\admin\Exception;
use think\admin\storage\LocalStorage;
use think\console\Input;
use think\console\Output;
use think\db\exception\DbException;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WePay\Transfers;
use WePay\TransfersBank;
use WePayV3\Transfers as TransfersV3;

/**
 * 代理提现处理.
 * @class Trans
 */
class Trans extends Command
{
    /**
     * 代理提现配置.
     */
    protected function configure()
    {
        $this->setName('xdata:mall:trans');
        $this->setDescription('执行提现打款操作');
    }

    /**
     * 执行微信提现操作.
     * @throws Exception
     * @throws DbException
     */
    protected function execute(Input $input, Output $output)
    {
        $msgs = [
            'ACCEPTED' => '已受理。',
            'PROCESSING' => '转账中。已开始处理批次内的转账明细单。',
            'FINISHED' => '已完成。批次内的所有转账明细单都已处理完成。',
            'CLOSED' => '已关闭。可查询具体的批次关闭原因确认。',
            'PARAM_ERROR' => '参数错误。',
            'INVALID_REQUEST' => '请求参数符合参数格式，但不符合业务规则。',
            'SIGN_ERROR' => '验证不通过。',
            'SYSTEM_ERROR' => '系统异常，请稍后重试。',
        ];
        $model = PluginWemallUserTransfer::mk()->where(['type' => 'wechat_wallet', 'status' => [3, 4]]);
        [$total, $count, $error, $changeNow] = [(clone $model)->count(), 0, 0, date('Y-m-d H:i:s')];
        /* @var PluginWemallUserTransfer $item */
        foreach ((clone $model)->cursor() as $model) {
            try {
                $this->queue->message($total, ++$count, sprintf('开始处理订单 %s 提现', $model->getAttr('code')));
                if ($model->getAttr('status') === 3) {
                    $this->queue->message($total, $count, sprintf('尝试处理订单 %s 打款', $model->getAttr('code')), 1);
                    $result = $this->createTransferV3($model);
                    if (isset($result['code'], $result['batch_id'])) {
                        $model->save([
                            'status' => 4,
                            'trade_no' => $result['batch_id'],
                            'trade_time' => date('Y-m-d H:i:s', strtotime($result['create_time'] ?? $changeNow)),
                            'change_time' => $changeNow,
                            'change_desc' => '创建微信提现成功',
                        ]);
                    } else {
                        $model->save([
                            'change_time' => $changeNow,
                            'change_desc' => $result['message'] ?? ($msgs[$result['batch_status'] ?? '-'] ?? '线上提现失败'),
                        ]);
                    }
                } elseif ($model->getAttr('status') === 4) {
                    $this->queue->message($total, $count, sprintf('刷新提现订单 %s 状态', $model->getAttr('code')), 1);
                    $this->queryTransferV3($model);
                }
            } catch (\Exception $exception) {
                ++$error;
                $this->queue->message($total, $count, sprintf('处理提现订单 %s 失败, %s', $model->getAttr('code'), $exception->getMessage()), 1);
                $model->save(['change_time' => $changeNow, 'change_desc' => $exception->getMessage()]);
            }
        }
        $this->setQueueSuccess(sprintf('此次共处理 %d 笔提现操作, 其中有 %d 笔处理失败。', $total, $error));
    }

    /**
     * 通过商户转账到余额.
     * @throws InvalidDecryptException
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    private function createTransferV3(PluginWemallUserTransfer $model): array
    {
        $amount = floatval($model->getAttr('amount')) - floatval($model->getAttr('charge_amount'));
        return TransfersV3::instance($this->getConfig($model))->batchs([
            'out_batch_no' => "B{$model->getAttr('code')}",
            'batch_name' => '微信余额提现',
            'batch_remark' => '来自商户批量提现！',
            'transfer_detail_list' => [
                [
                    'openid' => $model->getAttr('openid'),
                    'out_detail_no' => $model->getAttr('code'),
                    'transfer_amount' => intval($amount * 100),
                    'transfer_remark' => '自商户收入的提现',
                ],
            ],
        ]);
    }

    /**
     * 查询V3接口提现打款状态
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    private function queryTransferV3(PluginWemallUserTransfer $model): void
    {
        $result = TransfersV3::instance($this->getConfig($model))->query($model->getAttr('trade_no'));
        p($result, false, 'transfer_notify');
        if (is_array($result) && isset($result['transfer_detail_list'])) {
            $msgs = [
                'INIT' => '初始态。系统转账校验中。',
                'WAIT_PAY' => '待确认。符合免密条件时, 系统会自动扭转为转账中。',
                'FAIL' => '转账失败。需要确认失败原因后，再决定是否重新发起对该笔明细单的转账。',
                'PROCESSING' => '转账中。正在处理中，转账结果尚未明确。',
                'SUCCESS' => '转账成功。',
            ];
            $nowTime = date('Y-m-d H:i:s');
            $updateTime = empty($result['transfer_batch']['update_time']) ? $nowTime : $result['transfer_batch']['update_time'];
            foreach ($result['transfer_detail_list'] as $item) {
                if ($item['detail_status'] === 'SUCCESS') {
                    // 处理完成，直接完成操作
                    $model->save([
                        'status' => 5,
                        'trade_time' => date('Y-m-d H:i:s', strtotime($updateTime)),
                        'change_time' => $nowTime,
                        'change_desc' => $msgs[$item['detail_status']] ?? '微信提现打款成功',
                    ]);
                } elseif (in_array($item['detail_status'], ['INIT', 'WAIT_PAY', 'PROCESSING'])) {
                    // 处理中，等待完成即可
                    $model->save([
                        'status' => 4,
                        'trade_time' => date('Y-m-d H:i:s', strtotime($updateTime)),
                        'change_time' => $nowTime,
                        'change_desc' => $msgs[$item['detail_status']],
                    ]);
                } else {
                    // 异常状态，需要回退提现
                    $model->save([
                        'status' => 0,
                        'change_time' => $nowTime,
                        'change_desc' => $msgs[$item['detail_status'] ?? '-'] ?? '微信提现打款失败',
                    ]);
                    // 刷新用户可提现余额
                    UserRebate::recount($model->getAttr('unid'));
                }
            }
        }
    }

    /**
     * 企业付款到银行卡
     * @throws InvalidDecryptException
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @deprecated 微信商户已不再提供此接口
     */
    private function createTransferBank(PluginWemallUserTransfer $model): array
    {
        return TransfersBank::instance($this->getConfig($model))->create([
            'partner_trade_no' => $model->getAttr('code'),
            'enc_bank_no' => $model->getAttr('bank_code'),
            'enc_true_name' => $model->getAttr('bank_user'),
            'bank_code' => $model->getAttr('bank_wseq'),
            'amount' => intval($model->getAttr('amount') - $model->getAttr('charge_amount')) * 100,
            'desc' => '微信银行卡提现',
        ]);
    }

    /**
     * 企业付款到零钱.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @deprecated 微信商户已不再提供此接口
     */
    private function createTransferWallet(PluginWemallUserTransfer $model): array
    {
        return Transfers::instance($this->getConfig($model))->create([
            'openid' => $model->getAttr('openid'),
            'amount' => intval($model->getAttr('amount') - $model->getAttr('charge_amount')) * 100,
            'partner_trade_no' => $model->getAttr('code'),
            'spbill_create_ip' => '127.0.0.1',
            'check_name' => 'NO_CHECK',
            'desc' => '微信余额提现！',
        ]);
    }

    /**
     * 查询企业付款到银行卡
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @deprecated 微信商户已不再提供此接口
     */
    private function queryTransferBank(PluginWemallUserTransfer $model)
    {
        $result = TransfersBank::instance($this->getConfig($model))->query($model->getAttr('trade_no'));
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            if ($result['status'] === 'SUCCESS') {
                $model->save([
                    'status' => 5,
                    'trade_time' => $result['pay_succ_time'] ?: date('Y-m-d H:i:s'),
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_desc' => '微信提现打款成功',
                ]);
            } elseif (in_array($result['status'], ['FAILED', 'BANK_FAIL'])) {
                $model->save([
                    'status' => 0,
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_desc' => '微信提现打款失败',
                ]);
                // 刷新用户可提现余额
                UserRebate::recount($model->getAttr('unid'));
            }
        }
    }

    /**
     * 查询企业付款到零钱.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @deprecated 微信商户已不再提供此接口
     */
    private function queryTransferWallet(PluginWemallUserTransfer $model)
    {
        $result = Transfers::instance($this->getConfig($model))->query($model->getAttr('trade_no'));
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            $model->save([
                'status' => 5,
                'trade_time' => $result['payment_time'],
                'change_time' => date('Y-m-d H:i:s'),
                'change_desc' => '微信提现打款成功！',
            ]);
        }
    }

    /**
     * 获取微信提现参数.
     * @throws Exception
     */
    private function getConfig(PluginWemallUserTransfer $model): array
    {
        $data = sysdata('plugin.wemall.transfer.wxpay');
        if (empty($data)) {
            throw new Exception('未配置微信提现商户！');
        }
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
            'appid' => $model->getAttr('appid'),
            'mch_id' => $data['wechat_mch_id'],
            // V3 证书配置
            'mch_v3_key' => $data['wechat_mch_key'],
            'cert_private' => $local->path($file1, true),
            'cert_public' => $local->path($file2, true),
            // 设置缓存目录
            'cache_path' => syspath('safefile/wechat'),
        ];
    }
}
