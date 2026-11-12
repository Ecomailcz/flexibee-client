<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Result;

final readonly class EvidenceResult
{

    /**
     * @param array<mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

}
