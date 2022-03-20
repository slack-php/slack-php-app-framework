<?php

namespace SlackPhp\Framework\Tests\Fakes;

use Closure;
use Psr\Http\Message\ResponseInterface;
use SlackPhp\Framework\Http\ResponseEmitter;

class FakeResponseEmitter implements ResponseEmitter
{
    private ?Closure $fn;
    private ?ResponseInterface $lastResponse;

    public function __construct(?callable $fn = null)
    {
        $this->fn = $fn ? Closure::fromCallable($fn) : null;
        $this->lastResponse = null;
    }

    public function emit(ResponseInterface $response): void
    {
        $this->lastResponse = $response;
        if ($this->fn !== null) {
            ($this->fn)($response);
        }
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * @phpstan-return string[]
     */
    public function getLastResponseData(): array
    {
        if ($this->lastResponse === null) {
            return [];
        }

        return json_decode((string) $this->lastResponse->getBody(), true) ?? [];
    }
}
