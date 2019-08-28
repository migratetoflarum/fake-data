<?php

namespace MigrateToFlarum\FakeData\Controllers;

use Carbon\Carbon;
use Faker\Factory;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\User;
use Illuminate\Support\Arr;
use MigrateToFlarum\FakeData\Validators\FakeDataParametersValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

        $userCount = Arr::get($attributes, 'user_count');
        $discussionCount = Arr::get($attributes, 'discussion_count');
        $postCount = Arr::get($attributes, 'post_count');

        $this->validator->assertValid([
            'user_count' => $userCount,
            'discussion_count' => $discussionCount,
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

        $userQuery = User::query()->inRandomOrder();

        if ($userCount > 0) {
            $userQuery->whereIn('id', $userIds);
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

        $discussionQuery = Discussion::query()->inRandomOrder();

        if ($discussionCount > 0) {
            $discussionQuery->whereIn('id', $discussionIds);
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

        return new EmptyResponse(204);
    }
}
