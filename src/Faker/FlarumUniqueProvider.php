<?php

namespace MigrateToFlarum\FakeData\Faker;

use Faker\Provider\Base;

class FlarumUniqueProvider extends Base
{
    protected $flarumUnique;

    public function flarumUnique()
    {
        if (!$this->flarumUnique) {
            $this->flarumUnique = new DatabaseUniqueGenerator($this->generator);
        }

        return $this->flarumUnique;
    }
}
