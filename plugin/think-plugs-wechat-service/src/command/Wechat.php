<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wechat\service\command;

use plugin\wechat\service\AuthService;
use plugin\wechat\service\model\WechatAuth;
use think\admin\Command;
use think\console\Input;
use think\console\Output;

/**
 * 同步公众号授权记录
 * Class WeChat
 * @package plugin\wechat\service\command
 */
class Wechat extends Command
{
    protected function configure()
    {
        $this->setName('xsync:wechat');
        $this->setDescription('同步所有已授权的公众号信息');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        [$offset, $wechat] = [0, AuthService::WeOpenService()];
        do {
            $data = $wechat->getAuthorizerList(500, $offset);
            if (!isset($data['total_count'])) {
                $this->queue->error($data['errmsg'] ?? '接口调用异常！');
            }
            foreach ($data['list'] ?? [] as $item) {
                $this->queue->message($data['total_count'] ?? 0, ++$offset, "公众号 {$item['authorizer_appid']} 开始同步数据");
                $config = WechatAuth::mk()->where(['authorizer_appid' => $item['authorizer_appid']])->find();
                if (isset($item['refresh_token']) && isset($item['auth_time'])) {
                    $info = array_merge(AuthService::buildAuthData($wechat->getAuthorizerInfo($item['authorizer_appid'])), [
                        'authorizer_appid' => $item['authorizer_appid'], 'authorizer_refresh_token' => $item['refresh_token'], 'auth_time' => $item['auth_time'], 'deleted' => 0,
                    ]);
                    if (empty($config) || empty($config['appkey'])) {
                        $info['appkey'] = md5(uniqid('', true) . rand(1000, 9999));
                    }
                    $state = ($config ?: WechatAuth::mk())->save($info) ? '成功' : '失败';
                    $this->queue->message($data['total_count'] ?? 0, $offset, "公众号 {$item['authorizer_appid']} 更新授权{$state}", 1);
                } else {
                    empty($config) or $config->save(['status' => 0]);
                    $this->queue->message($data['total_count'] ?? 0, $offset, "公众号 {$item['authorizer_appid']} 已经取消授权", 1);
                }
            }
        } while ($offset < $data['total_count'] ?? 0);
    }
}