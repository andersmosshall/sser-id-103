## How to update SSR

1. Check if there is a d10 supported version of vimeo_embed_field and views_custom_permissions
2. Run lando composer update --with-all-dependencies
3. Do git diff and check what has changed.
4. Do git add . (no commit yet)
5. Run lando drush updb -y
6. Run lando drush cex -y
7. Do git diff and check what has changed, co whats not relevant. Espacially [user:mail] seems to whats to be changed to [user:name]. It should be [user:mail]! And people seems to be translated to people in swedish as well in views.view.user_admin_people.yml
8. Most likely you want to do git co config/sync/language/en/user.mail.yml config/sync/views.view.user_admin_people.yml
9. Run lando drush deploy + lando drush cr and do a sanity check.
   * Maskera som admin och konrollera följande:
   * Skapa kurs
   * Registrera frånvaro
   * Ta bort kurs
   * Registrera dagsfrånvaro
   * Generera betygskatalog
   * Spara SO med ckeditor
   * Maskera som lärare och se mina kurser
   * Maskera som elev och kolla behörighet
   * Skicka meddelande
   * Testa signering
   * Testa markera alla i elevlistan (ska vara samtliga sidor)
   * Kolla att personummer kontra födelsedatum states fungerar på elever och vårdnadshavare
   * Kolla logg efter errors
10. Commit and push update.
11. On dev machine run ssr_update_all
12. On server run ssr_deploy_all
