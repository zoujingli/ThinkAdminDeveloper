import os
from github import Github

def delete_all_releases(repo):
    for release in repo.get_releases():
        try:
            release.delete_release()
            print(f"Deleted release: {release.title}")
        except Exception as e:
            print(f"Failed to delete release {release.title}: {e}")

def main():
    token = os.getenv("GITHUB_TOKEN")
    repo_name = os.getenv("GITHUB_REPOSITORY").split('/')[-1]  # 获取仓库名
    owner = os.getenv("GITHUB_REPOSITORY").split('/')[0]  # 获取仓库所有者
    g = Github(token)
    repo = g.get_repo(f"{owner}/{repo_name}")
    delete_all_releases(repo)

if __name__ == "__main__":
    main()