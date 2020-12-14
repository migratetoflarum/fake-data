<?php

namespace MigrateToFlarum\FakeData\Controllers;

use Carbon\Carbon;
use Faker\Factory;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Post\CommentPost;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use MigrateToFlarum\FakeData\Faker\FlarumInternetProvider;
use MigrateToFlarum\FakeData\Faker\FlarumUniqueProvider;
use MigrateToFlarum\FakeData\Validators\FakeDataParametersValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Laminas\Diactoros\Response\EmptyResponse;

class FakeDataController implements RequestHandlerInterface
{
    protected $validator;
    protected $inBulkMode = false;
    protected $bulkModeCache = [];

    public function __construct(FakeDataParametersValidator $validator)
    {
        $this->validator = $validator;
    }

    protected function reuseInBulkMode(string $key, callable $callback)
    {
        if (!$this->inBulkMode) {
            return $callback();
        }

        if (!Arr::exists($this->bulkModeCache, $key)) {
            $this->bulkModeCache[$key] = $callback();
        }

        return $this->bulkModeCache[$key];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request->getAttribute('actor')->assertAdmin();

        $attributes = $request->getParsedBody();

        $this->inBulkMode = (bool)Arr::get($attributes, 'bulk');

        $userCount = Arr::get($attributes, 'user_count', 0);
        $providedUserIds = Arr::get($attributes, 'user_ids');
        $discussionCount = Arr::get($attributes, 'discussion_count', 0);
        $providedDiscussionIds = Arr::get($attributes, 'discussion_ids');
        $postCount = Arr::get($attributes, 'post_count', 0);

        $this->validator->assertValid([
            'user_count' => $userCount,
            'user_ids' => $providedUserIds,
            'discussion_count' => $discussionCount,
            'discussion_ids' => $providedDiscussionIds,
            'post_count' => $postCount,
        ]);

        $faker = Factory::create();
        $faker->addProvider(new FlarumInternetProvider($faker));
        $faker->addProvider(new FlarumUniqueProvider($faker));

        $userIds = [];
        $bulkUserIncrement = 1;

        for ($i = 0; $i < $userCount; $i++) {
            $user = new User();
            $user->email = $this->inBulkMode ? $this->reuseInBulkMode('email-prefix', function () use ($faker) {
                    return $faker->domainWord;
                }) . $bulkUserIncrement . $this->reuseInBulkMode('email-domain', function () use ($faker) {
                    return '@' . $faker->safeEmailDomain;
                }) : $faker->flarumUnique()->safeEmail;
            $user->username = $this->inBulkMode ? $this->reuseInBulkMode('username-prefix', function () use ($faker) {
                    return $faker->flarumUnique()->userName;
                }) . $bulkUserIncrement : $faker->flarumUnique()->userName;
            $user->is_email_confirmed = true;
            $user->joined_at = Carbon::now();
            $user->save();

            $userIds[] = $user->id;
            $bulkUserIncrement++;
        }

        // Put logic in an IF so that we only do the count() check if necessary
        if ($discussionCount > 0 || $postCount > 0) {
            $userQuery = User::query()->inRandomOrder();

            if ($userCount > 0) {
                $userQuery->whereIn('id', $userIds);
            }

            if (is_array($providedUserIds)) {
                $userQuery->whereIn('id', $providedUserIds);
            }

            if ($userQuery->count() === 0) {
                throw new ValidationException([
                    'users' => [
                        app(TranslatorInterface::class)->trans('migratetoflarum-fake-data.api.no-users-matched'),
                    ],
                ]);
            }
        }

        $discussionIds = [];
        $discussionIdsToRefresh = [];

        for ($i = 0; $i < $discussionCount; $i++) {
            $author = $this->reuseInBulkMode('discussion-author', function () use ($userQuery) {
                return $userQuery->first();
            });
            $title = $this->reuseInBulkMode('discussion-title', function () use ($faker) {
                return $faker->sentence($faker->numberBetween(1, 6));
            });
            $discussion = Discussion::start($title, $author);
            $discussion->save();

            $discussionIds[] = $discussion->id;
            $discussionIdsToRefresh[] = $discussion->id;

            $content = $this->reuseInBulkMode('discussion-content', function () use ($faker) {
                return implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10)));
            });
            $post = CommentPost::reply($discussion->id, $content, $author->id, null);
            $post->save();
        }

        // Put logic in an IF so that we only do the count() check if necessary
        if ($postCount > 0) {
            $discussionQuery = Discussion::query()->inRandomOrder();

            if ($discussionCount > 0) {
                $discussionQuery->whereIn('id', $discussionIds);
            }

            if (is_array($providedDiscussionIds)) {
                $discussionQuery->whereIn('id', $providedDiscussionIds);
            }

            if ($discussionQuery->count() === 0) {
                throw new ValidationException([
                    'discussions' => [
                        app(TranslatorInterface::class)->trans('migratetoflarum-fake-data.api.no-discussions-matched'),
                    ],
                ]);
            }

            for ($i = 0; $i < $postCount; $i++) {
                $author = $this->reuseInBulkMode('post-author', function () use ($userQuery) {
                    return $userQuery->first();
                });
                $discussion = $discussionQuery->first();

                // Add the randomly selected discussions to the list of discussions in need of a meta update
                // We skip the check if new discussions were created above, because we are certain those are already in the array
                if ($discussionCount === 0 && !in_array($discussion->id, $discussionIdsToRefresh)) {
                    $discussionIdsToRefresh[] = $discussion->id;
                }

                $content = $this->reuseInBulkMode('discussion-content', function () use ($faker) {
                    return implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10)));
                });
                $post = CommentPost::reply($discussion->id, $content, $author->id, null);
                $post->save();
            }
        }

        if (count($discussionIdsToRefresh)) {
            Discussion::query()->whereIn('id', $discussionIdsToRefresh)->chunk(100, function (Collection $discussions) {
                $discussions->each(function (Discussion $discussion) {
                    $discussion->refreshLastPost();
                    $discussion->refreshCommentCount();
                    $discussion->refreshParticipantCount();
                    $discussion->save();
                });
            });
        }

        return new EmptyResponse(204);
    }
}
