<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeResponse implements Response
{

    private ?float $version = null;

    private bool $success;

    private ?string $message = null;

    private int $statusCode;

    /**
     * @var array<mixed>
     */
    private array $data;

    /**
     * @var array<string>
     */
    private array $statistics = [];

    private int $rowCount = 0;

    private ?int $globalVersion = null;

    /**
     * FlexibeeResponse constructor.
     *
     * @param array<mixed> $data
     * @param array<string> $statistics
     */
    public function __construct(
        int $statusCode,
        ?float $version,
        bool $success,
        ?string $message,
        int $rowCount,
        ?int $globalVersion,
        array $data = [],
        array $statistics = []
    )
    {
        $this->version = $version;
        $this->success = $success;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->statistics = $statistics;
        $this->rowCount = $rowCount;
        $this->globalVersion = $globalVersion;
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
