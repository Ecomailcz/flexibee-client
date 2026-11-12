<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use CurlHandle;
use EcomailFlexibee\Config;

use function curl_init;
use function curl_setopt_array;

final readonly class HttpCurlBuilder
{

    private CurlOptionsFactory $curlOptionsFactory;

    public function __construct()
    {
        $this->curlOptionsFactory = new CurlOptionsFactory();
    }

    /**
     * @param array<mixed> $postFields
     * @param array<mixed> $queryParameters
     * @param array<int, string> $headers
     */
    public function build(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config): CurlHandle
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlOptionsFactory->create($url, $httpMethod, $postFields, $queryParameters, $headers, $config));

        return $ch;
    }

}
