<?php

namespace MigrateToFlarum\FakeData\Commands;

use Exception;
use Flarum\Foundation\ValidationException;
use Illuminate\Console\Command;
use MigrateToFlarum\FakeData\SeedConfiguration;
use MigrateToFlarum\FakeData\Seeder;

class FakeCommand extends Command
{
    protected $signature = 'migratetoflarum:fake-data {--b|bulk : Bulk Mode} {--date_start=now : Start date} {--date_interval=1 : Interval in seconds} {--u|user_count=0 : Number of users to create} {--user_ids= : Comma-separated list of user IDs to use as authors} {--d|discussion_count=0 : Number of discussions to create} {--tag_ids= : Comma-separated list of tag IDs to assign to new discussions or "random"} {--discussion_ids= : Comma-separated list of discussion IDs to use for new posts} {--p|post_count=0 : Number of posts to create}';
    protected $description = 'Generate fake discussion test data';

    protected $seeder;

    public function __construct(Seeder $seeder)
    {
        $this->seeder = $seeder;

        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->seeder->seed(new SeedConfiguration($this->options()), $this->output);
        } catch (Exception $exception) {
            if ($exception instanceof ValidationException) {
                $this->error('Validation Exception');
                foreach ($exception->getAttributes() as $attribute => $message) {
                    $this->error("$attribute: $message");
                }
            } else if ($exception instanceof \Illuminate\Validation\ValidationException) {
                $this->error('Validation Exception');
                foreach ($exception->errors() as $attribute => $messages) {
                    foreach ($messages as $message) {
                        $this->error("$attribute: $message");
                    }
                }
            } else {
                throw $exception;
            }
        }
    }
}
