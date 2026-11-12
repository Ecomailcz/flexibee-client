<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Result\EvidenceResult;

use function array_filter;
use function array_map;
use function count;
use function is_array;
use function is_string;

class ResponseHydrator
{

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function convertResponseToEvidenceResults(Response $response): array
    {
        $data = $this->getDataOrThrowRequestFail($response);
        $evidenceData = $this->getEvidenceData($data);

        return $evidenceData === null
            ? [new EvidenceResult($this->getDataOrStatistics($response, $data))]
            : $this->createEvidenceResults($evidenceData);
    }

    /**
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function convertResponseToPaginatedEvidenceResults(Response $response): array
    {
        $data = $this->getDataOrThrowRequestFail($response);
        $evidenceData = $this->getEvidenceData($data);

        return [
            'data' => $evidenceData === null
                ? [new EvidenceResult($this->getDataOrStatistics($response, $data))]
                : $this->createEvidenceResults($evidenceData),
            'row_count' => $response->getRowCount(),
        ];
    }

    public function convertResponseToEvidenceResult(Response $response, bool $throwException): EvidenceResult
    {
        $data = $response->getData();

        $evidenceData = $this->getEvidenceData($data);

        if ($response->getStatusCode() === 404 || $evidenceData === null) {
            if ($throwException) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }

            return count($data) !== 0
                ? new EvidenceResult($data)
                : new EvidenceResult([]);
        }

        return new EvidenceResult($evidenceData);
    }

    /**
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private function getDataOrThrowRequestFail(Response $response): array
    {
        $data = $response->getData();
        $errors = isset($data[0]) && is_array($data[0]) && isset($data[0]['errors']) && is_array($data[0]['errors'])
            ? $data[0]['errors']
            : null;

        if ($errors === null && (!isset($data['success']) || $data['success'] !== 'false')) {
            return $data;
        }

        $message = $data['message'] ?? null;

        throw new EcomailFlexibeeRequestFail(
            is_string($message) ? $message : $this->createErrorMessage($errors ?? []),
            $response->getStatusCode(),
        );
    }

    /**
     * @param array<mixed> $errors
     */
    private function createErrorMessage(array $errors): string
    {
        $message = '';

        foreach ($errors as $error) {
            if (is_array($error) && isset($error['message']) && is_string($error['message'])) {
                $message .= "\n" . $error['message'];
            }
        }

        return $message;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function getDataOrStatistics(Response $response, array $data): array
    {
        if (count($data) !== 0) {
            return $data;
        }

        $data = $response->getStatistics();
        $data['status_code'] = $response->getStatusCode();
        $data['message'] = $response->getMessage();
        $data['version'] = $response->getVersion();
        $data['row_count'] = $response->getRowCount();

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    private function getEvidenceData(array $data): ?array
    {
        $evidenceData = $data[$this->config->getEvidence()] ?? null;

        return is_array($evidenceData) ? $evidenceData : null;
    }

    /**
     * @param array<mixed> $evidenceData
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     */
    private function createEvidenceResults(array $evidenceData): array
    {
        return array_map(
            static fn (array $item): EvidenceResult => new EvidenceResult($item),
            array_filter($evidenceData, is_array(...)),
        );
    }

}
