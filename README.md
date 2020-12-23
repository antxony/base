# BASE SYSTEM

Base project with users control, roles, clients, logger, categories...

## Documentation

PHP docs:

```console
foo@bar:~$ php phpDocumentor.phar -d src -d Antxony -t docs/php --title="Base"
```

Typescript docs

```console
foo@bar:~$ typedoc
```

## FOS routes

```console
foo@bar:~$ php bin/console fos:js-routing:dump --format=json --target=assets/js/fos_js_routes.json
```
