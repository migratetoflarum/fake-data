<?php

namespace MigrateToFlarum\FakeData;

use Flarum\Extend;
use MigrateToFlarum\FakeData\Controllers\FakeDataController;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),
    (new Extend\Routes('api'))
        ->post('/fake-data', 'migratetoflarum-fake-data', FakeDataController::class),
    new Extend\Locales(__DIR__ . '/resources/locale'),
];
