<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestSupport;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class MockEmitter implements EmitterInterface
{
    public ResponseInterface|null $response = null;

    #[\Override]
    public function emit(ResponseInterface $response): bool
    {
        $this->response = $response;
        return true;
    }
}
