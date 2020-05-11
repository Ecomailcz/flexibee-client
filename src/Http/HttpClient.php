<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;

final class HttpClient
{

    private \EcomailFlexibee\Http\UrlNormalizer $urlNormalizer;

    private \EcomailFlexibee\Http\HttpCurlBuilder $httpCurlBuilder;

    public function __construct() {
        $this->urlNormalizer = new UrlNormalizer();
        $this->httpCurlBuilder = new HttpCurlBuilder();
    }

    /**
     * @param array<mixed> $postFields
     * @param array<string> $queryParameters
     * @param array<string> $headers
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function request(
        string $url,
        Method $httpMethod,
        array $postFields,
        array $queryParameters,
        array $headers,
        Config $config
    ): \EcomailFlexibee\Http\Response\FlexibeeResponse
    {

        $ch = $this->httpCurlBuilder->build(
            $this->urlNormalizer->normalize($url),
            $httpMethod,
            $postFields,
            $queryParameters,
            $headers,
            $config,
        );

        $output = \curl_exec($ch);
        $output = \is_string($output) ? $output : null;
        $errorMessage = \mb_strlen(\trim(\curl_error($ch)))
            ? null
            : \curl_error($ch);

        return ResponseFactory::createFromOutput(
            $url,
            $httpMethod,
            $output,
            \curl_getinfo($ch, \CURLINFO_HTTP_CODE),
            \curl_errno($ch),
            $errorMessage,
        );
    }

}
