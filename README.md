## How to setup new site (TODO: update readme)

1. Locally remove -- from scoped lando file
2. Run composer install
3. Create web/sites/default/private-files
4. Generate key with dd if=/dev/urandom bs=32 count=1 | base64 -i - > .encryption_key/encrypt.key
5. Store the key in last pass
6. Setup doc root to web in the repository.
6. Visit site to set up drupal core (setup english use minimal installation)
7. Temporary set web/sites/default and web/sites/default/settings.php to writeable (chmod +w)
8. Add 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_swedish_ci' to settings.php
8. Remove generated $settings['config_sync_directory'] in web/sites/default/settings.php
9. Copy non secret settings (cp .settings/prod/settings.local.php web/sites/default/settings.local.php && cp .settings/prod/settings.common-local.php web/sites/default/settings.common-local.php)
9. Create web/sites/default/settings.common-secrets.php with data stored in bitwarden
10. Uncomment the include $app_root . '/' . $site_path . '/settings.local.php'; stuff in web/sites/default/settings.php
11. Run drush cex -y to get updated system site config.
12. Run git add config/sync/system.site.yml && git add config/sync/system.theme.global.yml && git checkout . && git clean -fd to add the system config files to git.
13. Change the system.site.yml file and change langcode and default langoage to sv. Run git add config/sync/system.site.yml
13. cd .settings/prod and then create symlinks to the system site config; ln -s ../../config/sync/system.site.yml system.site.yml && ln -s ../../config/sync/system.theme.global.yml system.theme.global.yml
14. Git add and commit and push.
12. Take a backup [school]-pre-install-[date] of the database (can be done in inleed)
13. Run drush updb -y
14. Run drush cim -y twice
15. Rund drush cex -y (nothing should be changed for git, but some parts may due to hash updates and such)
16. Copy config split to the config/sync folder and create a symlink to it in the .settings/prod folder
16. Run drush deploy (or ssr_deploy_local)
17. Do git push
17. Tack a backup [school]-init-[date] of the database and force add them to git.
17. Store database data from web/sites/default/settings.php to bitwarden (only need in prod)
19. Verify that help pages has been imported.
20. Do a rebuild permissions just in case.
21. Check grade terms and sort them.
23. Add new branch to ssr_update_all alias on dev machine + new dir on server ssr_deploy_all
24. Setup cron jobs. See what crul address to use as super user on /admin/config/system/cron
