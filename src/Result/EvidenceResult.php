<?php declare(strict_types = 1);

namespace EcomailFlexibee\Result;

use Consistence\ObjectPrototype;

final class EvidenceResult extends ObjectPrototype
{

    /**
     * @var array<mixed>
     */
    private $data;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

}
