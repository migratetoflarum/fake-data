<?php

namespace MigrateToFlarum\FakeData;

use Carbon\Carbon;
use Faker\Factory;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\Post\CommentPost;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use MigrateToFlarum\FakeData\Faker\FlarumInternetProvider;
use MigrateToFlarum\FakeData\Faker\FlarumUniqueProvider;
use MigrateToFlarum\FakeData\Validators\FakeDataParametersValidator;

class Seeder
{
    protected $validator;
    protected $translator;
    protected $db;

    public function __construct(FakeDataParametersValidator $validator, Translator $translator, ConnectionInterface $db)
    {
        $this->validator = $validator;
        $this->translator = $translator;
        $this->db = $db;
    }

    public function seed(SeedConfiguration $config, OutputStyle $output)
    {
        $startTime = Carbon::now();

        // Unguard so we can fill in IDs in model constructors
        AbstractModel::unguard();

        if ($config->transaction) {
            $output->info('Starting database transaction');
            $this->db->beginTransaction();

            try {
                $this->wrappedTransaction($config, $output);
            } catch (\Exception $exception) {
                $output->error('An exception was thrown, rolling back database changes');

                $this->db->rollBack();

                throw $exception;
            }

            $this->db->commit();
            $output->info('Database changes committed');
        } else {
            $output->info('Seeding without transaction');
            $this->wrappedTransaction($config, $output);
        }

        AbstractModel::reguard();

        $this->logTimeElapsed($output, $startTime, 'Total time ');
    }

    protected function wrappedTransaction(SeedConfiguration $config, OutputStyle $output)
    {
        $this->validator->assertValid([
            'user_count' => $config->userCount,
            'user_ids' => $config->providedUserIds,
            'discussion_count' => $config->discussionCount,
            'discussion_ids' => $config->providedDiscussionIds,
            'post_count' => $config->postCount,
        ]);

        $faker = Factory::create();
        $faker->addProvider(new FlarumInternetProvider($faker));
        $faker->addProvider(new FlarumUniqueProvider($faker));

        $bulkUserIncrement = 1;
        $userStartTime = Carbon::now();

        $output->info("Seeding {$config->userCount} users");
        $output->progressStart($config->userCount);

        $lastUserIdBeforeImport = $this->largestId('users');

        for ($i = 0; $i < $config->userCount; $i++) {
            $user = new User();
            $user->email = $config->bulkMode ? $config->reuseInBulkMode('email-prefix', function () use ($faker) {
                    return $faker->domainWord;
                }) . $bulkUserIncrement . $config->reuseInBulkMode('email-domain', function () use ($faker) {
                    return '@' . $faker->safeEmailDomain;
                }) : $faker->flarumUnique()->safeEmail;
            $user->username = $config->bulkMode ? $config->reuseInBulkMode('username-prefix', function () use ($faker) {
                    return $faker->flarumUnique()->userName;
                }) . $bulkUserIncrement : $faker->flarumUnique()->userName;
            $user->is_email_confirmed = true;
            $user->joined_at = $config->nextDate();
            $this->batchSave($user);

            $bulkUserIncrement++;

            $output->progressAdvance();
        }

        $this->batchSave();
        $output->progressFinish();
        $this->logTimeElapsed($output, $userStartTime);

        $userIdsForNewContent = [];

        // Put logic in an IF so that we only do the count() check if necessary
        if ($config->discussionCount > 0 || $config->postCount > 0) {
            if (is_array($config->providedUserIds)) {
                $userIdsForNewContent = $config->providedUserIds;
            } else if ($config->userCount > 0) {
                $userIdsForNewContent = User::query()
                    ->where('id', '>', $lastUserIdBeforeImport)
                    ->pluck('id');
            } else {
                $userIdsForNewContent = User::query()
                    ->inRandomOrder()
                    ->limit(10000)
                    ->pluck('id');
            }

            $count = count($userIdsForNewContent);

            if ($count === 0) {
                throw new ValidationException([
                    'users' => $this->translator->trans('migratetoflarum-fake-data.api.no-users-matched'),
                ]);
            }

            $output->info("Will use $count users for discussion and post seed");
        }

        $newDiscussionIds = [];
        $discussionIdsToRefresh = [];
        $userIdsToRefresh = [];
        $tagIdsToRefresh = [];
        $discussionStartTime = Carbon::now();

        $output->info("Seeding {$config->discussionCount} discussions");
        $output->progressStart($config->discussionCount);

        $cachedLastPostNumber = [];

        for ($i = 0; $i < $config->discussionCount; $i++) {
            // Wrap user into model because it's needed for Discussion::start() but all we really need is the ID
            $author = new User([
                'id' => $config->reuseInBulkMode('discussion-author', function () use ($faker, $userIdsForNewContent) {
                    return (int)$faker->randomElement($userIdsForNewContent);
                }),
            ]);
            $title = $config->reuseInBulkMode('discussion-title', function () use ($faker) {
                return $faker->sentence($faker->numberBetween(1, 6));
            });
            $discussion = Discussion::start($title, $author);
            $discussion->created_at = $config->nextDate();
            $discussion->save();

            $tagIds = $config->reuseInBulkMode('discussion-tags', function () use ($config) {
                if ($config->providedTagIds === 'random') {
                    // Take one random primary tag
                    $randomTag = Tag::query()->whereNotNull('position')->inRandomOrder()->first();

                    if (!$randomTag) {
                        return [];
                    }

                    // If it's a primary tag child, add the parent as well
                    if ($randomTag->parent_id) {
                        return [$randomTag->id, $randomTag->parent_id];
                    }

                    return [$randomTag->id];
                }

                return $config->providedTagIds ?: [];
            });

            if (count($tagIds)) {
                $discussion->tags()->attach($tagIds);

                foreach ($tagIds as $tagId) {
                    if (!in_array($tagId, $tagIdsToRefresh)) {
                        $tagIdsToRefresh[] = $tagId;
                    }
                }
            }

            $newDiscussionIds[] = $discussion->id;
            $discussionIdsToRefresh[] = $discussion->id;

            if (!in_array($author->id, $userIdsToRefresh)) {
                $userIdsToRefresh[] = $author->id;
            }

            $content = $config->reuseInBulkMode('discussion-content', function () use ($faker) {
                return implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10)));
            });
            $post = CommentPost::reply($discussion->id, $content, $author->id, null);
            $post->created_at = $discussion->created_at;
            $post->save();

            // Optimization for below so we don't require an additional query per discussion to get the initial number value
            $cachedLastPostNumber[$discussion->id] = 1;

            $output->progressAdvance();
        }

        $output->progressFinish();
        $this->logTimeElapsed($output, $discussionStartTime);

        // Put logic in an IF so that we only do the count() check if necessary
        if ($config->postCount > 0) {
            if (is_array($config->providedDiscussionIds)) {
                $discussionIdsForNewPosts = $config->providedDiscussionIds;
            } else if (count($newDiscussionIds)) {
                $discussionIdsForNewPosts = $newDiscussionIds;
            } else {
                // Use a limit to reduce the risk of hitting memory limits with a huge array
                // Use random order so that if we do hit the limit, we can still get any discussion
                $discussionIdsForNewPosts = Discussion::query()
                    ->where('is_private', false)
                    ->inRandomOrder()
                    ->limit(10000)
                    ->pluck('id');
            }

            $count = count($discussionIdsForNewPosts);

            if ($count === 0) {
                throw new ValidationException([
                    'discussions' => $this->translator->trans('migratetoflarum-fake-data.api.no-discussions-matched'),
                ]);
            }

            $output->info("Will use $count discussions for post seed");

            $postStartTime = Carbon::now();

            $output->info("Seeding {$config->postCount} posts");
            $output->progressStart($config->postCount);

            for ($i = 0; $i < $config->postCount; $i++) {
                $authorId = $config->reuseInBulkMode('post-author', function () use ($faker, $userIdsForNewContent) {
                    return (int)$faker->randomElement($userIdsForNewContent);
                });
                // Don't place this in reuseInBulkMode() wrapper or all new posts would go into a single discussion
                $discussionId = (int)$faker->randomElement($discussionIdsForNewPosts);

                // Add the randomly selected discussions to the list of discussions in need of a meta update
                // We skip the check if new discussions were created above, because we are certain those are already in the array
                if ($config->discussionCount === 0 && !in_array($discussionId, $discussionIdsToRefresh)) {
                    $discussionIdsToRefresh[] = $discussionId;
                }

                if (!in_array($authorId, $userIdsToRefresh)) {
                    $userIdsToRefresh[] = $authorId;
                }

                $content = $config->reuseInBulkMode('discussion-content', function () use ($faker) {
                    return implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10)));
                });
                $post = CommentPost::reply($discussionId, $content, $authorId, null);
                $post->created_at = $config->nextDate();

                // Re-create logic from Post::boot()
                // We can actually skip setting the ->type because CommentPost::reply already does it even though it doesn't need to
                // Since Flarum 1.3 we don't need to read/update post_number_index attribute since it's unused and deprecated
                // We still maintain our own temporary $updatedDiscussionNumberIndex because it makes the batch save easier
                // with the correct values already bound instead of having one sub-query for each row
                if (Arr::exists($cachedLastPostNumber, $discussionId)) {
                    $post->number = $cachedLastPostNumber[$discussionId] + 1;
                } else {
                    // Same code as Post::boot() but as an actual query instead of a wrapped expression
                    $nextNumber = $this->db->table('posts', 'pn')
                        ->whereRaw($this->db->getTablePrefix() . 'pn.discussion_id = ' . $discussionId)
                        ->selectRaw('(IFNULL(MAX(' . $this->db->getTablePrefix() . 'pn.number), 0) + 1) as next_number')
                        ->get('next_number');

                    $post->number = $nextNumber->isEmpty() ? 1 : $nextNumber[0]->next_number;
                }
                $cachedLastPostNumber[$discussionId] = $post->number;

                $this->batchSave($post);

                $output->progressAdvance();
            }

            $this->batchSave();
            $output->progressFinish();
            $this->logTimeElapsed($output, $postStartTime);
        }

        if (count($discussionIdsToRefresh)) {
            $discussionMetaStartTime = Carbon::now();

            $output->info('Updating meta of ' . count($discussionIdsToRefresh) . ' discussions');
            $output->progressStart(count($discussionIdsToRefresh));

            $this->safeWhereIdInEach(Discussion::query(), $discussionIdsToRefresh, function (Discussion $discussion) use ($newDiscussionIds, $output) {
                // Not all discussions need their first post refreshed
                // This IF will save some precious time when seeding large number of replies only
                if (in_array($discussion->id, $newDiscussionIds)) {
                    $discussion->setFirstPost($discussion->comments()->oldest()->first());
                }

                $discussion->refreshLastPost();
                $discussion->refreshCommentCount();
                $discussion->refreshParticipantCount();
                $discussion->save();

                $output->progressAdvance();
            });

            $output->progressFinish();
            $this->logTimeElapsed($output, $discussionMetaStartTime);
        }

        if (count($tagIdsToRefresh)) {
            $tagMetaStartTime = Carbon::now();

            $output->info('Updating meta of ' . count($tagIdsToRefresh) . ' tags');
            $output->progressStart(count($tagIdsToRefresh));

            $this->safeWhereIdInEach(Tag::query(), $tagIdsToRefresh, function (Tag $tag) use ($output) {
                // There is no built-in method to refresh the discussion count because it's all based on events and deltas
                $tag->discussion_count = $tag->discussions()->where('is_private', false)->whereNull('hidden_at')->count();
                $tag->refreshLastPostedDiscussion();
                $tag->save();

                $output->progressAdvance();
            });

            $output->progressFinish();
            $this->logTimeElapsed($output, $tagMetaStartTime);
        }

        if (count($userIdsToRefresh)) {
            $userMetaStartTime = Carbon::now();

            $output->info('Updating meta of ' . count($userIdsToRefresh) . ' users');
            $output->progressStart(count($userIdsToRefresh));

            $this->safeWhereIdInEach(User::query(), $userIdsToRefresh, function (User $user) use ($output) {
                $user->refreshDiscussionCount();
                $user->refreshCommentCount();
                $user->save();

                $output->progressAdvance();
            });

            $output->progressFinish();
            $this->logTimeElapsed($output, $userMetaStartTime);
        }
    }

    protected $batchTable = null;
    protected $batchInsert = [];

    /**
     * To be called in place of AbstractModel::save()
     * @param AbstractModel|null $model Call with no parameter to save remaining queued items
     */
    protected function batchSave(AbstractModel $model = null)
    {
        if (!$model) {
            if (count($this->batchInsert)) {
                $this->batchDoInsert();
            }
            $this->batchTable = null;

            return;
        }

        $table = $model->getTable();

        if ($this->batchTable && $this->batchTable !== $table) {
            throw new \Exception("Switched from table {$this->batchTable} to $table without persisting");
        }

        if ($model->exists) {
            throw new \Exception('Batch save not supported for update');
        }

        $this->batchTable = $table;
        $this->batchInsert[] = $model->getAttributes();

        if (count($this->batchInsert) >= 100) {
            $this->batchDoInsert();
        }
    }

    /**
     * Used by batchSave() to reduce duplicate logic
     */
    protected function batchDoInsert()
    {
        $this->db->table($this->batchTable)->insert($this->batchInsert);
        $this->batchInsert = [];
    }

    /**
     * Retrieves the latest ID value of a given table
     * This will be used to "guess" all the new IDs during mass insertion
     * This will not work if IDs are not continuously incrementing but this shouldn't happen with regular MySQL setups
     * Users who run into this issue can manually provide IDs to work around it
     * @param string $table
     * @return int
     */
    protected function largestId(string $table): int
    {
        return (int)$this->db->table($table)->max('id');
    }

    protected function logTimeElapsed(OutputStyle $output, Carbon $start, string $prefix = 'Completed in '): void
    {
        $milliseconds = $start->diffInMilliseconds();

        if ($milliseconds >= 10000) {
            $output->info($prefix . round($milliseconds / 1000) . 's');
        } else {
            $output->info($prefix . $milliseconds . 'ms');
        }
    }

    /**
     * Performs a Builder::whereIn()->each() call where only a subset of IDs is used in each query
     * This prevents hitting "Prepared statement contains too many placeholders" PDO error
     * @param Builder $query
     * @param array $whereInIds
     * @param callable $callback
     * @param int $count
     */
    protected function safeWhereIdInEach(Builder $query, array $whereInIds, callable $callback, int $count = 1000)
    {
        foreach (array_chunk($whereInIds, $count) as $subsetOfIds) {
            $query->clone()->whereIn('id', $subsetOfIds)->each($callback, $count);
        }
    }
}
