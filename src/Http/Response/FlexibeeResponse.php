<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeResponse implements Response
{

    /**
     * FlexibeeResponse constructor.
     *
     * @param array<mixed> $data
     * @param array<string> $statistics
     */
    public function __construct(private int $statusCode, private ?float $version, private bool $success, private ?string $message = null, private int $rowCount = 0, private ?int $globalVersion = null, private array $data = [], private array $statistics = [])
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
