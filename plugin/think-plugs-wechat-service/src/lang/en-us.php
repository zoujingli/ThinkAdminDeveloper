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
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------

$extra = [];

return array_merge($extra, [
    // 通用
    '操作面板' => 'Actions',
    '已激活' => 'Activated',
    '已禁用' => 'Disabled',
    '同 步' => 'Sync',
    '清 零' => 'Clear',
    '回收站' => 'Recycle Bin',
    '公众号' => 'Official Account',
    '确定要同步授权微信吗？' => 'Are you sure you want to sync authorized WeChat?',
    '每个微信每个月有10次清零机会，请谨慎使用！' => 'Each WeChat account has 10 clear opportunities per month, please use with caution!',

    // 微信授权
    '同步授权微信' => 'Sync Authorized WeChat',
    '接口信息' => 'Interface Information',
    '公众号APPID' => 'Official Account APPID',
    '已请求' => 'Requested',
    '次' => 'times',
    '平台接口密钥' => 'Platform Interface Key',
    '未生成平台接口密钥, 请稍候授权绑定' => 'Platform interface key not generated, please wait for authorization binding',
    '消息推送地址' => 'Message Push Address',
    '未配置消息推送地址' => 'Message push address not configured',
    '公众号信息' => 'Official Account Information',
    '未获取到微信昵称' => 'WeChat nickname not obtained',
    '完成授权' => 'Authorization Completed',
    '未认证' => 'Not Verified',
    '已认证' => 'Verified',

    // 搜索
    '公众号ID' => 'Official Account ID',
    '请输入APPID' => 'Please enter APPID',
    '微信名称' => 'WeChat Name',
    '请输入微信名称' => 'Please enter WeChat name',
    '公司名称' => 'Company Name',
    '请输入公司名称' => 'Please enter company name',
    '认证类型' => 'Verification Type',
    '- 全部 -' => '- All -',
    '订阅号' => 'Subscription Account',
    '服务号' => 'Service Account',
    '小程序' => 'Mini Program',
    '认证状态' => 'Verification Status',
    '授权时间' => 'Authorization Time',
    '请选择授权时间' => 'Please select authorization time',
    '于' => 'At',

    // 配置
    '开放平台配置' => 'Open Platform Configuration',
    '微信开放平台对接参数及客户端接口网关地址，面向客户端系统支持 Yar、JsonRpc、WebService 接口方式调用。' => 'WeChat Open Platform docking parameters and client interface gateway address, supporting Yar, JsonRpc, WebService interface methods for client systems.',
    '开放平台账号' => 'Open Platform Account',
    '未配置' => 'Not Configured',
    '开放平台服务 AppId，需要在微信开放平台获取' => 'Open Platform Service AppId, needs to be obtained from WeChat Open Platform',
    '开放平台密钥' => 'Open Platform Secret',
    '开放平台服务 AppSecret，需要在微信开放平台获取' => 'Open Platform Service AppSecret, needs to be obtained from WeChat Open Platform',
    '开放平台消息校验' => 'Open Platform Message Verification',
    '开发者在代替微信接收到消息时，用此 TOKEN 来校验消息' => 'When developers receive messages on behalf of WeChat, use this TOKEN to verify messages',
    '开放平台消息加解密' => 'Open Platform Message Encryption/Decryption',
    '在代替微信收发消息时使用，必须是长度为43位字母和数字组合的字符串' => 'Used when sending and receiving messages on behalf of WeChat, must be a 43-character string of letters and numbers',
    '授权白名单IP地址' => 'Authorization Whitelist IP Address',
    '需要在开放平台配置此IP地址后才能调用开放平台的接口哦' => 'This IP address needs to be configured in the Open Platform before calling the Open Platform interface',
    '授权发起页域名' => 'Authorization Initiation Page Domain',
    '微信开放平台对接所需参数，从本域名跳转到登录授权页才可以完成登录授权，无需填写域名协议前缀' => 'Required parameter for WeChat Open Platform docking. Jump from this domain to the login authorization page to complete login authorization. No need to fill in domain protocol prefix',
    '授权事件接收地址' => 'Authorization Event Receiving Address',
    '微信开放平台对接所需参数，用于接收取消授权通知、授权成功通知、授权更新通知、接收 TICKET 凭据' => 'Required parameter for WeChat Open Platform docking, used to receive authorization cancellation notifications, authorization success notifications, authorization update notifications, and receive TICKET credentials',
    '微信消息接收地址' => 'WeChat Message Receiving Address',
    '微信开放平台对接所需参数，通过该 URL 接收微信消息和事件推送，$APPID$ 将被替换为微信 AppId' => 'Required parameter for WeChat Open Platform docking. Receive WeChat messages and event pushes through this URL. $APPID$ will be replaced with WeChat AppId',
    '微信授权绑定跳转入口' => 'WeChat Authorization Binding Redirect Entry',
    '应用插件 ThinkPlugsWechat 对接所需参数，使用微信第三方授权时会跳转到这个页面，由微信管理员进行扫码授权' => 'Required parameter for ThinkPlugsWechat plugin docking. When using WeChat third-party authorization, it will jump to this page for WeChat administrator to scan code for authorization',
    '客户端系统 Yar 调用接口' => 'Client System Yar Call Interface',
    '应用插件 ThinkPlugsWechat 对接所需参数，客户端 Yar 接口，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechat plugin docking. Client Yar interface. TOKEN contains encrypted content of class appid time nostr sign',
    '客户端系统 Soap 调用接口' => 'Client System Soap Call Interface',
    '应用插件 ThinkPlugsWechat 对接所需参数，客户端 Soap 接口，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechat plugin docking. Client Soap interface. TOKEN contains encrypted content of class appid time nostr sign',
    '客户端系统 JsonRpc 调用接口' => 'Client System JsonRpc Call Interface',
    '应用插件 ThinkPlugsWechat 对接所需参数，客户端 JsonRpc 接口链接，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechat plugin docking. Client JsonRpc interface link. TOKEN contains encrypted content of class appid time nostr sign',

    // 未授权页面
    '还没有授权，请授权公众号' => 'Not yet authorized, please authorize the official account',
]);
