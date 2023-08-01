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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($config->getAuthSessionId() !== null) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, false);
            $headers[] = sprintf('X-authSessionId: %s', $config->getAuthSessionId());
        } else {
            curl_setopt($ch, CURLOPT_HTTPAUTH, true);
            curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $config->getUser(), $config->getPassword()));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)');
        $verifySSLCertificate = $config->verifySSLCertificate() && $config->getAuthSessionId() === null;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSLCertificate);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySSLCertificate);

        if (count($postFields) > 0) {
            curl_setopt(
                $ch, CURLOPT_POSTFIELDS, json_encode(
                    [
                    'winstrom' => $postFields,
                    ]
                )
            );
        }

        if (count($queryParameters) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($queryParameters));
        }

        if (count($headers) !== 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        return $ch;
    }

}
