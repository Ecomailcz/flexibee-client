<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

final readonly class CurlRequest
{

    /**
     * @param array<mixed> $postFields
     * @param array<mixed> $queryParameters
     * @param array<int, string> $headers
     */
    public function __construct(
        public string $url,
        public Method $httpMethod,
        public array $postFields = [],
        public array $queryParameters = [],
        public array $headers = [],
        public ?string $body = null,
    )
    {
    }

}
