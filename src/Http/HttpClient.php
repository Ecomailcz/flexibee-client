<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

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

        $startTime = \microtime(true);
        $output = \curl_exec($ch);
        $responseTime = \microtime(true) - $startTime;
        $output = \is_string($output) ? $output : null;
        $errorMessage = \mb_strlen(\trim(\curl_error($ch))) === 0
            ? null
            : \curl_error($ch);
        $statusCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        if ($config->getLogFilePath() !== null) {
            $rootDir = \dirname($config->getLogFilePath());
            $fileSystem = new Filesystem(new Local($rootDir, \FILE_APPEND));
            $logContent = \sprintf(
                '%s METHOD: %s URL:%s TIME:%s STATUS:%s',
                \date('Y-m-d H:i:s'),
                $httpMethod->getValue(),
                $url,
                \number_format($responseTime, 2),
                $statusCode,
            );

            if ($errorMessage !== null) {
                $logContent = \sprintf('%s ERROR:%s', $logContent, $errorMessage);
            }

            $logContent .= "\n";
            $fileSystem->put(
                \basename($config->getLogFilePath()),
                $logContent,
            );
        }

        return ResponseFactory::createFromOutput(
            $url,
            $httpMethod,
            $output,
            $statusCode,
            \curl_errno($ch),
            $errorMessage,
        );
    }

}
