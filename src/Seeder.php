<?php

namespace MigrateToFlarum\FakeData;

use Faker\Factory;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\Post\CommentPost;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Console\OutputStyle;
use MigrateToFlarum\FakeData\Faker\FlarumInternetProvider;
use MigrateToFlarum\FakeData\Faker\FlarumUniqueProvider;
use MigrateToFlarum\FakeData\Validators\FakeDataParametersValidator;

class Seeder
{
    protected $validator;
    protected $translator;

    public function __construct(FakeDataParametersValidator $validator, Translator $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    public function seed(SeedConfiguration $config, OutputStyle $output)
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

        $newUserIds = [];
        $bulkUserIncrement = 1;

        $output->info("Seeding {$config->userCount} users");
        $output->progressStart($config->userCount);

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
            $user->save();

            $newUserIds[] = $user->id;
            $bulkUserIncrement++;

            $output->progressAdvance();
        }

        $output->progressFinish();

        // Put logic in an IF so that we only do the count() check if necessary
        if ($config->discussionCount > 0 || $config->postCount > 0) {
            $userQuery = User::query()->inRandomOrder();

            if ($config->userCount > 0) {
                $userQuery->whereIn('id', $newUserIds);
            }

            if (is_array($config->providedUserIds)) {
                $userQuery->whereIn('id', $config->providedUserIds);
            }

            $count = $userQuery->count();

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

        $output->info("Seeding {$config->discussionCount} discussions");
        $output->progressStart($config->discussionCount);

        for ($i = 0; $i < $config->discussionCount; $i++) {
            $author = $config->reuseInBulkMode('discussion-author', function () use ($userQuery) {
                return $userQuery->first();
            });
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

            $output->progressAdvance();
        }

        $output->progressFinish();

        // Put logic in an IF so that we only do the count() check if necessary
        if ($config->postCount > 0) {
            $discussionQuery = Discussion::query()->inRandomOrder();

            if ($config->discussionCount > 0) {
                $discussionQuery->whereIn('id', $newDiscussionIds);
            }

            if (is_array($config->providedDiscussionIds)) {
                $discussionQuery->whereIn('id', $config->providedDiscussionIds);
            }

            $count = $discussionQuery->count();

            if ($count === 0) {
                throw new ValidationException([
                    'discussions' => $this->translator->trans('migratetoflarum-fake-data.api.no-discussions-matched'),
                ]);
            }

            $output->info("Will use $count discussions for post seed");

            $output->info("Seeding {$config->postCount} posts");
            $output->progressStart($config->postCount);

            for ($i = 0; $i < $config->postCount; $i++) {
                $author = $config->reuseInBulkMode('post-author', function () use ($userQuery) {
                    return $userQuery->first();
                });
                $discussion = $discussionQuery->first();

                // Add the randomly selected discussions to the list of discussions in need of a meta update
                // We skip the check if new discussions were created above, because we are certain those are already in the array
                if ($config->discussionCount === 0 && !in_array($discussion->id, $discussionIdsToRefresh)) {
                    $discussionIdsToRefresh[] = $discussion->id;
                }

                if (!in_array($author->id, $userIdsToRefresh)) {
                    $userIdsToRefresh[] = $author->id;
                }

                $content = $config->reuseInBulkMode('discussion-content', function () use ($faker) {
                    return implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10)));
                });
                $post = CommentPost::reply($discussion->id, $content, $author->id, null);
                $post->created_at = $config->nextDate();
                $post->save();

                $output->progressAdvance();
            }

            $output->progressFinish();
        }

        if (count($discussionIdsToRefresh)) {
            $output->info('Updating meta of ' . count($discussionIdsToRefresh) . ' discussions');
            $output->progressStart(count($discussionIdsToRefresh));

            Discussion::query()->whereIn('id', $discussionIdsToRefresh)->each(function (Discussion $discussion) use ($newDiscussionIds, $output) {
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
        }

        if (count($tagIdsToRefresh)) {
            $output->info('Updating meta of ' . count($tagIdsToRefresh) . ' tags');
            $output->progressStart(count($tagIdsToRefresh));

            Tag::query()->whereIn('id', $tagIdsToRefresh)->each(function (Tag $tag) use ($output) {
                // There is no built-in method to refresh the discussion count because it's all based on events and deltas
                $tag->discussion_count = $tag->discussions()->where('is_private', false)->whereNull('hidden_at')->count();
                $tag->refreshLastPostedDiscussion();
                $tag->save();

                $output->progressAdvance();
            });

            $output->progressFinish();
        }

        if (count($userIdsToRefresh)) {
            $output->info('Updating meta of ' . count($userIdsToRefresh) . ' users');
            $output->progressStart(count($userIdsToRefresh));

            User::query()->whereIn('id', $userIdsToRefresh)->each(function (User $user) use ($output) {
                $user->refreshDiscussionCount();
                $user->refreshCommentCount();
                $user->save();

                $output->progressAdvance();
            });

            $output->progressFinish();
        }
    }
}
