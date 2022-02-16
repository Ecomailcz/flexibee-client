<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Result;

final class EvidenceResult
{

    /**
     * @param array $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

}
