<?php

namespace MigrateToFlarum\FakeData\Faker;

use Faker\UniqueGenerator;
use Flarum\User\User;

/**
 * Not only do we want a unique value for the current run, we also want a value that's not already in the database
 * To achieve that, we pre-populate the $uniques array with values from the database
 */
class DatabaseUniqueGenerator extends UniqueGenerator
{
    public function __call($name, $arguments)
    {
        if (!isset($this->uniques[$name])) {
            $serialize = function ($value, $key) {
                return [serialize($value) => null];
            };

            switch ($name) {
                case 'userName':
                    $this->uniques['userName'] = User::query()->pluck('username')->mapWithKeys($serialize)->all();
                    break;
                case 'safeEmail':
                    $this->uniques['safeEmail'] = User::query()->pluck('email')->mapWithKeys($serialize)->all();
                    break;
            }
        }

        return parent::__call($name, $arguments);
    }
}
