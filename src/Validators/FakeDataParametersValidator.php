<?php

namespace MigrateToFlarum\FakeData\Validators;

use Flarum\Foundation\AbstractValidator;

class FakeDataParametersValidator extends AbstractValidator
{
    protected function getRules()
    {
        return [
            'user_count' => 'required|integer|min:0',
            'user_ids' => 'nullable|array',
            'discussion_count' => 'required|integer|min:0',
            'discussion_ids' => 'nullable|array',
            'post_count' => 'required|integer|min:0',
        ];
    }
}
