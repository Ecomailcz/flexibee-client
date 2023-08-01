<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeResponse implements Response
{

    /**
     * FlexibeeResponse constructor.
     *
     * @param array<mixed>  $data
     * @param array<string> $statistics
     */
    public function __construct(private readonly int $statusCode, private readonly ?float $version, private readonly bool $success, private readonly ?string $message = null, private readonly int $rowCount = 0, private readonly ?int $globalVersion = null, private readonly array $data = [], private readonly array $statistics = [])
    {
    }

    public function getVersion(): ?float
    {
        return $this->version;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string>
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getGlobalVersion(): ?int
    {
        return $this->globalVersion;
    }

}
