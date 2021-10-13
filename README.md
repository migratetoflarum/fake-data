# Fake Data by MigrateToFlarum

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/migratetoflarum/fake-data/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/migratetoflarum/fake-data.svg)](https://packagist.org/packages/migratetoflarum/fake-data) [![Total Downloads](https://img.shields.io/packagist/dt/migratetoflarum/fake-data.svg)](https://packagist.org/packages/migratetoflarum/fake-data) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension allows you to generate fake data to test your forum.

You choose the number of new users, discussions and posts.

If you do not create new users, random existing users will be picked as authors for new discussions and posts.
Alternatively, a list of user-provided user IDs can be used (REST API and command line only).

If you do not create new discussions, random existing discussions will be picked for new posts.
Alternatively, a list of user-provided discussion IDs can be used (REST API and command line only).

A new button is added on discussions if you want to generate replies to that discussion only.

The date seed is made of two options: start date will be parsed by `Carbon::parse()`, so it accepts dates in many formats (example `2021-01-01 15:00:00`) as well as human time (example `2 days ago`).
Date interval is in seconds.
Every time the seed needs a date, the interval will be used to make it unique (user join date, discussion creation time, reply time, ...)
If you generate many records at once with the default date settings, part of the content will end up a few minutes or hours in the future.

The extension can be used via 3 different methods:

- Via the Flarum frontend, in the admin panel (global seed) or in the discussion action dropdown (discussion seed).
- Via the command line. Run `php flarum help migratetoflarum:fake-data` to view the list of options.
- Via the REST API. Perform a `POST` request to `/api/fake-data`. The body can be JSON or any format recognized by Flarum. The parameters have the same name as the command line, for example `user_count`.

**Even though the feature is restricted to admin accounts, It's probably best to not install this on a production forum!**

There is no way to mass delete the generated data.

## Installation

    composer require migratetoflarum/fake-data:"*"

## A MigrateToFlarum extension

This is a free extension by MigrateToFlarum, an online forum migration tool (launching soon).
Follow us on Twitter for updates https://twitter.com/MigrateToFlarum

Need a custom Flarum extension ? [Contact Clark Winkelmann !](https://clarkwinkelmann.com/flarum)

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/21160)
- [Source code on GitHub](https://github.com/migratetoflarum/fake-data)
- [Report an issue](https://github.com/migratetoflarum/fake-data/issues)
- [Download via Packagist](https://packagist.org/packages/migratetoflarum/fake-data)
