<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Http\Response\FlexibeeResponse;

final class ResponseFactory extends ObjectPrototype
{

    protected function __construct()
    {
    }

    public static function createFromOutput(string $response, int $statusCode): FlexibeeResponse
    {

        /** @var array<mixed>|null $data */
        $data = json_decode($response, true);
        $data = $data === null || $statusCode === 404 ? [] : $data;
        $data = $data['winstrom'] ?? $data;

        /** @var float|null $message */
        $version = null;
        /** @var string|null $message */
        $message = null;
        $success = false;
        $statistics = [];

        if (isset($data['@version'])) {
            $version = (float) $data['@version'];
            unset($data['@version']);
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
        }

        $results = $data['results'] ?? $data;

        if (in_array($statusCode, [500, 400])) {
            foreach ($results as $resultData) {
                if (!isset($resultData['errors'])) {
                    continue;
                }

                self::throwErrorMessage($resultData['errors']);
            }

            throw new EcomailFlexibeeRequestError($message);
        }

        return new FlexibeeResponse(
            $statusCode,
            $version,
            $success,
            $message,
            $results,
            $statistics
        );
    }

    /**
     * @param array<mixed> $errors
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    private static function throwErrorMessage(array $errors): void
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

            throw new EcomailFlexibeeRequestError(implode("\n", $messageLines));
        }
    }

}
