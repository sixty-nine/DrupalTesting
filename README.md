Drupal 7 PHPUnit testing
========================

This is code is completely experimental !

Purpose
-------

The goal is to integrate PHPUnit and SQLite with Drupal in order to be able to run tests
outside of Drupal.

For now you can get a get a Symfony/DomCrawler from a page and log-in and out from Drupal.

Usage
-----

```
# Install composer + init / update, then...
phpunit -c src/Liip/Drupal/Testing/Tests/
```

TODO
----

- integrate SQLite
- switch to a new database
- explore integration of Lapistano's libraries
- NTH: auto-install + modules enable/disable into a clean DB
