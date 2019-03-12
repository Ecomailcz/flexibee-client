<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeResponse implements Response
{

    /**
     * @var float|null
     */
    private $version;

    /**
     * @var bool
     */
    private $success;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array<mixed>
     */
    private $data;

    /**
     * @var array<string>
     */
    private $statistics;

    /**
     * @var int|null
     */
    private $rowCount;

    /**
     * FlexibeeResponse constructor.
     *
     * @param int $statusCode
     * @param float|null $version
     * @param bool $success
     * @param string|null $message
     * @param array<mixed> $data
     * @param array<string> $statistics
     */
    public function __construct(
        int $statusCode,
        ?float $version,
        bool $success,
        ?string $message,
        ?int $rowCount,
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

    public function getRowCount(): ?int
    {
        return $this->rowCount;
    }

}
