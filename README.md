# DevPanel Starshot

This is the repository for using DevPanel to contribute to [Drupal Starshot](https://www.drupal.org/about/starshot). You can use [Drupal Forge](https://www.drupalforge.org/form/subscription?template=14) to set up the Starshot prototype with DevPanel.

## Contributing to the Starshot prototype
1. [Fork](https://github.com/phenaproxima/starshot-prototype/fork) the Starshot prototype repository.

2. Open DevPanel VS Code and run the following commands:
   ```bash
   cd /var/www/html/starshot-prototype
   git remote add <github_username> https://<github_username>:<github_personal_token>@github.com/<github_username>/<github_repo>
   ```
   It is best practice to generate a [GitHub personal access token](https://github.com/settings/tokens) rather than to use your GitHub password for `<github_personal_token>`. If you use a classic access token, it needs the `repo` permission. If you use a fine-grained access token, it needs read and write access to repository contents.
3. Create a branch for your issue:
   ```bash
   git checkout -b <issue-branch>
   ```
5. When you have committed your work, push the branch to your fork:
   ```bash
   git push -u <github_username> HEAD
   ```
5. Create a GitHub pull request from your fork back to the official [starshot-prototype](https://github.com/phenaproxima/starshot-prototype) repository.
