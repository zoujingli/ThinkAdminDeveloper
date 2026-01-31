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
$extra = [];

return array_merge($extra, [
    // 通用
    '用户管理' => 'User Management',
    '回 收 站' => 'Recycle Bin',
    '排序权重' => 'Sort Weight',
    '头像' => 'Avatar',
    '账号状态' => 'Account Status',
    '操作面板' => 'Actions',
    '已激活' => 'Activated',
    '已禁用' => 'Disabled',
    '已启用' => 'Enabled',
    '已冻结的用户' => 'Frozen Users',
    '已激活的用户' => 'Activated Users',
    '删 除' => 'Delete',
    '保存数据' => 'Save Data',
    '取消编辑' => 'Cancel Edit',
    '保存配置' => 'Save Configuration',
    '取消修改' => 'Cancel Modification',
    '确定要取消编辑吗？' => 'Are you sure you want to cancel editing?',
    '确定要取消修改吗？' => 'Are you sure you want to cancel the modification?',
    '确定要永久删除此账号吗？' => 'Are you sure you want to permanently delete this account?',
    '全部' => 'All',
    '搜 索' => 'Search',
    '导 出' => 'Export',

    // 设备管理
    '账号接口配置' => 'Account Interface Configuration',
    '账号配置' => 'Account Configuration',
    '终端类型' => 'Device Type',
    '绑定手机' => 'Bound Mobile',
    '用户姓名' => 'User Name',
    '用户昵称' => 'User Nickname',
    '关联账号' => 'Associated Account',
    '使用状态' => 'Status',
    '首次登录' => 'First Login',
    '请输入绑定手机' => 'Please enter bound mobile',
    '请输入用户姓名' => 'Please enter user name',
    '请输入用户昵称' => 'Please enter user nickname',
    '请选择绑定时间' => 'Please select binding time',
    '用户账号数据' => 'User Account Data',

    // 主账号管理
    '用户编号' => 'User Code',
    '绑定邮箱' => 'Bound Email',
    '绑定时间' => 'Binding Time',
    '请输入用户编号' => 'Please enter user code',
    '请输入绑定邮箱' => 'Please enter bound email',

    // 消息管理
    '短信配置' => 'SMS Configuration',
    '消息编号' => 'Message Code',
    '短信类型' => 'SMS Type',
    '发送手机' => 'Send Mobile',
    '短信内容' => 'SMS Content',
    '发送时间' => 'Send Time',
    '发送失败' => 'Send Failed',
    '发送成功' => 'Send Success',
    '请输入消息编号' => 'Please enter message code',
    '请输入发送手机' => 'Please enter send mobile',
    '请输入短信内容' => 'Please enter SMS content',
    '请选择发送时间' => 'Please select send time',

    // 短信配置
    '服务区域' => 'Service Region',
    '阿里云账号' => 'Aliyun Account',
    '阿里云密钥' => 'Aliyun Secret Key',
    '短信签名' => 'SMS Signature',
    '短信模板编号' => 'SMS Template Code',
    '请输入阿里云账号' => 'Please enter Aliyun account',
    '请输入阿里云密钥' => 'Please enter Aliyun secret key',
    '请输入短信签名' => 'Please enter SMS signature',
    '请输入短信模板编号' => 'Please enter SMS template code',

    // 账号配置
    '认证有效时间' => 'Authentication Expire Time',
    '登录自动注册' => 'Auto Register on Login',
    '默认昵称前缀' => 'Default Nickname Prefix',
    '默认用户头像' => 'Default User Avatar',
    '开放接口通道' => 'Open Interface Channels',
    '设置为 0 表示永不过期，建议设置有效时间达到系统自动回收令牌。' => 'Set to 0 means never expires. It is recommended to set an expiration time for automatic token recycling.',
    '启用自动登录时，通过验证码登录时账号不存在会自动创建！' => 'When auto login is enabled, accounts that do not exist will be automatically created when logging in with verification code!',
    '用户绑定账号后会自动使用此前缀与手机号后4位拼接为新默认昵称。' => 'After user binds account, this prefix will be automatically combined with the last 4 digits of mobile number as new default nickname.',
    '当用户未设置头像时，自动使用此头像设置的图片链接。' => 'When user has not set avatar, automatically use the image link set in this avatar.',
    '请输入默认昵称前缀' => 'Please enter default nickname prefix',
]);
