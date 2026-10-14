#!/usr/bin/env bash
set -euo pipefail

# 先检查全部目标，再按指定标签发布；--dry-run 只检查远程分支和已有标签。
DRY_RUN=false
if [[ ${1:-} == --dry-run ]]; then
    DRY_RUN=true
    shift
fi
if [[ $# -eq 0 ]]; then
    echo "Usage: $0 [--dry-run] <v-tag> [plugin ...]" >&2
    exit 1
fi

VERSION=$1
shift
if [[ $VERSION != v* ]] || ! git check-ref-format "refs/tags/$VERSION"; then
    echo "Invalid release tag: $VERSION" >&2
    exit 1
fi
BASEPATH=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
CURRENT_BRANCH=$(git -C "$BASEPATH" symbolic-ref --quiet --short HEAD) || {
    echo "Release requires a checked-out branch." >&2
    exit 1
}
REPOS=("$@")
if [[ ${#REPOS[@]} -eq 0 ]]; then
    for manifest in "$BASEPATH"/plugin/*/composer.json; do
        REPOS+=("$(basename "$(dirname "$manifest")")")
    done
fi

for repo in "${REPOS[@]}"; do
    if [[ ! $repo =~ ^think-[a-z0-9-]+$ || ! -f "$BASEPATH/plugin/$repo/composer.json" ]]; then
        echo "Unknown plugin: $repo" >&2
        exit 1
    fi
done

# 临时目录由本次调用独占，退出时只清理本次克隆，避免并行发布互相删除文件。
release_dir=$(mktemp -d "${TMPDIR:-/tmp}/thinkadmin-release.XXXXXX")
trap 'rm -rf -- "$release_dir"' EXIT
for repo in "${REPOS[@]}"; do
    git clone --quiet --no-checkout "git@github.com:zoujingli/$repo.git" "$release_dir/$repo"
    target=$(git -C "$release_dir/$repo" rev-parse "refs/remotes/origin/$CURRENT_BRANCH^{commit}")
    tagged=$(git -C "$release_dir/$repo" rev-parse --verify "refs/tags/$VERSION^{commit}" 2>/dev/null || true)
    if [[ -n $tagged && $tagged != "$target" ]]; then
        echo "$repo: $VERSION already points to another commit; no tags were published." >&2
        exit 1
    fi
    echo "$repo: $VERSION -> $target"
done

if [[ $DRY_RUN == true ]]; then
    echo "Dry run passed; no tags were created or pushed."
    exit 0
fi

for repo in "${REPOS[@]}"; do
    if git -C "$release_dir/$repo" rev-parse --verify "refs/tags/$VERSION" >/dev/null 2>&1; then
        echo "$repo: $VERSION already published."
        continue
    fi
    git -C "$release_dir/$repo" tag "$VERSION" "refs/remotes/origin/$CURRENT_BRANCH"
    git -C "$release_dir/$repo" push origin "refs/tags/$VERSION:refs/tags/$VERSION"
done
