<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use CurlHandle;
use EcomailFlexibee\Config;

use function count;
use function curl_init;
use function curl_setopt;
use function http_build_query;
use function json_encode;
use function sprintf;

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

final class HttpCurlBuilder
{

    /**
     * @param array<string> $postFields
     * @param array<string> $queryParameters
     * @param array<string> $headers
     */
    public function build(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config): CurlHandle
    {
        $curlHandle = $this->initCommonOptions(curl_init());
        $curlHandle = $this->addPostFields($postFields, $curlHandle);
        $curlHandle = $this->addQueryParameters($queryParameters, $curlHandle);
        $curlHandle = $this->addSSLVerifier($config, $curlHandle);
        $curlHandle = $this->addUrl($url, $curlHandle);
        $curlHandle = $this->addHttpMethod($httpMethod, $curlHandle);
        $curlHandle = $this->addAuthSessionId($config, $curlHandle);

        if ($config->getAuthSessionId() !== null) {
            $headers[] = sprintf('X-authSessionId: %s', $config->getAuthSessionId());
        }

        return $this->addHeaders($headers, $curlHandle);
    }

    private function initCommonOptions(CurlHandle $curlHandle): CurlHandle
    {
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Ecomail.cz Flexibee HTTP client (https://github.com/Ecomailcz/flexibee-client)');

        return $curlHandle;
    }

    private function addPostFields(array $postFields, CurlHandle $curlHandle): CurlHandle
    {
        if (count($postFields) > 0) {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode([
                'winstrom' => $postFields,
            ]));
        }

        return $curlHandle;
    }

    private function addQueryParameters(array $queryParameters, CurlHandle $curlHandle): CurlHandle
    {
        if (count($queryParameters) > 0) {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($queryParameters));
        }

        return $curlHandle;
    }

    private function addHeaders(array $headers, CurlHandle $curlHandle): CurlHandle
    {
        if (count($headers) !== 0) {
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        }

        return $curlHandle;
    }

    private function addAuthSessionId(Config $config, CurlHandle $curlHandle): CurlHandle
    {
        if ($config->getAuthSessionId() !== null) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, FALSE);
        } else {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, TRUE);
            curl_setopt($curlHandle, CURLOPT_USERPWD, sprintf('%s:%s', $config->getUser(), $config->getPassword()));
        }

        return $curlHandle;
    }

    private function addHttpMethod(Method $httpMethod, CurlHandle $curlHandle): CurlHandle
    {
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());

        return $curlHandle;
    }

    private function addUrl(string $url, CurlHandle $curlHandle): CurlHandle
    {
        curl_setopt($curlHandle, CURLOPT_URL, $url);

        return $curlHandle;
    }

    private function addSSLVerifier(Config $config, CurlHandle $curlHandle): CurlHandle
    {
        $verifySSLCertificate = $config->verifySSLCertificate() && $config->getAuthSessionId() === null;
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, $verifySSLCertificate);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, $verifySSLCertificate);

        return $curlHandle;
    }

}
