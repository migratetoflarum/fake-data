<?php

namespace MigrateToFlarum\FakeData;

use Flarum\Extend;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/resources/less/admin.less'),
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/resources/less/forum.less'),
    (new Extend\Routes('api'))
        ->post('/fake-data', 'migratetoflarum-fake-data', Controllers\FakeDataController::class),
    new Extend\Locales(__DIR__ . '/resources/locale'),
];
