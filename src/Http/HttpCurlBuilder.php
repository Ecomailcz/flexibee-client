<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;

final class HttpCurlBuilder
{

    /**
     * @param array<string> $postFields
     * @param array<string> $queryParameters
     * @param array<string> $headers
     * @return resource
     */
    public function build(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config)
    {
        /** @var resource $ch */
        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, TRUE);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, TRUE);

        if ($config->getAuthSessionId() !== null) {
            \curl_setopt($ch, \CURLOPT_HTTPAUTH, FALSE);
            $headers[] = \sprintf('X-authSessionId: %s', $config->getAuthSessionId());
        } else {
            \curl_setopt($ch, \CURLOPT_HTTPAUTH, TRUE);
            \curl_setopt($ch, \CURLOPT_USERPWD, \sprintf('%s:%s', $config->getUser(), $config->getPassword()));
        }

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());
        \curl_setopt($ch, \CURLOPT_USERAGENT, 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)');

        if ($config->isSelfSignedCertificate() || $config->getAuthSessionId() !== null) {
            \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, FALSE);
            \curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if (\count($postFields) > 0) {
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode([
                'winstrom' => $postFields,
            ]));
        }

        if (\count($queryParameters) > 0) {
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($queryParameters));
        }

        if (\count($headers) !== 0) {
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);
        }

        return $ch;
    }

}
