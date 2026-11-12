<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;

use function count;
use function http_build_query;
use function json_encode;
use function sprintf;

use const CURLAUTH_BASIC;
use const CURLINFO_HEADER_OUT;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPAUTH;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;
use const CURLOPT_USERPWD;
use const JSON_THROW_ON_ERROR;

final class CurlOptionsFactory
{

    private const USER_AGENT = 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)';

    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const TIMEOUT_SECONDS = 25;

    /**
     * @param array<mixed> $postFields
     * @param array<mixed> $queryParameters
     * @param array<int, string> $headers
     * @return array<int, mixed>
     */
    public function create(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config): array
    {
        $verifySSLCertificate = $config->verifySSLCertificate();
        $options = [
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_CUSTOMREQUEST => $httpMethod->value,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => $verifySSLCertificate ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $verifySSLCertificate,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_URL => $url !== '' ? $url : throw new EcomailFlexibeeRequestFail('Request URL must not be empty.'),
            CURLOPT_USERAGENT => self::USER_AGENT,
        ] + $this->createAuthenticationOptions($config);

        if ($config->debugMode()) {
            $options[CURLINFO_HEADER_OUT] = true;
        }

        if (count($postFields) > 0) {
            $options[CURLOPT_POSTFIELDS] = json_encode(['winstrom' => $postFields], JSON_THROW_ON_ERROR);
        }

        if (count($queryParameters) > 0) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($queryParameters);
        }

        if ($config->getAuthSessionId() !== null) {
            $headers[] = sprintf('X-authSessionId: %s', $config->getAuthSessionId());
        }

        if (count($headers) !== 0) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        return $options;
    }

    /**
     * @return array<int, mixed>
     */
    private function createAuthenticationOptions(Config $config): array
    {
        if ($config->getAuthSessionId() !== null) {
            return [CURLOPT_HTTPAUTH => 0];
        }

        return [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => sprintf('%s:%s', $config->getUser(), $config->getPassword()),
        ];
    }

}
