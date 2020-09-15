<?php

namespace MigrateToFlarum\FakeData\Faker;

use Faker\Provider\Internet;

/**
 * Same as Faker's Internet provider, but we change the username rules to match Flarum
 */
class FlarumInternetProvider extends Internet
{
    protected static $userNameFormats = array(
        '{{lastName}}-{{firstName}}',
        '{{firstName}}_{{lastName}}',
        '{{firstName}}##',
        '?{{lastName}}',
    );

    public function userName()
    {
        // Remove any leftover dot
        return str_replace('.', '_', parent::userName());
    }
}
