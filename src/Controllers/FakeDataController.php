<?php

namespace MigrateToFlarum\FakeData\Controllers;

use Illuminate\Console\OutputStyle;
use Laminas\Diactoros\Response\JsonResponse;
use MigrateToFlarum\FakeData\SeedConfiguration;
use MigrateToFlarum\FakeData\Seeder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

        $output = new BufferedOutput();

        $this->seeder->seed(new SeedConfiguration($request->getParsedBody()), new OutputStyle(new StringInput(''), $output));

        // A TextResponse could be used but using JSON we prevent future breaking changes if we ever add more attributes to the response
        return new JsonResponse([
            'output' => $output->fetch(),
        ]);
    }
}
