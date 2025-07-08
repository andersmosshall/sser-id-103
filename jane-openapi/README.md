# REST client generator
This is a completely separate sub-project as it is not needed in production.
Therefor you need to run `composer install` in this directory to bring it up.

The REST client generator is used to generate PHP objects to be used in
application api in runtime.

Use this command to generate the client:
```bash
lando php vendor/bin/jane-openapi generate --config-file config/ssr-v2-config
```
