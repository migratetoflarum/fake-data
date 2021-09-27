<?php

namespace MigrateToFlarum\FakeData\Controllers;

use Illuminate\Console\OutputStyle;
use Laminas\Diactoros\Response\EmptyResponse;
use MigrateToFlarum\FakeData\SeedConfiguration;
use MigrateToFlarum\FakeData\Seeder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class FakeDataController implements RequestHandlerInterface
{
    protected $seeder;

    public function __construct(Seeder $seeder)
    {
        $this->seeder = $seeder;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request->getAttribute('actor')->assertAdmin();

        $this->seeder->seed(new SeedConfiguration($request->getParsedBody()), new OutputStyle(new StringInput(''), new NullOutput()));

        return new EmptyResponse(204);
    }
}
