Drupal 7 PHPUnit testing
========================

This is code is completely experimental !

The goal is to integrate PHPUnit with Drupal in order to be able to run tests
outside of Drupal.

Later on I'd like to add some SQLite support to speed up tests.

Lot of code comes from the Simple Test standard Drupal 7 module.

Update
------

I just discovered:

https://github.com/fabpot/goutte

It makes almost all this code obsolete!

Check src/Liip/Drupal/Testing/Tests/GoutteTest.php

More to come soon...

Usage
-----

```
# Install composer + init / update, then...
phpunit -c src/Liip/Drupal/Testing/Tests/
```
