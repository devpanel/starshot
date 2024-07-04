# DevPanel Starshot

This is the repository for using DevPanel to contribute to [Drupal Starshot](https://www.drupal.org/about/starshot). You can use [Drupal Forge](https://www.drupalforge.org/form/subscription?template=14) to set up the Starshot prototype with DevPanel.

## Contributing to the Starshot prototype
1. [Fork](https://github.com/phenaproxima/starshot-prototype/fork) the Starshot prototype repository.

2. Open DevPanel VS Code and run the following commands:
   ```
   bash
   cd /var/www/html/starshot-prototype
   git remote add <github_username> https://<github_username>:<github_personal_token>@github.com/<github_username>/<github_repo>
   ```
3. Create a branch for your issue:
   ```
   git checkout -b <issue-branch>
   ```
5. Work on your issue, then push your work to your repository:
   ```
   bash
   git push -u <github_username> HEAD
   ```
5. Create a pull request back to the official [starshot-prototype](https://github.com/phenaproxima/starshot-prototype) repository.
