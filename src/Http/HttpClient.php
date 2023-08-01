<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function is_string;
use function mb_strlen;
use function trim;
use const CURLINFO_HTTP_CODE;

final class HttpClient
{

    private readonly HttpCurlBuilder $httpCurlBuilder;

    public function __construct()
    {
        $this->httpCurlBuilder = new HttpCurlBuilder();
    }

    /**
     * @param  array $postFields
     * @param  array $queryParameters
     * @param  array $headers
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function request(string $url, Method $httpMethod, array $postFields, array $queryParameters, array $headers, Config $config): FlexibeeResponse
    {

        $ch = $this->httpCurlBuilder->build($url, $httpMethod, $postFields, $queryParameters, $headers, $config);

        $output = curl_exec($ch);
        $output = is_string($output) ? $output : null;
        $errorMessage = mb_strlen(trim(curl_error($ch))) === 0
            ? null
            : curl_error($ch);
        /**
 * @var int $statusCode 
*/
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return ResponseFactory::createFromOutput(
            $url,
            $httpMethod,
            $output,
            $statusCode,
            curl_errno($ch),
            $errorMessage,
        );
    }

}
