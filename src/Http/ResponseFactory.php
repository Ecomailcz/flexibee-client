<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail;
use EcomailFlexibee\Exception\EcomailFlexibeeForbidden;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed;
use EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Response\FlexibeeBackupResponse;
use EcomailFlexibee\Http\Response\FlexibeePdfResponse;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use EcomailFlexibee\Http\Response\GenericResponse;
use EcomailFlexibee\Http\Response\ResponseStatusCode;

use function implode;
use function in_array;
use function json_decode;
use function mb_stripos;
use function mb_strpos;
use function sprintf;

use const CURLE_OK;

final class ResponseFactory
{

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public static function checkResponseErrors(?string $responseContent, int $statusCode, int $errorNumber, ?string $errorMessage): void
    {
        if ($responseContent === null) {
            throw new EcomailFlexibeeRequestFail();
        }

        if ($errorNumber !== CURLE_OK && $errorMessage !== null) {
            throw new EcomailFlexibeeConnectionFail(sprintf('cURL error (%s): %s', $errorNumber, $errorMessage));
        }

        if ($statusCode === ResponseStatusCode::UNAUTHORIZED) {
            throw new EcomailFlexibeeInvalidAuthorization('Uživatel se musí pro provedení dané operace přihlásit.');
        }

        if ($statusCode === ResponseStatusCode::FORBIDDEN) {
            throw new EcomailFlexibeeForbidden('Uživatel na tuto operaci nemá oprávnění. Tato chyba se zobrazí i v případě, že danou operaci neumožňuje licence.');
        }

        if ($statusCode === ResponseStatusCode::NOT_ACCEPTABLE) {
            throw new EcomailFlexibeeNotAcceptableRequest('Cílový formát není nad konkrétním zdrojem podporovaný (např. export adresáře jako ISDOC).');
        }

        if ($statusCode === ResponseStatusCode::METHOD_NOT_ALLOWED) {
            throw new EcomailFlexibeeMethodNotAllowed();
        }
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public static function createResponseFromOutput(string $url, Method $httpMethod, ?string $responseContent, int $statusCode, int $errorNumber, ?string $errorMessage): GenericResponse
    {
        self::checkResponseErrors($responseContent, $statusCode, $errorNumber, $errorMessage);
        $responseContent ??= '';

        if (mb_strpos($url, '.pdf') !== false) {
            return new FlexibeePdfResponse($responseContent);
        }

        if ($httpMethod->equalsValue(Method::GET) && mb_stripos($url, '/backup') !== false) {
            return new FlexibeeBackupResponse($responseContent);
        }

        /** @var array|null $data */
        $data = json_decode($responseContent, true);
        $data ??= [];
        $data = $data['winstrom'] ?? $data;
        $results = $data['results'] ?? $data;
        $message = $data['message'] ?? $responseContent;
        $success = isset($data['success']) ? $data['success'] === 'true' || $data['success'] === true : in_array($statusCode, [200, 201], true);
        $statistics = $data['stats'] ?? [];
        $rowCount = isset($data['@rowCount']) ? (int) $data['@rowCount'] : 0;
        $globalVersion = isset($data['@globalVersion']) ? (int) $data['@globalVersion'] : null;
        $version = isset($data['@version']) ? (float) $data['@version'] : null;
        self::checkStatusCode($statusCode, $results, $message);

        return new FlexibeeResponse($statusCode, $version, $success, $message, $rowCount, $globalVersion, $results, $statistics);
    }

    /**
     * @param array $results
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function checkStatusCode(int $statusCode, array $results, string $message): void
    {
        if (in_array($statusCode, [ResponseStatusCode::INTERNAL_ERROR, ResponseStatusCode::BAD_REQUEST], true)) {
            foreach ($results as $resultData) {
                if (!isset($resultData['errors'])) {
                    continue;
                }

                self::throwErrorMessage($resultData['errors'], $statusCode);
            }

            throw new EcomailFlexibeeRequestFail($message);
        }
    }

    /**
     * @param array $errors
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function throwErrorMessage(array $errors, int $statusCode): void
    {
        foreach ($errors as $error) {
            $messageLines = [];

            if (isset($error['code'])) {
                $messageLines[] = sprintf('code: %s',$error['code']);
            }

            if (isset($error['for'])) {
                $messageLines[] = sprintf('for attribute: %s', $error['for']);
            }

            if (isset($error['path'])) {
                $messageLines[] = sprintf('path: %s',$error['path']);
            }

            if (isset($error['message'])) {
                $messageLines[] = sprintf('message: %s', $error['message']);
            }

            throw new EcomailFlexibeeRequestFail(implode("\n", $messageLines), $statusCode);
        }
    }

}
