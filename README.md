# Fake Data by MigrateToFlarum

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/migratetoflarum/fake-data/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/migratetoflarum/fake-data.svg)](https://packagist.org/packages/migratetoflarum/fake-data) [![Total Downloads](https://img.shields.io/packagist/dt/migratetoflarum/fake-data.svg)](https://packagist.org/packages/migratetoflarum/fake-data) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension allows you to generate fake data for testing your forum.

You choose number of new users, discussions and posts.

If you do not create new users, random existing users will be picked as authors for new discussions and posts.

If you do not create new discussions, random existing discussions will be picked for new posts.

**It's probably best to not install this on your production forum!**

## Installation

Use [Bazaar](https://discuss.flarum.org/d/5151-flagrow-bazaar-the-extension-marketplace) or install manually:

```bash
composer require migratetoflarum/fake-data
```

## Updating

```bash
composer update migratetoflarum/fake-data
php flarum cache:clear
```

## A MigrateToFlarum extension

This is a free extension by MigrateToFlarum, an online forum migration tool (launching soon).
Follow us on Twitter for updates https://twitter.com/MigrateToFlarum

Need a custom Flarum extension ? [Contact Clark Winkelmann !](https://clarkwinkelmann.com/flarum)

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/21160)
- [Source code on GitHub](https://github.com/migratetoflarum/fake-data)
- [Report an issue](https://github.com/migratetoflarum/fake-data/issues)
- [Download via Packagist](https://packagist.org/packages/migratetoflarum/fake-data)
