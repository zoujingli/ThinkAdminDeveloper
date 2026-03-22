<?php
// 检查 git 状态
echo "=== Git Status ===" . PHP_EOL;
exec('git status --short 2>&1', $output, $returnCode);
if (empty($output)) {
    echo "工作区干净，没有未提交的修改" . PHP_EOL;
} else {
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
}

echo PHP_EOL . "=== Git Log (最近 5 条) ===" . PHP_EOL;
exec('git log --oneline -5 2>&1', $log);
foreach ($log as $line) {
    echo $line . PHP_EOL;
}

echo PHP_EOL . "=== Git Diff (未暂存的文件) ===" . PHP_EOL;
exec('git diff --name-only 2>&1', $diff);
if (empty($diff)) {
    echo "没有未暂存的修改" . PHP_EOL;
} else {
    foreach ($diff as $line) {
        echo $line . PHP_EOL;
    }
}

echo PHP_EOL . "=== Git Diff (已暂存的文件) ===" . PHP_EOL;
exec('git diff --cached --name-only 2>&1', $cached);
if (empty($cached)) {
    echo "没有已暂存的修改" . PHP_EOL;
} else {
    foreach ($cached as $line) {
        echo $line . PHP_EOL;
    }
}
