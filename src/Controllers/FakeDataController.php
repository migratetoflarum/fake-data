<?php

namespace MigrateToFlarum\FakeData\Controllers;

use Carbon\Carbon;
use Faker\Factory;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Post\CommentPost;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\User;
use Illuminate\Support\Arr;
use MigrateToFlarum\FakeData\Validators\FakeDataParametersValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\EmptyResponse;

class FakeDataController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    protected $validator;

    public function __construct(FakeDataParametersValidator $validator)
    {
        $this->validator = $validator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $attributes = $request->getParsedBody();

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

        $userIds = [];

        for ($i = 0; $i < $userCount; $i++) {
            $user = new User();
            $user->email = $faker->unique()->safeEmail;
            $user->username = $faker->unique()->userName;
            $user->is_email_confirmed = true;
            $user->joined_at = Carbon::now();
            $user->save();

            $userIds[] = $user->id;
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

        for ($i = 0; $i < $discussionCount; $i++) {
            $author = $userQuery->first();
            $discussion = Discussion::start($faker->sentence($faker->numberBetween(1, 6)), $author);
            $discussion->refreshLastPost();
            $discussion->refreshCommentCount();
            $discussion->refreshParticipantCount();
            $discussion->save();

            $discussionIds[] = $discussion->id;

            $post = CommentPost::reply($discussion->id, implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10))), $author->id, null);
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
                $author = $userQuery->first();
                $discussion = $discussionQuery->first();

                $post = CommentPost::reply($discussion->id, implode("\n\n", $faker->paragraphs($faker->numberBetween(1, 10))), $author->id, null);
                $post->save();

                $discussion->refreshLastPost();
                $discussion->refreshCommentCount();
                $discussion->refreshParticipantCount();
                $discussion->save();
            }
        }

        return new EmptyResponse(204);
    }
}
