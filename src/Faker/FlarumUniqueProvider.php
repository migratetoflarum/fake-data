<?php

namespace MigrateToFlarum\FakeData\Faker;

use Faker\Provider\Base;

class FlarumUniqueProvider extends Base
{
    protected ?DatabaseUniqueGenerator $flarumUnique = null;

    public function flarumUnique(): DatabaseUniqueGenerator
    {
        if (!$this->flarumUnique) {
            $this->flarumUnique = new DatabaseUniqueGenerator($this->generator);
        }

        return $this->flarumUnique;
    }
}
