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

namespace plugin\account\controller\api;

use plugin\account\service\Account;
use plugin\account\service\WxappService;
use think\admin\Controller;
use think\exception\HttpResponseException;
use think\Response;

/**
 * 微信小程序入口.
 * @class Wxapp
 */
class Wxapp extends Controller
{
    /**
     * 接口通道类型.
     * @var string
     */
    private $type = Account::WXAPP;

    /**
     * 小程序服务.
     * @var WxappService
     */
    private $wxapp;

    /**
     * 换取会话.
     */
    public function session()
    {
        try {
            $input = $this->_vali(['code.require' => '凭证编码为空']);
            $session = $this->wxapp->getSession($input['code']);
            $data = [
                'appid' => $this->wxapp->getAppid(),
                'openid' => $session['openid'],
                'unionid' => $session['unionid'] ?? '',
                'session_key' => $session['session_key'],
            ];
            $this->successWithToken('授权换取成功', Account::mk($this->type)->set($data, true));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error("处理失败，{$exception->getMessage()}");
        }
    }

    /**
     * 数据解密.
     */
    public function decode()
    {
        try {
            $input = $this->_vali([
                'iv.require' => '解密向量为空',
                'code.require' => '授权编码为空',
                'encrypted.require' => '密文内容为空',
            ]);
            $session = $this->wxapp->getSession($input['code']);
            $result = $this->wxapp->decode($input['iv'], strval($session['session_key']), $input['encrypted']);
            if (is_array($result) && isset($result['avatarUrl'], $result['nickName'])) {
                $data = [
                    'extra' => $result,
                    'appid' => $this->wxapp->getAppid(),
                    'openid' => $session['openid'],
                    'unionid' => $session['unionid'] ?? '',
                    'headimg' => $result['avatarUrl'],
                    'nickname' => $result['nickName'],
                ];
                if ($data['nickname'] === '微信用户') {
                    unset($data['headimg'], $data['nickname']);
                }
                $this->successWithToken('解密成功', Account::mk($this->type)->set($data, true));
            } elseif (is_array($result)) {
                if (!empty($result['phoneNumber'])) {
                    $data = ['appid' => $this->wxapp->getAppid(), 'openid' => $session['openid'], 'unionid' => $session['unionid'] ?? ''];
                    ($account = Account::mk($this->type))->set($data);
                    $account->bind(['phone' => $result['phoneNumber']], $data);
                    $this->successWithToken('绑定成功', $account->get(true));
                } else {
                    $this->success('解密成功', $result);
                }
            } else {
                $this->error('解析失败');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error("处理失败，{$exception->getMessage()}");
        }
    }

    /**
     * 快速获取手机号.
     */
    public function phone()
    {
        try {
            $input = $this->_vali([
                'code.require' => '授权编码为空',
                'openid.require' => '用户编号为空',
            ]);
            $result = $this->wxapp->getPhoneNumber($input['code']);
            if (is_array($result)) {
                $this->success('解密成功', $result);
            } else {
                $this->error('解析失败');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error("处理失败，{$exception->getMessage()}");
        }
    }

    /**
     * 获取小程序码
     * @return Response|void
     */
    public function qrcode(): Response
    {
        try {
            $data = $this->_vali([
                'size.default' => 430,
                'type.default' => 'base64',
                'path.require' => '跳转链接为空',
            ]);
            $result = $this->wxapp->createMiniPath($data['path'], intval($data['size']));
            if ($data['type'] === 'base64') {
                $this->success('生成小程序码', ['base64' => 'data:image/png;base64,' . base64_encode($result)]);
            } else {
                return response($result)->contentType('image/png');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取直播列表.
     */
    public function getLiveList()
    {
        try {
            $data = $this->_vali(['start.default' => 0, 'limit.default' => 10]);
            $list = $this->wxapp->getLiveList(intval($data['start']), intval($data['limit']));
            $this->success('直播列表', $list);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取回放源视频.
     */
    public function getLiveInfo()
    {
        try {
            $data = $this->_vali([
                'start.default' => 0,
                'limit.default' => 10,
                'action.default' => 'get_replay',
                'room_id.require' => '直播间号为空',
            ]);
            $result = $this->wxapp->getLiveInfo($data);
            $this->success('回放列表', $result);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    /**
     * 接口初始化.
     * @throws \think\admin\Exception
     */
    protected function initialize()
    {
        if (Account::field($this->type)) {
            $this->wxapp = WxappService::instance();
        } else {
            $this->error('接口未开通');
        }
    }

    /**
     * 返回带账号令牌的成功结果并同步 Cookie。
     */
    private function successWithToken(string $info, array $data): void
    {
        Account::syncTokenCookie(strval($data['token'] ?? ''));
        $this->success($info, $data);
    }
}
