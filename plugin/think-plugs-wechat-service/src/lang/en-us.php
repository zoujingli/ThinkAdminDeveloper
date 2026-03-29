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
    '状态不能为空！' => 'Status is required',
    '同 步' => 'Sync',
    '清 零' => 'Clear',
    '复制' => 'Copy',
    '搜 索' => 'Search',
    '回收站' => 'Recycle Bin',
    '公众号' => 'Official Account',
    '公众号授权管理' => 'Authorized Official Account Management',
    '参数修改成功！' => 'Parameters updated successfully',
    '成功' => 'Success',
    '失败' => 'Failed',
    '确定要同步授权微信吗？' => 'Are you sure you want to sync authorized WeChat?',
    '每个微信每个月有10次清零机会，请谨慎使用！' => 'Each WeChat account has 10 clear opportunities per month, please use with caution!',
    '同步所有授权公众号数据' => 'Sync all authorized official account data',
    '同步所有已授权的公众号信息' => 'Sync all authorized official account information',
    '更新公众号授权成功！' => 'Official account authorization updated successfully',
    '无效的授权公众号，请重新绑定授权！' => 'Invalid authorized official account, please bind it again',
    '获取授权信息失败，请稍候再试！<br>%s' => 'Failed to get authorization info. Please try again later.<br>%s',
    '接口调用次数清零成功！' => 'API call quota cleared successfully',
    '接口调用次数清零失败！<br>%s' => 'Failed to clear API call quota.<br>%s',
    '接口调用次数清零失败，请稍候再试！' => 'Failed to clear API call quota. Please try again later',
    '接口调用异常！' => 'API call failed',
    '微信开放平台' => 'WeChat Open Platform',
    'yyyy年MM月dd日 HH:mm:ss' => 'yyyy-MM-dd HH:mm:ss',
    '请求 TOKEN 格式错误！' => 'Request TOKEN format error',
    '请求 TOKEN 格式异常！' => 'Request TOKEN payload is invalid',
    '该公众号还未授权，请重新授权！' => 'This official account is not authorized yet, please re-authorize',
    '该公众号已被禁用，请联系管理员！' => 'This official account has been disabled, please contact the administrator',
    '请求时间与服务器时差过大，请同步时间！' => 'The request time differs too much from the server time, please sync the clock',
    '该公众号%s请求签名异常！' => 'The request signature for official account %s is invalid',
    '公众号%s还没有授权！' => 'Official account %s is not authorized yet',
    '公众号%s开始同步数据' => 'Official account %s starts syncing data',
    '公众号%s更新授权%s' => 'Official account %s updates authorization: %s',
    '公众号%s已经取消授权' => 'Official account %s has been unauthorized',
    '请传入微信通知URL' => 'Please provide the WeChat notify URL',
    'Ticket 事件处理失败。' => 'Ticket event handling failed.',
    'Ticket 事件处理失败，%s' => 'Ticket event handling failed, %s',
    '网页授权失败，无法进一步操作！' => 'Web authorization failed and cannot continue',
    '网页授权信息获取失败，无法进一步操作！' => 'Failed to retrieve web authorization information and cannot continue',
    '请传入回跳 source 参数（请使用 enbase64url 加密）' => 'Please provide the return source parameter (please use enbase64url encryption)',
    '创建授权链接失败，请返回重试。' => 'Failed to create the authorization link. Please go back and try again.',
    '接收微信第三方平台授权失败！' => 'Failed to receive WeChat third-party platform authorization',
    '获取授权数据失败，请稍候再试！' => 'Failed to get authorization data. Please try again later',
    '未配置授权成功后的回跳地址！' => 'Redirect URL after successful authorization is not configured',

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
    '开放平台账号不能为空！' => 'Open platform account is required',
    '开放平台账号格式错误！' => 'Open platform account format is invalid',
    '开放平台密钥不能为空！' => 'Open platform secret is required',
    '开放平台密钥格式错误！' => 'Open platform secret format is invalid',
    '开放平台消息校验不能为空！' => 'Open platform message verification token is required',
    '开放平台消息加解密不能为空！' => 'Open platform message encryption key is required',
    '开放平台消息加解密格式错误！' => 'Open platform message encryption key format is invalid',
    '开放平台账号 AppID，需要在微信开放平台获取。' => 'Open platform account AppID, available from WeChat Open Platform.',
    '开放平台密钥 AppSecret，需要在微信开放平台获取。' => 'Open platform AppSecret, available from WeChat Open Platform.',
    '开发者在代替微信接收到消息时，用此 TOKEN 来校验消息。' => 'When developers receive messages on behalf of WeChat, use this TOKEN to verify the message.',
    '在代替微信收发消息时使用，必须是长度为 43 位字母和数字组合的字符串。' => 'Used when sending and receiving messages on behalf of WeChat. It must be a 43-character alphanumeric string.',
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
    '应用插件 ThinkPlugsWechatClient 对接所需参数，使用微信第三方授权时会跳转到这个页面，由微信管理员进行扫码授权' => 'Required parameter for ThinkPlugsWechatClient plugin docking. When using WeChat third-party authorization, it will jump to this page for WeChat administrator to scan code for authorization',
    '客户端系统 Yar 调用接口' => 'Client System Yar Call Interface',
    '应用插件 ThinkPlugsWechatClient 对接所需参数，客户端 Yar 接口，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechatClient plugin docking. Client Yar interface. TOKEN contains encrypted content of class appid time nostr sign',
    '客户端系统 Soap 调用接口' => 'Client System Soap Call Interface',
    '应用插件 ThinkPlugsWechatClient 对接所需参数，客户端 Soap 接口，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechatClient plugin docking. Client Soap interface. TOKEN contains encrypted content of class appid time nostr sign',
    '客户端系统 JsonRpc 调用接口' => 'Client System JsonRpc Call Interface',
    '应用插件 ThinkPlugsWechatClient 对接所需参数，客户端 JsonRpc 接口链接，TOKEN 包含 class appid time nostr sign 的加密内容' => 'Required parameter for ThinkPlugsWechatClient plugin docking. Client JsonRpc interface link. TOKEN contains encrypted content of class appid time nostr sign',
    'Ticket 推送时间' => 'Ticket Push Time',

    // 未授权页面
    '还没有授权，请授权公众号' => 'Not yet authorized, please authorize the official account',
]);
