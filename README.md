# WPMDB PRO Installer

[![Packagist](https://img.shields.io/packagist/v/philippbaschke/acf-pro-installer.svg?maxAge=3600)](https://packagist.org/packages/philippbaschke/acf-pro-installer)
[![Packagist](https://img.shields.io/packagist/l/philippbaschke/acf-pro-installer.svg?maxAge=2592000)](https://github.com/PhilippBaschke/acf-pro-installer/blob/master/LICENSE)
[![Travis](https://img.shields.io/travis/PhilippBaschke/acf-pro-installer.svg?maxAge=3600)](https://travis-ci.org/PhilippBaschke/acf-pro-installer)
[![Coveralls](https://img.shields.io/coveralls/PhilippBaschke/acf-pro-installer.svg?maxAge=3600)](https://coveralls.io/github/PhilippBaschke/acf-pro-installer)

A composer plugin that makes installing [WP Migrate DB Pro] with [composer] easier.

This package borrows very heavily from [acf-pro-installer] from Philipp Baschke.  Special thanks to Philipp for doing 99% of the work.

This package reads your :key: WPMDB PRO key from the **environment** or a **.env file**.

[WPMDB PRO]: https://deliciousbrains.com/wp-migrate-db-pro/
[composer]: https://github.com/composer/composer
[acf-pro-installer]: https://github.com/PhilippBaschke/acf-pro-installer

## Usage

**1. Add the package repository to the [`repositories`][composer-repositories] field in `composer.json`
   (based on this [gist][package-gist]):**

```json
{
  "type": "package",
  "package": {
    "name": "deliciousbrains/wp-migrate-db-pro",
    "version": "*.*.*(.*)",
    "type": "wordpress-plugin",
    "dist": {
      "type": "zip",
      "url": "https://connect.advancedcustomfields.com/index.php?p=pro&a=download"
    },
    "require": {
      "cadecannon/wpmdb-pro-installer": "^1.0",
      "composer/installers": "^1.0"
    }
  }
}
```
Replace `"version": "*.*.*(.*)"` with your desired version.

**2. Make your WPMDB PRO key available**

Set the environment variable **`WPMDB_PRO_KEY`** to your [WPMDB PRO key][wpmdb-account].

Alternatively you can add an entry to your **`.env`** file:

```ini
# .env (same directory as composer.json)
WPMDB_PRO_KEY=Your-Key-Here
```

**3. Require WPMDB PRO**

```sh
composer require cadecannon/wpmdb-pro-installer:*
```
You can specify an [exact version][composer-versions] (that matches your desired version).

If you use **`*`**, composer will install the version from the package repository (see 1). This has the benefit that you only need to change the version in the package repository when you want to update.

*Be aware that `composer update` will only work if you change the `version` in the package repository. Decreasing the version only works if you require an [exact version][composer-versions].*

[composer-repositories]: https://getcomposer.org/doc/04-schema.md#repositories
[composer-versions]: https://getcomposer.org/doc/articles/versions.md
[package-gist]: https://gist.github.com/fThues/705da4c6574a4441b488
[wpmdb-account]: https://www.advancedcustomfields.com/my-account/
