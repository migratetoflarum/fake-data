<?php

namespace MigrateToFlarum\FakeData;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class SeedConfiguration
{
    public $bulkMode;
    protected $bulkModeCache = [];
    protected $dateInterval;
    /**
     * @var $date Carbon
     */
    protected $date;
    public $userCount;
    public $providedUserIds;
    public $discussionCount;
    public $providedTagIds;
    public $providedDiscussionIds;
    public $postCount;

    public function __construct(array $attributes)
    {
        $this->bulkMode = (bool)Arr::get($attributes, 'bulk');
        $dateInput = Arr::get($attributes, 'date_start');
        $this->date = $dateInput ? Carbon::parse($dateInput) : Carbon::now();
        $intervalInput = Arr::get($attributes, 'date_interval');
        $this->dateInterval = is_numeric($intervalInput) ? max((int)$intervalInput, 0) : 1;

        $this->userCount = Arr::get($attributes, 'user_count', 0);
        $userIds = Arr::get($attributes, 'user_ids');
        $this->providedUserIds = is_string($userIds) ? explode(',', $userIds) : $userIds;
        $this->discussionCount = Arr::get($attributes, 'discussion_count', 0);
        $tagIds = Arr::get($attributes, 'tag_ids');
        $this->providedTagIds = is_string($tagIds) && $tagIds !== 'random' ? explode(',', $tagIds) : $tagIds;
        $discussionIds = Arr::get($attributes, 'discussion_ids');
        $this->providedDiscussionIds = is_string($discussionIds) ? explode(',', $discussionIds) : $discussionIds;
        $this->postCount = Arr::get($attributes, 'post_count', 0);
    }

    public function reuseInBulkMode(string $key, callable $callback)
    {
        if (!$this->bulkMode) {
            return $callback();
        }

        if (!Arr::exists($this->bulkModeCache, $key)) {
            $this->bulkModeCache[$key] = $callback();
        }

        return $this->bulkModeCache[$key];
    }

    public function nextDate()
    {
        $date = $this->date->copy();

        $this->date->addSeconds($this->dateInterval);

        return $date;
    }
}
