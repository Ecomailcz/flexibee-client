<?php declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Consistence\PhpException;
use Throwable;

class EcomailFlexibeeAnotherError extends PhpException
{

    /**
     * EcomailFlexibeeAnotherError constructor.
     *
     * @param array<mixed> $responseData
     * @param \Throwable|null $previous
     */
    public function __construct(array $responseData, ?Throwable $previous = null)
    {
        $jsonData = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        parent::__construct(is_string($jsonData) ? $jsonData : '', $previous);
    }

}
