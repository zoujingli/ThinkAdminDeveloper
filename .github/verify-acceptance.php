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
$directory = $argv[1] ?? '';
$source = $argv[2] ?? '';
if (!is_dir($directory) || !preg_match('/^[0-9a-f]{40}$/D', $source)) {
    fwrite(STDERR, "Usage: php .github/verify-acceptance.php <evidence-directory> <source-sha>\n");
    exit(1);
}
$expected = ['7.2/8.4' => 'full', '8.2/8.4' => 'full', '7.2/5.7' => 'upgrade', '8.2/8.0' => 'upgrade'];
$verified = [];
try {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getFilename() !== 'matrix.json') {
            continue;
        }
        $path = $file->getPath();
        $matrix = json_decode(file_get_contents($file->getPathname()), true);
        $key = ($matrix['php'] ?? '') . '/' . ($matrix['mysql'] ?? '');
        if (!isset($expected[$key]) || isset($verified[$key]) || ($matrix['scope'] ?? '') !== $expected[$key]) {
            throw new RuntimeException('矩阵不匹配或重复：' . $key);
        }
        foreach (['source-sha.txt', 'worktree.txt', 'exit-code.txt', 'results.tsv', 'composer.lock', 'dependencies.json', 'images.txt', 'source-archive.sha256', 'php-version.txt', 'fpm-version.txt', 'nginx-version.txt', 'composer-version.txt'] as $required) {
            if (!is_file($path . '/' . $required)) {
                throw new RuntimeException($key . ' 缺少证据：' . $required);
            }
        }
        if (trim(file_get_contents($path . '/source-sha.txt')) !== $source || trim(file_get_contents($path . '/worktree.txt')) !== '') {
            throw new RuntimeException($key . ' 验收源码不一致或包含未提交改动');
        }
        if (trim(file_get_contents($path . '/exit-code.txt')) !== '0') {
            throw new RuntimeException($key . ' 验收失败，禁止发布');
        }
        $checks = [];
        foreach (file($path . '/results.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $columns = explode("\t", $line);
            if (count($columns) !== 2 || $columns[1] !== '0' || isset($checks[$columns[0]])) {
                throw new RuntimeException($key . ' 检查失败或记录无效：' . $line);
            }
            $checks[$columns[0]] = true;
        }
        $requiredChecks = ['syntax', 'metadata', $expected[$key] === 'full' ? 'regression' : 'upgrade', 'file-distinct-sqlite', 'file-distinct-mysql', 'console', 'composer-sqlite', 'composer-mysql', 'http-sqlite', 'http-mysql', 'lifecycle'];
        if ($key === '8.2/8.4') {
            $requiredChecks[] = 'dev-dependencies';
            $requiredChecks[] = 'style';
        }
        if ($key === '7.2/8.4') {
            $requiredChecks[] = 'release';
        }
        foreach ($requiredChecks as $check) {
            if (!isset($checks[$check]) || !is_file($path . '/' . $check . '.log')) {
                throw new RuntimeException($key . ' 未执行检查：' . $check);
            }
        }
        $verified[$key] = ['matrix' => $matrix, 'checks' => array_keys($checks),
            'lock_sha256' => hash_file('sha256', $path . '/composer.lock'),
            'images_sha256' => hash_file('sha256', $path . '/images.txt'),
            'archive' => trim(file_get_contents($path . '/source-archive.sha256'))];
    }
    if (count($verified) !== count($expected)) {
        throw new RuntimeException('四组矩阵证据不完整');
    }
    ksort($verified);
    echo json_encode(['status' => 'passed', 'source' => $source, 'matrix' => $verified], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
