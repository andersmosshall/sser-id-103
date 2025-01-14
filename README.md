## How to setup new site

1. Locally remove -- from scoped lando file
2. Run composer install
3. Create sites/default/private-files
4. Generate key with dd if=/dev/urandom bs=32 count=1 | base64 -i - > .encryption_key/encrypt.key
5. Store the key in last pass
6. Visit site to set up drupal core (setup english use minimal installation)
7. Add 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_swedish_ci' and 'init_commands' to settings.php
8. Remove generated $settings['config_sync_directory'] in settings.php
9. Copy settings.local where they belong. Remove hash from config files.
10. Uncomment the include $app_root . '/' . $site_path . '/settings.local.php'; stuff in settings.php
11. Run drush cex -y to get updated system site config. Do git co . git clean -fd afterwards
12. Take a backup [school]-pre-install-[date] of the database (can be done in inleed)
12. Copy config_split + Copy properties to system.site and others.
13. Run drush updb -y
14. Run drush cim -y twice
15. Rund drush cex -y (nothing should be changed for git, but some parts may due to hash updates and such)
16. Copy config split to the .settings/prod folder
16. Run drush deploy
17. Do git push
17. Tack a backup [school]-init-[date] of the database and force add them to git.
17. Store database data from settings.php to last pass (only need in prod)
18. Store db access in last pass, copy it locally to settings.php do not push that to git!
19. Verify that help pages has been imported.
20. Do a rebuild permissions just in case.
21. Check grade terms and sort them.
22. Import a ssr-import if needed
23. Add new branch to ssr_update_all alias on dev machine + new dir on server ssr_deploy_all
