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

namespace plugin\system\service;

use plugin\system\model\SystemMenu;
use think\admin\Exception;
use think\admin\extend\ArrayTree;
use think\admin\extend\FileTools;
use think\admin\helper\QueryHelper;
use think\admin\Library;
use think\admin\Service;
use think\admin\service\AppService;
use think\admin\service\NodeService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\helper\Str;

/**
 * 系统菜单管理服务
 * @class MenuService
 */
class MenuService extends Service
{
    /**
     * 构建菜单列表上下文.
     * @return array<string, mixed>
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function buildIndexContext(): array
    {
        $type = self::normalizeIndexType(strval(request()->get('type', 'index')));
        $pid = trim(strval(request()->get('pid', '')));
        return [
            'title' => strval(lang('系统菜单管理')),
            'type' => $type,
            'pid' => $pid,
            'requestBaseUrl' => request()->baseUrl(),
            'indexUrl' => url('index')->build(),
            'menuRootList' => self::getRoots(),
        ];
    }

    /**
     * 构建菜单表单上下文.
     * @return array<string, mixed>
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function buildFormContext(string $action): array
    {
        $id = intval(request()->param('id', 0));
        $debug = self::instance()->app->isDebug();
        if ($debug && request()->isGet()) {
            AuthService::clear();
        }

        return [
            'action' => $action,
            'id' => $id,
            'isEdit' => $action === 'edit' || $id > 0,
            'actionUrl' => url($action, array_filter(['id' => $id ?: null]))->build(),
            'iconPickerUrl' => apiuri('system/plugs/icon'),
            'menus' => self::resolveParentMenus($id),
            'nodes' => self::getList($debug),
            'auths' => self::getAuths($debug),
        ];
    }

    /**
     * 应用菜单列表查询.
     * @param array<string, mixed> $context
     */
    public static function applyIndexQuery(QueryHelper $query, array $context = []): void
    {
    }

    /**
     * 列表数据处理.
     * @param array<int, array<string, mixed>> $data
     */
    public static function filterIndexData(array &$data): void
    {
        $type = self::normalizeIndexType(strval(request()->get('type', 'index')));
        $pid = trim(strval(request()->get('pid', '')));

        $data = ArrayTree::arr2tree($data);
        if ($type === 'recycle') {
            foreach ($data as $k1 => &$p1) {
                if (!empty($p1['sub'])) {
                    foreach ($p1['sub'] as $k2 => &$p2) {
                        if (!empty($p2['sub'])) {
                            foreach ($p2['sub'] as $k3 => $p3) {
                                if (intval($p3['status'] ?? 0) > 0) {
                                    unset($p2['sub'][$k3]);
                                }
                            }
                        }
                        if (empty($p2['sub']) && (($p2['url'] ?? '#') === '#' || intval($p2['status'] ?? 0) > 0)) {
                            unset($p1['sub'][$k2]);
                        }
                    }
                }
                if (empty($p1['sub']) && (($p1['url'] ?? '#') === '#' || intval($p1['status'] ?? 0) > 0)) {
                    unset($data[$k1]);
                }
            }
            unset($p2, $p1);
        }

        $data = ArrayTree::arr2table($data);
        if ($type === 'index' && $pid !== '') {
            $data = array_values(array_filter($data, static function (array $item) use ($pid): bool {
                return strpos(strval($item['spp'] ?? ''), ",{$pid},") !== false;
            }));
        }

        foreach ($data as &$item) {
            $url = strval($item['url'] ?? '#');
            if ($url !== '#' && !preg_match('/^(https?:)?(\/\/|\\\)/i', $url)) {
                $item['url'] = trim(strval(url($url)) . ($item['params'] ? "?{$item['params']}" : ''), '\/');
            }
        }
        unset($item);
    }

    /**
     * 加载菜单表单数据.
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function loadFormData(array $context): array
    {
        $id = intval($context['id'] ?? 0);
        $data = [
            'pid' => intval(request()->param('pid', 0)),
            'url' => '#',
            'target' => '_self',
            'sort' => 0,
            'status' => 1,
        ];
        if ($id < 1) {
            return $data;
        }

        $menu = SystemMenu::mk()->findOrEmpty($id);
        if ($menu->isEmpty()) {
            throw new Exception(lang('菜单数据不存在！'));
        }

        $loaded = array_merge($data, $menu->toArray());
        [$loaded['url'], $loaded['params']] = self::normalizeMenuTarget(
            strval($loaded['url'] ?? '#'),
            strval($loaded['params'] ?? '')
        );
        $loaded['node'] = self::normalizePermissionNode(strval($loaded['node'] ?? ''));

        return $loaded;
    }

    /**
     * 整理菜单表单数据.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function prepareFormData(array $data, array $context): array
    {
        $id = intval($context['id'] ?? 0);
        if ($id > 0 && SystemMenu::mk()->findOrEmpty($id)->isEmpty()) {
            throw new Exception(lang('菜单数据不存在！'));
        }

        $target = trim(strval($data['target'] ?? request()->post('target', '_self')));
        if (!in_array($target, ['_self', '_blank'], true)) {
            throw new Exception(lang('打开方式异常！'));
        }

        $status = intval($data['status'] ?? request()->post('status', 1));
        if (!in_array($status, [0, 1], true)) {
            throw new Exception(lang('状态值范围异常！'));
        }

        $title = trim(strval($data['title'] ?? ''));
        if ($title === '') {
            throw new Exception(lang('菜单名称不能为空！'));
        }

        $pid = self::normalizeParentId(intval($data['pid'] ?? request()->post('pid', 0)), $id);
        [$url, $params] = self::normalizeMenuTarget(
            strval($data['url'] ?? '#'),
            strval($data['params'] ?? '')
        );
        $node = self::normalizePermissionNode(strval($data['node'] ?? ''));
        if ($node !== '') {
            self::assertPermissionNodeAvailable($node);
        }

        return [
            'id' => $id,
            'pid' => $pid,
            'title' => $title,
            'icon' => trim(strval($data['icon'] ?? '')),
            'node' => $node,
            'url' => $url,
            'params' => $params,
            'target' => $target,
            'sort' => intval($data['sort'] ?? request()->post('sort', 0)),
            'status' => $status,
        ];
    }

    /**
     * 保存菜单表单数据.
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public static function saveFormData(array $data): void
    {
        $id = intval($data['id'] ?? 0);
        $menu = $id > 0 ? SystemMenu::mk()->findOrEmpty($id) : SystemMenu::mk();
        if ($id > 0 && $menu->isEmpty()) {
            throw new Exception(lang('菜单数据不存在！'));
        }

        $exists = $menu->isExists();
        if ($menu->save([
            'pid' => intval($data['pid'] ?? 0),
            'title' => strval($data['title'] ?? ''),
            'icon' => strval($data['icon'] ?? ''),
            'node' => strval($data['node'] ?? ''),
            'url' => strval($data['url'] ?? '#'),
            'params' => strval($data['params'] ?? ''),
            'target' => strval($data['target'] ?? '_self'),
            'sort' => intval($data['sort'] ?? 0),
            'status' => intval($data['status'] ?? 1),
        ]) === false) {
            throw new Exception(lang('数据保存失败，请稍候再试！'));
        }

        $action = $exists ? 'onAdminUpdate' : 'onAdminInsert';
        $menu->{$action}(strval($menu->getAttr('id')));
    }

    /**
     * 获取可选菜单节点.
     * @param bool $force 强制刷新
     */
    public static function getList(bool $force = false, ?string $plugin = null): array
    {
        $plugin = self::pluginCode($plugin);
        $keys = 'think.admin.menus' . ($plugin === '' ? '' : ".{$plugin}");
        $nodes = sysvar($keys) ?: [];
        if (empty($force) && count($nodes) > 0) {
            return $nodes;
        }
        $nodes = [];
        foreach (NodeService::getMethods($force) as $node => $method) {
            if ($method['ismenu'] && self::allowNode($node, $plugin)) {
                $nodes[] = ['node' => $node, 'title' => self::lang($method['title'])];
            }
        }
        return sysvar($keys, $nodes);
    }

    /**
     * 获取可选权限节点.
     * @param bool $force 强制刷新
     */
    public static function getAuths(bool $force = false, ?string $plugin = null): array
    {
        $plugin = self::pluginCode($plugin);
        $keys = 'think.admin.auths' . ($plugin === '' ? '' : ".{$plugin}");
        $nodes = sysvar($keys) ?: [];
        if (empty($force) && count($nodes) > 0) {
            return $nodes;
        }

        $nodes = [];
        foreach (NodeService::getMethods($force) as $node => $method) {
            if ($method['isauth'] && substr_count($node, '/') >= 2 && self::allowNode($node, $plugin)) {
                $nodes[] = ['node' => $node, 'title' => $method['title']];
            }
        }
        return sysvar($keys, $nodes);
    }

    /**
     * 获取有菜单声明的插件列表.
     */
    public static function getPlugins(bool $force = false): array
    {
        $items = [];
        foreach (AppService::all($force) as $code => $plugin) {
            if (empty(AppService::menus($plugin))) {
                continue;
            }
            $items[$code] = [
                'code' => $code,
                'name' => strval($plugin['name'] ?? $code),
                'type' => strval($plugin['type'] ?? 'plugin'),
                'prefix' => strval($plugin['prefix'] ?? ''),
            ];
        }
        ksort($items);
        return $items;
    }

    /**
     * 获取顶级菜单列表.
     */
    public static function getRoots(?string $plugin = null): array
    {
        $roots = [];
        foreach (self::filterTree(self::loadTree(), $plugin) as $menu) {
            $roots[$menu['id']] = [
                'id' => $menu['id'],
                'pid' => $menu['pid'],
                'title' => self::lang(strval($menu['title'] ?? '')),
            ];
        }
        return $roots;
    }

    /**
     * 获取插件上下文下的上级菜单选项.
     */
    public static function getParents(?string $plugin = null): array
    {
        $items = self::filterTree(self::loadTree(false), $plugin);
        $items = ArrayTree::arr2table(array_merge($items, [[
            'id' => '0', 'pid' => '-1', 'url' => '#', 'title' => '顶部菜单',
        ]]));

        foreach ($items as $key => $menu) {
            if ($menu['spt'] >= 3 || $menu['url'] !== '#') {
                unset($items[$key]);
            }
        }

        return array_values($items);
    }

    /**
     * 过滤指定插件菜单树.
     */
    public static function filterTree(array $menus, ?string $plugin = null): array
    {
        if (($plugin = self::pluginCode($plugin)) === '') {
            return $menus;
        }

        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = self::filterTree($menu['sub'], $plugin);
            }
            if (self::detectPlugin($menu) !== $plugin && empty($menu['sub'])) {
                unset($menus[$key]);
            }
        }

        return array_values($menus);
    }

    /**
     * 检测菜单所属插件编码.
     */
    public static function detectPlugin(array $menu): string
    {
        if (!empty($menu['node']) && ($code = self::pluginByNode(strval($menu['node'])))) {
            return $code;
        }
        if (!empty($menu['url']) && ($code = self::pluginByUrl(strval($menu['url'])))) {
            return $code;
        }
        foreach ((array)($menu['sub'] ?? []) as $sub) {
            if ($code = self::detectPlugin($sub)) {
                return $code;
            }
        }
        return '';
    }

    /**
     * 获取系统菜单树数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getTree(): array
    {
        $menus = SystemMenu::mk()->where(['status' => 1])->order('sort desc,id asc')->select()->toArray();
        if (function_exists('admin_menu_filter')) {
            $menus = call_user_func('admin_menu_filter', $menus);
        }
        foreach ($menus as &$menu) {
            $menu['title'] = self::lang($menu['title']);
        }
        return self::filter(ArrayTree::arr2tree($menus));
    }

    /**
     * 解析插件编码.
     */
    private static function pluginCode(?string $plugin): string
    {
        $current = AppService::resolvePlugin(trim(strval($plugin)), true);
        return strval($current['code'] ?? '');
    }

    /**
     * 判断节点是否允许出现在插件上下文中.
     */
    private static function allowNode(string $node, string $plugin = ''): bool
    {
        return $plugin === '' || self::pluginByNode($node) === $plugin;
    }

    /**
     * 通过节点解析插件编码.
     */
    private static function pluginByNode(string $node): string
    {
        $node = trim($node, '\/');
        if ($node === '') {
            return '';
        }
        $prefix = explode('/', $node, 2)[0];
        $plugin = AppService::resolvePluginPrefix($prefix, true) ?: AppService::resolvePlugin($prefix, true);
        return strval($plugin['code'] ?? '');
    }

    /**
     * 通过菜单链接解析插件编码.
     */
    private static function pluginByUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#' || preg_match('#^(https?:)?//#i', $url)) {
            return '';
        }

        $path = trim(strval(parse_url($url, PHP_URL_PATH) ?: $url), '\/');
        $path = preg_replace('/\.[a-zA-Z0-9]+$/', '', $path);
        return self::pluginByNode($path ?: '');
    }

    /**
     * 载入菜单树.
     */
    private static function loadTree(bool $active = true): array
    {
        $query = SystemMenu::mk()->order('sort desc,id asc');
        if ($active) {
            $query->where(['status' => 1]);
        }
        return ArrayTree::arr2tree($query->select()->toArray());
    }

    /**
     * 菜单分组语言包.
     */
    private static function lang(string $name): string
    {
        $lang = lang("menus_{$name}");
        if (stripos($lang, 'menus_') === 0) {
            return lang(substr($lang, 6));
        }
        return $lang;
    }

    /**
     * 后台主菜单权限过滤.
     * @param array $menus 当前菜单列表
     */
    private static function filter(array $menus): array
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = self::filter($menu['sub']);
            }
            if (!empty($menu['sub'])) {
                $menu['url'] = '#';
            } elseif (empty($menu['url']) || $menu['url'] === '#' || !(empty($menu['node']) || AuthService::check($menu['node']))) {
                unset($menus[$key]);
            } elseif (preg_match('#^(https?:)?//\w+#i', $menu['url'])) {
                if ($menu['params']) {
                    $menu['url'] .= (!str_contains($menu['url'], '?') ? '?' : '&') . $menu['params'];
                }
            } else {
                $node = join('/', array_slice(str2arr($menu['url'], '/'), 0, 3));
                $menu['url'] = system_uri($menu['url']) . ($menu['params'] ? '?' . $menu['params'] : '');
                if (!AuthService::check($node)) {
                    unset($menus[$key]);
                }
            }
        }
        return $menus;
    }

    /**
     * 标准化菜单列表类型.
     */
    private static function normalizeIndexType(string $type): string
    {
        return strtolower(trim($type)) === 'recycle' ? 'recycle' : 'index';
    }

    /**
     * 标准化父级菜单并校验最大层级。
     * @throws Exception
     */
    private static function normalizeParentId(int $pid, int $currentId = 0): int
    {
        if ($pid < 0) {
            throw new Exception(lang('上级菜单参数异常！'));
        }
        if ($pid < 1) {
            return 0;
        }

        $menus = [];
        foreach (SystemMenu::mk()->select()->toArray() as $menu) {
            $menus[intval($menu['id'] ?? 0)] = $menu;
        }
        if (!isset($menus[$pid])) {
            throw new Exception(lang('上级菜单不存在！'));
        }
        if ($currentId > 0 && $pid === $currentId) {
            throw new Exception(lang('上级菜单不能指向自身！'));
        }
        if (strval($menus[$pid]['url'] ?? '#') !== '#') {
            throw new Exception(lang('当前父级菜单不能继续挂载子节点！'));
        }

        $depth = 1;
        $cursor = $pid;
        $visited = [];
        while ($cursor > 0) {
            if (isset($visited[$cursor])) {
                throw new Exception(lang('菜单层级数据异常！'));
            }
            $visited[$cursor] = true;
            if ($currentId > 0 && $cursor === $currentId) {
                throw new Exception(lang('不能将菜单移动到自己的子节点下！'));
            }
            $parentId = intval($menus[$cursor]['pid'] ?? 0);
            if ($parentId > 0) {
                $depth++;
                if ($depth >= 3) {
                    throw new Exception(lang('当前菜单最多支持三级结构！'));
                }
                if (!isset($menus[$parentId])) {
                    throw new Exception(lang('菜单层级数据异常！'));
                }
            }
            $cursor = $parentId;
        }

        return $pid;
    }

    /**
     * 标准化菜单跳转目标与参数。
     * @return array{0:string,1:string}
     * @throws Exception
     */
    private static function normalizeMenuTarget(string $url, string $params = ''): array
    {
        $url = trim(str_replace('\\', '/', $url));
        $params = self::normalizeQueryString($params);
        if ($url === '' || $url === '#') {
            return ['#', ''];
        }

        if (preg_match('#^(?:https?://|//)#i', $url)) {
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['scheme']) && !str_starts_with($url, '//')) {
                throw new Exception(lang('菜单链接格式异常！'));
            }
            $normalized = self::buildExternalUrl($parts);
            $params = self::mergeQueryString(strval($parts['query'] ?? ''), $params);
            return [$normalized, $params];
        }
        if (preg_match('#^(?:@|\[)#', $url)) {
            return [$url, $params];
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new Exception(lang('菜单链接格式异常！'));
        }

        $path = trim(strval($parts['path'] ?? ''), '/');
        if ($path === '') {
            throw new Exception(lang('菜单链接不能为空！'));
        }

        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        if (count($segments) < 1) {
            throw new Exception(lang('菜单链接格式异常！'));
        }
        $last = count($segments) - 1;
        $segments[$last] = preg_replace('/\.[a-zA-Z0-9]+$/', '', strval($segments[$last])) ?: strval($segments[$last]);
        if (count($segments) === 1) {
            $segments[] = 'index';
            $segments[] = 'index';
        } elseif (count($segments) === 2) {
            $segments[] = 'index';
        }
        $segments[0] = strtolower($segments[0]);
        if (isset($segments[1])) {
            $segments[1] = Str::snake($segments[1]);
        }
        if (isset($segments[2])) {
            $segments[2] = strtolower($segments[2]);
        }

        $params = self::mergeQueryString(strval($parts['query'] ?? ''), $params);
        $node = join('/', array_slice($segments, 0, 3));
        if (self::resolveNodeMeta($node) === null) {
            throw new Exception(lang('菜单链接对应的系统节点不存在！'));
        }

        return [join('/', $segments), $params];
    }

    /**
     * 标准化权限节点。
     */
    private static function normalizePermissionNode(string $node): string
    {
        $node = trim(str_replace('\\', '/', $node));
        if ($node === '') {
            return '';
        }

        $parts = parse_url($node);
        $path = trim(strval(is_array($parts) ? ($parts['path'] ?? $node) : $node), '/');
        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        if (count($segments) > 0) {
            $last = count($segments) - 1;
            $segments[$last] = preg_replace('/\.[a-zA-Z0-9]+$/', '', strval($segments[$last])) ?: strval($segments[$last]);
        }
        if (isset($segments[0])) {
            $segments[0] = strtolower($segments[0]);
        }
        if (isset($segments[1])) {
            $segments[1] = Str::snake($segments[1]);
        }
        if (isset($segments[2])) {
            $segments[2] = strtolower($segments[2]);
        }

        return join('/', $segments);
    }

    /**
     * 校验权限节点是否启用了注释授权。
     * @throws Exception
     */
    private static function assertPermissionNodeAvailable(string $node): void
    {
        $meta = self::resolveNodeMeta($node);
        if ($meta === null) {
            throw new Exception(lang('权限节点不存在！'));
        }
        if (empty($meta['isauth'])) {
            throw new Exception(lang('权限节点未启用注释授权！'));
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function resolveNodeMeta(string $node): ?array
    {
        $methods = NodeService::getMethods(false);
        if (count($methods) < 1) {
            self::bootNodeCatalog();
            $methods = NodeService::getMethods(false);
        }
        if (!isset($methods[$node])) {
            $methods = NodeService::getMethods(true);
        }
        if (isset($methods[$node]) && is_array($methods[$node])) {
            return $methods[$node];
        }
        return self::discoverNodeMetaFromSource($node);
    }

    private static function bootNodeCatalog(): void
    {
        try {
            Library::$sapp->config->set(['with_route' => true], 'app');
            Library::$sapp->config->set(['default_app' => 'index'], 'route');
            if (method_exists(Library::$sapp->request, 'setRoot') && trim(strval(Library::$sapp->request->pathinfo())) === '') {
                Library::$sapp->request->setRoot('');
                Library::$sapp->request->setPathinfo('system/index/index');
            }
            Library::$sapp->initialize();
        } catch (\Throwable) {
        }
    }

    /**
     * 在未完整初始化应用时，按源码回退解析注释节点。
     * @return array<string, mixed>|null
     */
    private static function discoverNodeMetaFromSource(string $node): ?array
    {
        static $catalog = null;
        if ($catalog === null) {
            $catalog = [];
            self::loadSourceNodeCatalog($catalog);
        }
        return isset($catalog[$node]) && is_array($catalog[$node]) ? $catalog[$node] : null;
    }

    /**
     * @param array<string, array<string, mixed>> $catalog
     */
    private static function loadSourceNodeCatalog(array &$catalog): void
    {
        foreach (self::fallbackControllerRoots() as $root) {
            foreach (FileTools::scan($root['path'], null, 'php') as $file) {
                $relative = trim(str_replace('\\', '/', $file), '/');
                if ($relative === '' || !str_ends_with($relative, '.php')) {
                    continue;
                }
                $className = substr($relative, 0, -4);
                $class = $root['namespace'] . '\\' . str_replace('/', '\\', $className);
                if (!class_exists($class)) {
                    continue;
                }
                self::parseSourceController($catalog, $root['module'], $className, $class);
            }
        }
    }

    /**
     * @return array<int, array{module:string,namespace:string,path:string}>
     */
    private static function fallbackControllerRoots(): array
    {
        $roots = [];
        $appController = syspath('app/controller');
        if (is_dir($appController)) {
            $roots[] = [
                'module' => 'index',
                'namespace' => 'app\\controller',
                'path' => rtrim($appController, '\\/'),
            ];
        }

        foreach (glob(syspath('plugin/*'), GLOB_ONLYDIR) ?: [] as $pluginPath) {
            $module = self::guessPluginModule(basename($pluginPath));
            $controllerPath = rtrim($pluginPath, '\\/') . '/src/controller';
            if ($module === '' || !is_dir($controllerPath)) {
                continue;
            }
            $roots[] = [
                'module' => $module,
                'namespace' => 'plugin\\' . str_replace('-', '\\', $module) . '\\controller',
                'path' => $controllerPath,
            ];
        }

        return $roots;
    }

    private static function guessPluginModule(string $name): string
    {
        $name = trim($name);
        foreach (['think-plugs-', 'think-'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }
        return $name;
    }

    /**
     * @param array<string, array<string, mixed>> $catalog
     */
    private static function parseSourceController(array &$catalog, string $module, string $className, string $class): void
    {
        $ignoreMethods = get_class_methods('\think\admin\Controller');
        $reflection = new \ReflectionClass($class);
        $prefix = strtolower($module . '/' . NodeService::nameTolower($className));
        $catalog[$prefix] = self::parseSourceComment($reflection->getDocComment() ?: '', basename(str_replace('\\', '/', $className)));

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->getName(), $ignoreMethods, true)) {
                continue;
            }
            $catalog[strtolower($prefix . '/' . $method->getName())] = self::parseSourceComment(
                $method->getDocComment() ?: '',
                $method->getName()
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseSourceComment(string $comment, string $default): array
    {
        $text = strtr($comment, "\n", ' ');
        $title = preg_replace('/^\/\*\s*\*\s*\*\s*(.*?)\s*\*.*?$/', '$1', $text);
        if (in_array(substr(strval($title), 0, 5), ['@auth', '@menu', '@logi'], true)) {
            $title = $default;
        }
        return [
            'title' => $title ?: $default,
            'isauth' => intval(preg_match('/@auth\s*true/i', $text)),
            'ismenu' => intval(preg_match('/@menu\s*true/i', $text)),
            'islogin' => intval(preg_match('/@login\s*true/i', $text)),
        ];
    }

    private static function normalizeQueryString(string $query): string
    {
        return trim(trim($query), " \t\n\r\0\x0B?&");
    }

    private static function mergeQueryString(string $query, string $params): string
    {
        $data = [];
        parse_str(self::normalizeQueryString($query), $data);
        parse_str(self::normalizeQueryString($params), $extra);
        return http_build_query(array_merge($data, $extra));
    }

    /**
     * @param array<string, mixed> $parts
     */
    private static function buildExternalUrl(array $parts): string
    {
        $url = '';
        if (!empty($parts['scheme'])) {
            $url .= strval($parts['scheme']) . '://';
        } elseif (!empty($parts['host'])) {
            $url .= '//';
        }
        $url .= strval($parts['host'] ?? '');
        if (!empty($parts['port'])) {
            $url .= ':' . strval($parts['port']);
        }
        $url .= strval($parts['path'] ?? '');
        if (!empty($parts['fragment'])) {
            $url .= '#' . strval($parts['fragment']);
        }
        return $url;
    }

    /**
     * 解析表单父级菜单选项.
     * @return array<int, array<string, mixed>>
     */
    private static function resolveParentMenus(int $id = 0): array
    {
        $menus = self::getParents();
        $current = [];
        if ($id > 0) {
            foreach ($menus as $menu) {
                if (intval($menu['id'] ?? 0) === $id) {
                    $current = $menu;
                    break;
                }
            }
        }
        if (isset($current['spt'], $current['spc']) && in_array(intval($current['spt']), [1, 2], true) && intval($current['spc']) > 0) {
            foreach ($menus as $key => $menu) {
                if (intval($current['spt']) <= intval($menu['spt'] ?? 0)) {
                    unset($menus[$key]);
                }
            }
        }
        return array_values($menus);
    }
}
