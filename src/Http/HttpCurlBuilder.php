<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use CurlHandle;
use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;

use function count;
use function curl_init;
use function curl_setopt;
use function http_build_query;
use function json_encode;
use function sprintf;

use const CURLAUTH_BASIC;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPAUTH;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;
use const CURLOPT_USERPWD;
use const JSON_THROW_ON_ERROR;

final class HttpCurlBuilder
{

    /**
     * @param array<mixed> $postFields
     * @param array<mixed> $queryParameters
     * @param array<int, string> $headers
     */
    public function build(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config): CurlHandle
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, value: TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, value: TRUE);

        $headers = $this->setAuthentication($ch, $config, $headers);

        curl_setopt($ch, CURLOPT_URL, $url !== '' ? $url : throw new EcomailFlexibeeRequestFail('Request URL must not be empty.'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod->value);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)');
        $verifySSLCertificate = $config->verifySSLCertificate() && $config->getAuthSessionId() === null;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSLCertificate);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySSLCertificate ? 2 : 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        if($config->debugMode()) {
            curl_setopt($ch, CURLINFO_HEADER_OUT, value: true);
        }

        if (count($postFields) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'winstrom' => $postFields,
            ], JSON_THROW_ON_ERROR));
        }

        if (count($queryParameters) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($queryParameters));
        }

        if (count($headers) !== 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        return $ch;
    }

    /**
     * @param array<int, string> $headers
     * @return array<int, string>
     */
    private function setAuthentication(CurlHandle $ch, Config $config, array $headers): array
    {
        if ($config->getAuthSessionId() !== null) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, 0);
            $headers[] = sprintf('X-authSessionId: %s', $config->getAuthSessionId());

            return $headers;
        }

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $config->getUser(), $config->getPassword()));

        return $headers;
    }

}
