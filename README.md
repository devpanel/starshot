[![About Drupal Starshot](https://github.com/user-attachments/assets/9a2bffa5-9e75-4063-8f97-1b6773ae661e)](https://www.drupal.org/about/starshot)
# DevPanel Starshot


This is the repository for using DevPanel to contribute to the Drupal Starshot prototype. You can launch the Starshot prototype with a cloud development environment from Drupal Forge.

<div align="center">
   <a href="https://www.drupalforge.org/form/subscription?template=14">
      <figure>
         <img src="https://github.com/user-attachments/assets/69745ec7-d9a6-498f-9f47-8b60795195bb" height="100px" />
         <br />
         <figcaption>Launch from Drupal Forge</figcaption>
      </figure>
   </a>
</div>


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
