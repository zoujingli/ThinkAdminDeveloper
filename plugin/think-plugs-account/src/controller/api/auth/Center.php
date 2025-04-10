<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\controller\api\auth;

use plugin\account\controller\api\Auth;
use plugin\account\model\PluginAccountAuth;
use plugin\account\model\PluginAccountBind;
use plugin\account\service\Message;
use think\admin\service\RuntimeService;
use think\admin\Storage;
use think\exception\HttpResponseException;

/**
 * 用户账号管理
 * @class Center
 * @package plugin\account\controller\api\auth
 */
class Center extends Auth
{
    /**
     * 获取账号信息
     * @return void
     */
    public function get()
    {
        $this->success('获取资料', $this->account->get());
    }

    /**
     * 修改帐号信息
     * @return void
     */
    public function set()
    {
        try {
            $data = $this->checkUserStatus()->_vali([
                'headimg.default'     => '',
                'nickname.default'    => '',
                'password.default'    => '',
                'region_prov.default' => '',
                'region_city.default' => '',
                'region_area.default' => '',
            ]);
            // 保存用户头像
            if (!empty($data['headimg'])) {
                $data['headimg'] = Storage::saveImage($data['headimg'], 'headimg')['url'] ?? '';
            }
            // 修改登录密码
            if (!empty($data['password']) && strlen($data['password']) > 4) {
                $this->account->pwdModify($data['password']);
                unset($data['password']);
            }
            foreach ($data as $k => $v) if ($v === '') unset($data[$k]);
            if (empty($data)) $this->success('无需修改', $this->account->get());
            $this->success('修改成功', $this->account->bind(['id' => $this->unid], $data));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 注销当前账号
     * @return void
     */
    public function forbid()
    {
        if (($user = $this->account->user())->isExists()) try {
            $this->app->db->transaction(function () use ($user) {
                $user->save(['deleted' => 1, 'remark' => '用户主动申请注销账号！']);
                PluginAccountAuth::mk()->where(['usid' => $this->usid])->delete();
                PluginAccountBind::mk()->where(['unid' => $this->unid])->delete();
            });
            $this->success('账号注销成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        } else {
            $this->error('未完成注册！');
        }
    }

    /**
     * 绑定主账号
     * @return void
     */
    public function bind()
    {
        try {
            $data = $this->_vali([
                'phone.mobile'   => '手机号错误',
                'phone.require'  => '手机号为空',
                'verify.require' => '验证码为空',
                'passwd.default' => ''
            ]);
            if (Message::checkVerifyCode($data['verify'], $data['phone'])) {
                Message::clearVerifyCode($data['phone']);
                $map = $bind = ['phone' => $data['phone']];
                if (!$this->account->isBind()) {
                    $user = $this->account->get();
                    $bind['headimg'] = $user['headimg'];
                    $bind['unionid'] = $user['unionid'];
                    $bind['nickname'] = $user['nickname'];
                }
                $this->account->set($map);
                $this->account->bind($map, $bind);
                if (!empty($data['passwd'])) {
                    $this->account->pwdModify($data['passwd']);
                }
                $this->success('关联成功!', $this->account->get(true));
            } else {
                $this->error('验证失败');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 解除账号关联
     * @return void
     */
    public function unbind()
    {
        $this->account->unBind();
        $this->success('关联成功', $this->account->get());
    }
}