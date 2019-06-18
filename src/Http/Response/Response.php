<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

interface Response
{

    public function getVersion(): ?float;

    public function getMessage(): ?string;

    public function isSuccess(): bool;

    public function getStatusCode(): int;

    /**
     * @return array<mixed>
     */
    public function getData(): array;

    /**
     * @return array<string>
     */
    public function getStatistics(): array;

    public function getRowCount(): ?int;

}
