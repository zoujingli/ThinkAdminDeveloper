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
require __DIR__ . '/acceptance.php';

/**
 * 执行本地 Git，非零退出立即携带证据失败。
 * @param string $site 仓库或工作目录
 * @param string[] $arguments Git 参数
 * @return string 标准输出
 */
function releaseGit(string $site, array $arguments): string
{
    [$status, $output] = acceptanceRun($site, array_merge(['git'], $arguments));
    acceptanceCheck($status === 0, '本地 Git 演练失败：' . $output);
    return trim($output);
}

/**
 * 执行实际工作流的发布步骤，验证直接提交、首次发布、旧标签补发和重跑行为。
 * Git 使用隔离仓库；仅将 gh 替换为参数及正文记录器，不连接 GitHub 或创建远端 Release。
 * @param string $root 被验收源码目录
 * @param string $directory 本次临时根目录
 * @throws RuntimeException 正文遗漏提交、范围错误或幂等行为变化时失败
 */
function releaseNotesCheck(string $root, string $directory): void
{
    $workflow = file_get_contents($root . '/.github/workflows/release.yml');
    $marker = "        run: |\n";
    acceptanceCheck(substr_count($workflow, $marker) === 1, '发布步骤结构变化，需重新确认回归入口');
    $script = preg_replace('/^          /m', '', substr($workflow, strpos($workflow, $marker) + strlen($marker)));
    $site = $directory . '/notes project';
    mkdir($site);
    acceptanceWrite($directory . '/release-step.sh', $script);
    acceptanceWrite(
        $directory . '/bin/gh',
        <<<'PHP'
#!/usr/bin/env php
<?php
if (($argv[1] ?? '') !== 'release') {
    exit(64);
}
if (($argv[2] ?? '') === 'view') {
    exit(getenv('RELEASE_FIXTURE_EXISTS') === '1' ? 0 : 1);
}
if (($argv[2] ?? '') !== 'create') {
    exit(64);
}
$index = array_search('--notes-file', $argv, true);
$body = $index === false ? '' : file_get_contents($argv[$index + 1]);
file_put_contents(getenv('RELEASE_FIXTURE_RECORD'), json_encode(['arguments' => $argv, 'body' => $body]));
PHP
    );
    chmod($directory . '/bin/gh', 0755);
    $environment = [];
    foreach (['PATH', 'GITHUB_REPOSITORY', 'GITHUB_SERVER_URL', 'RELEASE_TAG', 'RELEASE_FIXTURE_RECORD', 'RELEASE_FIXTURE_EXISTS'] as $name) {
        $environment[$name] = getenv($name);
    }
    try {
        putenv('PATH=' . $directory . '/bin:' . getenv('PATH'));
        putenv('GITHUB_REPOSITORY=acceptance/fixture');
        putenv('GITHUB_SERVER_URL=https://github.com');
        $record = $directory . '/release-record.json';
        putenv('RELEASE_FIXTURE_RECORD=' . $record);
        putenv('RELEASE_FIXTURE_EXISTS=0');
        releaseGit($site, ['init']);
        $commits = [
            ['v1.0.0', 'fix(发布): 首次提交'],
            ['v1.1.0-rc.1', 'fix(中间件): 直接提交 $(touch injected)'],
            ['v1.1.0', 'fix(文件): 前缀修复'],
            ['v2.0.0', 'fix(后续): 不应混入旧版本'],
        ];
        foreach ($commits as [$tag, $subject]) {
            // 从文件读取中文提交，避免旧 PHP 的 C locale 在 shell 参数转义时丢失多字节字符。
            acceptanceWrite($directory . '/commit-message.txt', $subject . "\n");
            releaseGit($site, ['commit', '--allow-empty', '-F', $directory . '/commit-message.txt']);
            releaseGit($site, ['tag', $tag]);
        }
        foreach (['v1.1.0', 'v1.0.0', 'v1.1.0-rc.1'] as $tag) {
            putenv('RELEASE_TAG=' . $tag);
            [$status, $output] = acceptanceRun($site, ['bash', $directory . '/release-step.sh']);
            acceptanceCheck($status === 0 && is_file($record), '发布步骤失败：' . $output);
            $release = json_decode(file_get_contents($record), true);
            $body = $release['body'];
            $arguments = $release['arguments'];
            acceptanceCheck($arguments[3] === $tag && strpos($body, '不应混入旧版本') === false, '正文或目标使用了最新标签而非触发标签');
            if ($tag === 'v1.0.0') {
                acceptanceCheck(strpos($body, '首次提交') !== false && strpos($body, '前缀修复') === false, '首次发布遗漏提交或包含未来提交');
                acceptanceCheck(strpos($body, '/commits/v1.0.0') !== false, '首次发布缺少提交历史链接');
            } else {
                acceptanceCheck(strpos($body, '直接提交 $(touch injected)') !== false, 'Release 正文没有具体的直接提交内容：' . $body);
                acceptanceCheck(strpos($body, '首次提交') === false && strpos($body, '/compare/v1.0.0...' . $tag) !== false, '发布范围没有使用先前版本');
                acceptanceCheck((strpos($body, '前缀修复') !== false) === ($tag === 'v1.1.0'), '稳定版与预发布的提交范围错误');
            }
            acceptanceCheck(in_array('--prerelease', $arguments, true) === (strpos($tag, '-') !== false), '预发布标记变化');
            acceptanceCheck(!file_exists($site . '/injected'), '提交信息被作为 shell 命令执行');
        }
        echo "PASS: release notes / 直接提交、首次发布、旧标签补发、稳定版范围及预发布\n";

        $before = file_get_contents($record);
        putenv('RELEASE_FIXTURE_EXISTS=1');
        [$status] = acceptanceRun($site, ['bash', $directory . '/release-step.sh']);
        acceptanceCheck($status === 0 && file_get_contents($record) === $before, '重跑覆盖了已存在的 Release 正文');
        putenv('RELEASE_FIXTURE_EXISTS=0');
        putenv('RELEASE_TAG=v9.9.9');
        [$status] = acceptanceRun($site, ['bash', $directory . '/release-step.sh']);
        acceptanceCheck($status !== 0 && file_get_contents($record) === $before, '不存在的标签仍生成 Release');
        echo "PASS: release notes / 已有正文保留、缺失标签阻断及提交信息按数据处理\n";

        foreach (array_merge([$root . '/.github/workflows/release.yml'], glob($root . '/plugin/*/.github/workflows/release.yml')) as $file) {
            $content = file_get_contents($file);
            acceptanceCheck(substr_count($content, $marker) === 1 && strpos($content, 'fetch-depth: 0') !== false, '工作流未获取完整标签历史：' . $file);
            $actual = preg_replace('/^          /m', '', substr($content, strpos($content, $marker) + strlen($marker)));
            acceptanceCheck($actual === $script, '子仓发布步骤偏离权威工作流：' . $file);
        }
        echo "PASS: release notes / 主仓与全部子仓的发布步骤和完整历史检出一致\n";
    } finally {
        foreach ($environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
    }
}

/**
 * 创建门禁专用合成证据，覆盖所有必须存在的检查。
 * @param string $directory 临时证据目录
 * @param string $source 演练源码提交
 */
function releaseEvidence(string $directory, string $source): void
{
    foreach ([['7.2', '8.4', 'full'], ['8.2', '8.4', 'full'], ['7.2', '5.7', 'upgrade'], ['8.2', '8.0', 'upgrade']] as [$php, $mysql, $scope]) {
        $path = $directory . '/' . $php . '-' . $mysql;
        acceptanceWrite($path . '/matrix.json', json_encode(['php' => $php, 'mysql' => $mysql, 'scope' => $scope]));
        acceptanceWrite($path . '/source-sha.txt', $source);
        acceptanceWrite($path . '/worktree.txt', '');
        acceptanceWrite($path . '/exit-code.txt', '0');
        foreach (['composer.lock', 'dependencies.json', 'images.txt', 'source-archive.sha256', 'php-version.txt', 'fpm-version.txt', 'nginx-version.txt', 'composer-version.txt'] as $file) {
            acceptanceWrite($path . '/' . $file, 'SYNTHETIC_GATE_FIXTURE');
        }
        $checks = ['syntax', 'metadata', $scope === 'full' ? 'regression' : 'upgrade', 'file-distinct-sqlite', 'file-distinct-mysql', 'console', 'composer-sqlite', 'composer-mysql', 'http-sqlite', 'http-mysql', 'lifecycle'];
        if ($php === '8.2' && $mysql === '8.4') {
            $checks[] = 'dev-dependencies';
            $checks[] = 'style';
        }
        if ($php === '7.2' && $mysql === '8.4') {
            $checks[] = 'release';
        }
        $lines = '';
        foreach ($checks as $check) {
            $lines .= $check . "\t0\n";
            acceptanceWrite($path . '/' . $check . '.log', 'SYNTHETIC_GATE_FIXTURE');
        }
        acceptanceWrite($path . '/results.tsv', $lines);
    }
}

$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/thinkadmin-release-check-' . bin2hex(random_bytes(8));
$site = $directory . '/project with spaces';
$remotes = $directory . '/remotes';
mkdir($site, 0777, true);
mkdir($remotes);
try {
    // 本地独立配置将脚本固定 GitHub URL 全部改写为本次 bare 仓库。
    acceptanceWrite($directory . '/gitconfig', "[user]\n name = Acceptance\n email = acceptance@example.invalid\n[protocol \"file\"]\n allow = always\n[url \"file://{$remotes}/\"]\n insteadOf = git@github.com:zoujingli/\n");
    putenv('GIT_CONFIG_GLOBAL=' . $directory . '/gitconfig');
    // Debian 旧版 Git 尚不支持 GIT_CONFIG_GLOBAL，用参数继承保证所有子命令使用同一配置。
    putenv("GIT_CONFIG_PARAMETERS='include.path=" . $directory . "/gitconfig'");
    putenv('GIT_CONFIG_NOSYSTEM=1');
    releaseNotesCheck($root, $directory);
    foreach (['release.sh', 'split-linux.sh', 'splitsh-lite-linux', 'verify-acceptance.php'] as $file) {
        acceptanceWrite($site . '/.github/' . $file, file_get_contents($root . '/.github/' . $file));
        chmod($site . '/.github/' . $file, 0755);
    }
    acceptanceWrite($site . '/.gitignore', "runtime/\n");
    foreach (['think-one', 'think-two'] as $plugin) {
        acceptanceWrite($site . '/plugin/' . $plugin . '/composer.json', json_encode(['name' => 'zoujingli/' . $plugin]));
        acceptanceWrite($site . '/plugin/' . $plugin . '/value.php', '<?php return ' . var_export($plugin, true) . ';');
        releaseGit($directory, ['init', '--bare', $remotes . '/' . $plugin . '.git']);
    }
    releaseGit($site, ['init']);
    releaseGit($site, ['symbolic-ref', 'HEAD', 'refs/heads/master']);
    releaseGit($site, ['add', '.']);
    releaseGit($site, ['commit', '-m', 'fixture source']);
    $source = releaseGit($site, ['rev-parse', 'HEAD']);
    releaseGit($directory, ['init', '--bare', $remotes . '/source.git']);
    releaseGit($site, ['remote', 'add', 'origin', $remotes . '/source.git']);
    releaseGit($site, ['push', 'origin', 'master']);
    $evidence = $directory . '/evidence';
    releaseEvidence($evidence, $source);
    putenv('THINKADMIN_ACCEPTANCE_DIR=' . $evidence);
    putenv('GITHUB_SHA=' . $source);
    putenv('GITHUB_REF_NAME=master');
    putenv('SPLIT_MANIFEST_DIR');

    foreach (['exit-code.txt' => '1', 'worktree.txt' => ' M composer.json', 'source-sha.txt' => str_repeat('0', 40), 'results.tsv' => "console\t0\n"] as $file => $invalid) {
        $path = $evidence . '/7.2-8.4/' . $file;
        $original = file_get_contents($path);
        acceptanceWrite($path, $invalid);
        [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
        acceptanceCheck($status !== 0, '无效验收证据未阻止拆分：' . $file);
        acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'for-each-ref', '--format=%(refname)']) === '', '验收失败后仍发生推送');
        acceptanceWrite($path, $original);
    }
    echo "PASS: release / 失败、脏源码、来源不符及缺失检查均阻断\n";

    // 总状态成功不能覆盖单项失败；缺失日志和矩阵同样不能成为发布凭据。
    $results = $evidence . '/7.2-8.4/results.tsv';
    $original = file_get_contents($results);
    foreach (['file-distinct-sqlite', 'file-distinct-mysql'] as $check) {
        acceptanceWrite($results, str_replace($check . "\t0\n", '', $original));
        [$status] = acceptanceRun($site, [PHP_BINARY, '.github/verify-acceptance.php', $evidence, $source]);
        acceptanceCheck($status !== 0, '缺失文件去重检查未阻断：' . $check);
    }
    foreach ([str_replace("console\t0", "console\t1", $original), $original . "console\t0\n"] as $invalid) {
        acceptanceWrite($results, $invalid);
        [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
        acceptanceCheck($status !== 0, '总状态掩盖单项失败或重复检查');
    }
    acceptanceWrite($results, $original);
    foreach ([$evidence . '/7.2-8.4/console.log', $evidence . '/8.2-8.0/matrix.json', $evidence . '/8.2-8.4/http-sqlite.log', $evidence . '/8.2-8.4/fpm-version.txt', $evidence . '/7.2-8.4/file-distinct-sqlite.log', $evidence . '/8.2-8.0/file-distinct-mysql.log'] as $path) {
        rename($path, $path . '.held');
        [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
        acceptanceCheck($status !== 0, '证据文件缺失未阻断：' . basename($path));
        rename($path . '.held', $path);
    }
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'for-each-ref', '--format=%(refname)']) === '', '证据不完整时仍发生推送');
    echo "PASS: release / 单项失败、重复检查、缺失日志及缺失矩阵均阻断\n";

    // 模拟第二个仓库拒绝更新，第一仓库的局部成功不能产生完整发布凭据。
    $rejectHook = $remotes . '/think-two.git/hooks/pre-receive';
    acceptanceWrite($rejectHook, "#!/bin/sh\nexit 1\n");
    chmod($rejectHook, 0755);
    [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
    acceptanceCheck($status !== 0, '部分拆分失败仍返回成功');
    $manifest = $site . '/runtime/release/' . $source . '/split.tsv';
    acceptanceCheck(!is_file($manifest) && !is_file(dirname($manifest) . '/acceptance.json'), '部分拆分失败仍生成发布凭据');
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'rev-parse', 'master']) !== '', '未进入部分拆分成功场景');
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-two.git', 'for-each-ref', '--format=%(refname)']) === '', '失败的远端仍被更新');
    unlink($rejectHook);

    [$status, $output] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
    if ($status !== 0) {
        fwrite(STDERR, $output);
    }
    acceptanceCheck($status === 0, '真实 splitsh 拆分失败，退出码 ' . $status);
    acceptanceCheck(is_file($manifest), '未生成拆分凭据');
    foreach (['think-one', 'think-two'] as $plugin) {
        $tree = releaseGit($directory, ['--git-dir=' . $remotes . '/' . $plugin . '.git', 'rev-parse', 'master^{tree}']);
        acceptanceCheck($tree === releaseGit($site, ['rev-parse', 'HEAD:plugin/' . $plugin]), '拆分树与被验收源码不一致');
    }
    echo "PASS: release / 部分失败阻止发布并允许修复后重跑\n";
    echo "PASS: release / 真实 splitsh 与源码目录树一致\n";
    $arguments = ['bash', '.github/release.sh', '--manifest', $manifest];
    [$status, $output] = acceptanceRun($site, array_merge($arguments, ['--dry-run', 'v1.0.0']));
    acceptanceCheck($status === 0, '发版 dry-run 失败：' . $output);
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'tag', '--list']) === '', 'dry-run 创建了标签');
    for ($attempt = 0; $attempt < 2; ++$attempt) {
        [$status, $output] = acceptanceRun($site, array_merge($arguments, ['v1.0.0']));
        acceptanceCheck($status === 0, '本地发版或重跑失败：' . $output);
    }
    foreach (['think-one', 'think-two'] as $plugin) {
        $bare = '--git-dir=' . $remotes . '/' . $plugin . '.git';
        acceptanceCheck(releaseGit($directory, [$bare, 'rev-parse', 'v1.0.0^{commit}']) === releaseGit($directory, [$bare, 'rev-parse', 'master']), '标签未绑定验收拆分');
    }
    echo "PASS: release / dry-run、标签绑定和重复执行\n";

    $originalSplit = releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'rev-parse', 'master']);
    $drift = releaseGit($site, ['commit-tree', $originalSplit . '^{tree}', '-p', $originalSplit, '-m', 'untested remote head']);
    releaseGit($site, ['push', 'git@github.com:zoujingli/think-one.git', $drift . ':refs/heads/master']);
    [$status] = acceptanceRun($site, array_merge($arguments, ['--dry-run', 'vdrift']));
    acceptanceCheck($status !== 0, '远端分支不再对应验收拆分时未阻断');
    releaseGit($site, ['push', '--force', 'git@github.com:zoujingli/think-one.git', $originalSplit . ':refs/heads/master']);
    echo "PASS: release / 远端提交偏离验收拆分时阻断\n";

    releaseGit($site, ['push', 'git@github.com:zoujingli/think-two.git', 'HEAD:refs/tags/vconflict']);
    [$status] = acceptanceRun($site, array_merge($arguments, ['vconflict']));
    acceptanceCheck($status !== 0, '标签冲突未阻断');
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'tag', '--list', 'vconflict']) === '', '全量预检前已发布部分标签');
    $receipt = dirname($manifest) . '/acceptance.json';
    file_put_contents($receipt, ' ', FILE_APPEND);
    [$status] = acceptanceRun($site, array_merge($arguments, ['--dry-run', 'vrejected']));
    acceptanceCheck($status !== 0, '凭据哈希变化未阻断');
    echo "PASS: release / 标签冲突全量预检及凭据一致性\n";

    $newer = releaseGit($site, ['commit-tree', 'HEAD^{tree}', '-p', 'HEAD', '-m', 'newer source']);
    releaseGit($site, ['push', 'origin', $newer . ':refs/heads/master']);
    [$status, $output] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
    acceptanceCheck($status === 0 && strpos($output, 'skipping this split') !== false, '过期来源未跳过');
    putenv('GITHUB_SHA=' . str_repeat('0', 40));
    [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh']);
    acceptanceCheck($status !== 0, '检出与 CI SHA 不符仍执行拆分');
    echo "PASS: release / 过期来源跳过及 CI SHA 校验\n";

    // 读取目标旧值之后才制造竞争提交，真实 Git 服务端应拒绝覆盖。
    $oldSplit = releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'rev-parse', 'master']);
    $concurrent = releaseGit($site, ['commit-tree', $oldSplit . '^{tree}', '-p', $oldSplit, '-m', 'concurrent plugin']);
    releaseGit($site, ['push', 'git@github.com:zoujingli/think-one.git', $concurrent . ':refs/heads/concurrent']);
    acceptanceWrite($site . '/plugin/think-one/value.php', '<?php return "changed";');
    releaseGit($site, ['add', 'plugin/think-one/value.php']);
    releaseGit($site, ['commit', '-m', 'new source for lease']);
    $source = releaseGit($site, ['rev-parse', 'HEAD']);
    releaseGit($site, ['push', '--force', 'origin', 'master']);
    releaseEvidence($evidence, $source);
    putenv('GITHUB_SHA=' . $source);
    acceptanceWrite($site . '/.git/hooks/pre-push', "#!/bin/sh\n" . 'git --git-dir=' . escapeshellarg($remotes . '/think-one.git') . ' update-ref refs/heads/master ' . escapeshellarg($concurrent) . "\n");
    chmod($site . '/.git/hooks/pre-push', 0755);
    [$status] = acceptanceRun($site, ['bash', '.github/split-linux.sh', 'think-one']);
    acceptanceCheck($status !== 0, '并发目标变化未阻止覆盖');
    acceptanceCheck(releaseGit($directory, ['--git-dir=' . $remotes . '/think-one.git', 'rev-parse', 'master']) === $concurrent, '覆盖了并发提交');
    echo "PASS: release / 真实 force-with-lease 拒绝并发覆盖\n";
} finally {
    acceptanceRemove($directory);
}
