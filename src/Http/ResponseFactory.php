<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail;
use EcomailFlexibee\Exception\EcomailFlexibeeForbidden;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed;
use EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Response\FlexibeeAttachmentResponse;
use EcomailFlexibee\Http\Response\FlexibeeBackupResponse;
use EcomailFlexibee\Http\Response\FlexibeePdfResponse;
use EcomailFlexibee\Http\Response\FlexibeeResponse;

use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function json_decode;
use function mb_stripos;
use function parse_url;
use function reset;
use function sprintf;
use function str_ends_with;

use const CURLE_OK;
use const PHP_URL_PATH;

final class ResponseFactory
{

    private const ERROR_FIELD_LABELS = [
        ['code', 'code'],
        ['for', 'for attribute'],
        ['path', 'path'],
        ['message', 'message'],
    ];

    public static function createFromOutput(
        string $url,
        Method $httpMethod,
        ?string $responseContent,
        int $statusCode,
        int $errorNumber,
        ?string $errorMessage,
        ?string $curlInfoDebug,
    ): FlexibeeResponse
    {
        if ($responseContent === null) {
            throw new EcomailFlexibeeRequestFail();
        }

        if ($errorNumber !== CURLE_OK && $errorMessage !== null) {
            throw new EcomailFlexibeeConnectionFail(sprintf('cURL error (%s): %s', $errorNumber, $errorMessage));
        }

        return self::createBinaryResponse($url, $httpMethod, $responseContent, $statusCode)
            ?? self::createFromJson($responseContent, $statusCode, $curlInfoDebug);
    }

    private static function createBinaryResponse(string $url, Method $httpMethod, string $responseContent, int $statusCode): ?FlexibeeResponse
    {
        if ($httpMethod !== Method::GET) {
            return null;
        }

        if (str_ends_with((string) parse_url($url, PHP_URL_PATH), '.pdf')) {
            return new FlexibeePdfResponse($responseContent);
        }

        if (mb_stripos($url, '/backup') !== false) {
            return new FlexibeeBackupResponse($responseContent);
        }

        return $statusCode === 200 && str_ends_with($url, '/content') ? new FlexibeeAttachmentResponse($responseContent) : null;
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function createFromJson(string $responseContent, int $statusCode, ?string $curlInfoDebug): FlexibeeResponse
    {
        $decoded = json_decode($responseContent, associative: true);
        $data = is_array($decoded) ? $decoded : [];
        $data = self::getArray($data, 'winstrom') ?? $data;
        $results = self::getArray($data, 'results') ?? $data;
        $message = isset($data['message']) && is_string($data['message']) ? $data['message'] : $responseContent;

        self::throwExceptionForStatusCode($statusCode, $curlInfoDebug);

        if (in_array($statusCode, [500, 400], strict: true)) {
            self::throwErrorFromResults($results, $statusCode, $message);
        }

        return new FlexibeeResponse(
            $statusCode,
            self::getNumber($data, '@version'),
            self::isSuccess($data, $statusCode),
            $message,
            self::getInt($data, '@rowCount') ?? 0,
            self::getInt($data, '@globalVersion'),
            $results,
            self::getArray($data, 'stats') ?? [],
        );
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    private static function getArray(array $data, string $key): ?array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<mixed> $data
     */
    private static function getNumber(array $data, string $key): ?float
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : null;
    }

    /**
     * @param array<mixed> $data
     */
    private static function getInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * @param array<mixed> $data
     */
    private static function isSuccess(array $data, int $statusCode): bool
    {
        if (isset($data['success'])) {
            return $data['success'] === 'true' || $data['success'] === true;
        }

        return in_array($statusCode, [200, 201], strict: true);
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     */
    private static function throwExceptionForStatusCode(int $statusCode, ?string $curlInfoDebug): void
    {
        match ($statusCode) {
            401 => throw new EcomailFlexibeeInvalidAuthorization('Uživatel se musí pro provedení dané operace přihlásit.'),
            403 => throw new EcomailFlexibeeForbidden(
                $curlInfoDebug ?? 'Uživatel na tuto operaci nemá oprávnění. Tato chyba se zobrazí i v případě, že danou operaci neumožňuje licence.',
            ),
            405 => throw new EcomailFlexibeeMethodNotAllowed(),
            406 => throw new EcomailFlexibeeNotAcceptableRequest(
                'Cílový formát není nad konkrétním zdrojem podporovaný (např. export adresáře jako ISDOC).',
            ),
            default => null,
        };
    }

    /**
     * @param array<mixed> $results
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function throwErrorFromResults(array $results, int $statusCode, string $message): never
    {
        foreach ($results as $resultData) {
            if (is_array($resultData) && isset($resultData['errors']) && is_array($resultData['errors'])) {
                self::throwErrorMessage($resultData['errors'], $statusCode);
            }
        }

        throw new EcomailFlexibeeRequestFail($message);
    }

    /**
     * @param array<mixed> $errors
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function throwErrorMessage(array $errors, int $statusCode): void
    {
        $error = reset($errors);

        if (!is_array($error)) {
            return;
        }

        $messageLines = [];

        foreach (self::ERROR_FIELD_LABELS as [$field, $label]) {
            if (isset($error[$field]) && is_scalar($error[$field])) {
                $messageLines[] = sprintf('%s: %s', $label, $error[$field]);
            }
        }

        throw new EcomailFlexibeeRequestFail(implode("\n", $messageLines), $statusCode);
    }

}
