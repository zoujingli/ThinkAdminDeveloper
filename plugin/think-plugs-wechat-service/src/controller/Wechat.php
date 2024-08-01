<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

namespace plugin\wechat\service\controller;

use plugin\wechat\service\AuthService;
use plugin\wechat\service\model\WechatAuth;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 公众号授权管理
 * Class Wechat
 * @package plugin\wechat\service\controller
 */
class Wechat extends Controller
{
    /**
     * 公众号授权管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        WechatAuth::mQuery()->layTable(function () {
            $this->title = '公众号授权管理';
        }, function (QueryHelper $query) {
            $query->like('authorizer_appid,user_nickname,user_company');
            $query->equal('service_type,service_verify')->timeBetween('auth_time#create_at');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 修改公众号状态
     * @auth true
     */
    public function state()
    {
        WechatAuth::mSave($this->_vali([
            'status.require' => '状态不能为空!',
        ]));
    }

    /**
     * 同步公众号授权信息
     * @auth true
     */
    public function sync()
    {
        try {
            $appid = $this->request->post('appid');
            $where = ['authorizer_appid' => $appid, 'deleted' => 0];
            $author = WechatAuth::mk()->where($where)->findOrEmpty()->toArray();
            if (empty($author)) $this->error('无效的授权公众号，请重新绑定授权！');
            $info = AuthService::WeOpenService()->getAuthorizerInfo($appid);
            $data = AuthService::buildAuthData(array_merge($info, ['authorizer_appid' => $appid]));
            $where = ['authorizer_appid' => $data['authorizer_appid']];
            $appkey = WechatAuth::mk()->where($where)->value('appkey');
            if (empty($appkey)) $data['appkey'] = md5(uniqid('', true));
            if (WechatAuth::mUpdate($data, 'authorizer_appid')) {
                $this->success('更新公众号授权成功！', '');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("获取授权信息失败，请稍候再试！<br>{$exception->getMessage()}");
        }
    }

    /**
     * 同步所有授权公众号
     * @auth true
     */
    public function queue()
    {
        $this->_queue("同步所有授权公众号数据", 'xsync:wechat');
    }

    /**
     * 重置公众号调用次数
     * @auth true
     */
    public function clear()
    {
        try {
            $appid = $this->request->post('appid');
            $result = AuthService::WeChatLimit($appid)->clearQuota();
            if (empty($result['errcode']) && $result['errmsg'] === 'ok') {
                $this->success('接口调用次数清零成功！');
            } elseif (isset($result['errmsg'])) {
                $this->error("接口调用次数清零失败！<br>{$result['errmsg']}");
            } else {
                $this->error('接口调用次数清零失败，请稍候再试！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("接口调用次数清零失败！<br>{$exception->getMessage()}");
        }
    }
}