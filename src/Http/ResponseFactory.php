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
use function implode;
use function in_array;
use function json_decode;
use function mb_stripos;
use function mb_strpos;
use function sprintf;
use const CURLE_OK;

final class ResponseFactory
{

    public static function createFromOutput(string $url, Method $httpMethod, ?string $responseContent, int $statusCode, int $errorNumber, ?string $errorMessage): FlexibeeResponse
    {
        if ($responseContent === null) {
            throw new EcomailFlexibeeRequestFail();
        }

        if ($errorNumber !== CURLE_OK && $errorMessage !== null) {
            throw new EcomailFlexibeeConnectionFail(sprintf('cURL error (%s): %s', $errorNumber, $errorMessage));
        }

        // PDF content
        if (mb_strpos($url, '.pdf') !== false) {
            return new FlexibeePdfResponse($responseContent);
        }

        // Backup content
        if ($httpMethod->equalsValue(Method::GET) && mb_stripos($url, '/backup') !== false) {
            return new FlexibeeBackupResponse($responseContent);
        }

        /**
 * @var array|null $data 
*/
        $data = json_decode($responseContent, true);
        $data ??= [];
        $data = $data['winstrom'] ?? $data;
        $results = $data['results'] ?? $data;

        $version = null;
        /**
 * @var string|null $message 
*/
        $message = $responseContent;
        $success = false;
        $statistics = [];
        $rowCount = 0;
        $globalVersion = null;

        if (isset($data['@version'])) {
            $version = (float) $data['@version'];
            unset($data['@version']);
        }

        if (isset($data['@rowCount'])) {
            $rowCount = (int) $data['@rowCount'];
            unset($data['@rowCount']);
        }

        if (isset($data['@globalVersion'])) {
            $globalVersion = (int) $data['@globalVersion'];
            unset($data['@globalVersion']);
        }

        if (isset($data['message'])) {
            $message = $data['message'];
            unset($data['message']);
        }

        if (isset($data['stats'])) {
            $statistics = $data['stats'];
            unset($data['stats']);
        }

        if (isset($data['success'])) {
            $success = (isset($data['success']) && ($data['success'] === 'true' || $data['success'] === true));
            unset($data['success']);
        } elseif(in_array($statusCode, [200, 201], true)) {
            $success = true;
        }

        if ($statusCode === 401) {
            throw new EcomailFlexibeeInvalidAuthorization('Uživatel se musí pro provedení dané operace přihlásit.');
        }

        if ($statusCode === 403) {
            throw new EcomailFlexibeeForbidden('Uživatel na tuto operaci nemá oprávnění. Tato chyba se zobrazí i v případě, že danou operaci neumožňuje licence.');
        }

        if ($statusCode === 406) {
            throw new EcomailFlexibeeNotAcceptableRequest('Cílový formát není nad konkrétním zdrojem podporovaný (např. export adresáře jako ISDOC).');
        }

        if ($statusCode === 405) {
            throw new EcomailFlexibeeMethodNotAllowed();
        }

        if (in_array($statusCode, [500, 400], true)) {
            foreach ($results as $resultData) {
                if (!isset($resultData['errors'])) {
                    continue;
                }

                self::throwErrorMessage($resultData['errors'], $statusCode);
            }

            throw new EcomailFlexibeeRequestFail($message);
        }

        return new FlexibeeResponse($statusCode, $version, $success, $message, $rowCount, $globalVersion, $results, $statistics);
    }

    /**
     * @param  array $errors
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private static function throwErrorMessage(array $errors, int $statusCode): void
    {
        foreach ($errors as $error) {
            $messageLines = [];

            if (isset($error['code'])) {
                $messageLines[] = sprintf('code: %s', $error['code']);
            }

            if (isset($error['for'])) {
                $messageLines[] = sprintf('for attribute: %s', $error['for']);
            }

            if (isset($error['path'])) {
                $messageLines[] = sprintf('path: %s', $error['path']);
            }

            if (isset($error['message'])) {
                $messageLines[] = sprintf('message: %s', $error['message']);
            }

            throw new EcomailFlexibeeRequestFail(implode("\n", $messageLines), $statusCode);
        }
    }

}
