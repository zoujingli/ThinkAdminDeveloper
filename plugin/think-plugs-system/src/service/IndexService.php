<?php

declare(strict_types=1);

namespace plugin\system\service;

use plugin\system\model\SystemUser;
use think\admin\Exception;
use think\admin\Service;
use think\admin\service\AppService;

/**
 * 后台首页与个人入口服务。
 * @class IndexService
 */
class IndexService extends Service
{
    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function buildShellContext(bool $debug = false): array
    {
        AuthService::apply($debug);
        $menus = MenuService::getTree();
        $login = AuthService::isLogin();
        $site = ConfigService::getSiteConfig();
        $userId = AuthService::getUserId();
        $username = strval(AuthService::getUser('username', ''));
        $nickname = trim(strval(AuthService::getUser('nickname', '')));
        $displayName = $nickname !== '' ? $nickname : $username;

        return [
            'menus' => $menus,
            'login' => $login,
            'redirectUrl' => empty($menus) && empty($login) ? sysuri('system/login/index') : '',
            'title' => strval(lang('系统管理后台')),
            'pageTitle' => strval(lang('系统管理后台')),
            'super' => AuthService::isSuper(),
            'theme' => AuthService::getUserTheme(),
            'tokenValueJson' => json_encode(AuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'staticRoot' => AppService::uri('static'),
            'websiteName' => strval($site['website_name'] ?? 'ThinkAdmin'),
            'applicationName' => strval($site['application_name'] ?? 'ThinkAdmin'),
            'applicationVersion' => strval($site['application_version'] ?? ''),
            'browserIcon' => strval($site['browser_icon'] ?? ''),
            'homeUrl' => sysuri('@'),
            'loginUrl' => sysuri('system/login/index'),
            'hasUser' => $username !== '',
            'currentUserId' => $userId,
            'currentUserName' => $username,
            'currentUserDisplayName' => $displayName,
            'currentUserHeadimg' => strval(AuthService::getUser('headimg', '')),
            'profileUrl' => $userId > 0 ? sysuri('system/index/info', ['id' => $userId]) : '',
            'passwordUrl' => $userId > 0 ? sysuri('system/index/pass', ['id' => $userId]) : '',
            'themeUrl' => sysuri('system/index/theme'),
            'logoutUrl' => sysuri('system/login/out'),
        ];
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @return array<string, mixed>
     */
    public static function buildThemeContext(array $themes): array
    {
        $scene = strval(request()->param('scene', 'user'));
        $theme = strval(request()->param('value', ''));
        if (!isset($themes[$theme])) {
            $theme = $scene === 'config'
                ? ConfigService::getSiteTheme($themes)
                : AuthService::getUserTheme();
        }
        if (!isset($themes[$theme])) {
            $theme = 'default';
        }

        return [
            'scene' => $scene === 'config' ? 'config' : 'user',
            'picker' => strval(request()->param('picker', '')),
            'theme' => $theme,
            'themes' => $themes,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public static function buildThemeFormData(array $context): array
    {
        return ['site_theme' => strval($context['theme'] ?? 'default')];
    }

    /**
     * @param array<string, array<string, string>> $themes
     * @throws Exception
     */
    public static function saveTheme(string $theme, array $themes): void
    {
        $theme = trim($theme);
        if ($theme === '' || !isset($themes[$theme])) {
            throw new Exception(lang('主题方案不存在！'));
        }
        if (!AuthService::setUserTheme($theme)) {
            throw new Exception(lang('主题配置保存失败！'));
        }
    }

    /**
     * @throws Exception
     */
    public static function assertCurrentUser(int $id, string $message): void
    {
        if (AuthService::getUserId() !== $id) {
            throw new Exception(lang($message));
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public static function changeOwnPassword(int $id, array $data): void
    {
        $user = SystemUser::mk()->findOrEmpty($id);
        if ($user->isEmpty()) {
            throw new Exception(lang('用户不存在！'));
        }
        if (!UserService::verifyPassword(strval($data['oldpassword'] ?? ''), strval($user['password']))) {
            throw new Exception(lang('旧密码验证失败，请重新输入！'));
        }
        if (!$user->save(['password' => UserService::hashPassword(strval($data['password'] ?? ''))])) {
            throw new Exception(lang('密码修改失败，请稍候再试！'));
        }

        sysoplog('系统用户管理', "修改用户[{$user['id']}]密码成功");
        app()->event->trigger('PluginAdminChangePassword', [
            'uuid' => intval($user['id']),
            'pass' => strval($data['password'] ?? ''),
        ]);
    }
}
