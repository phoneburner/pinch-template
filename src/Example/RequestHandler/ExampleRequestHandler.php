<?php

declare(strict_types=1);

namespace App\Example\RequestHandler;

use App\Example\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhoneBurner\Pinch\Component\Http\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ExampleRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->em->getRepository(User::class)->find(1);
        $time = new \DateTimeImmutable()->format(\DATE_RFC3339);

        $this->logger->info('Handled request at ' . $time . 'for user: ' . $user?->username);

        return new HtmlResponse(<<<HTML
            <h1>Hello {$user?->username}!</h1><br><br><strong>The Current Time Is: </strong>{$time}
            HTML);
    }
}
