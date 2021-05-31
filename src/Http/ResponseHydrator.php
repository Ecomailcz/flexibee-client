<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Result\EvidenceResult;
use function array_map;
use function count;

class ResponseHydrator extends ObjectPrototype
{

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function convertResponseToEvidenceResults(Response $response): array
    {
        $data = $response->getData();
        $hasErrors = isset($data[0], $data[0]['errors']);

        if ($hasErrors || (isset($data['success']) && $data['success'] === 'false')) {

            if ($hasErrors && !isset($data['message'])) {
                $data['message'] = '';

                foreach ($data[0]['errors'] as $errors) {
                    $data['message'] .= "\n" . $errors['message'];
                }
            }

            throw new EcomailFlexibeeRequestFail($data['message'], $response->getStatusCode());
        }

        if (!isset($data[$this->config->getEvidence()])) {
            if (count($data) === 0) {
                $data = $response->getStatistics();
                $data['status_code'] = $response->getStatusCode();
                $data['message'] = $response->getMessage();
                $data['version'] = $response->getVersion();
                $data['row_count'] = $response->getRowCount();
            }

            return [new EvidenceResult($data)];
        }

        return array_map(static fn (array $data) => new EvidenceResult($data), $data[$this->config->getEvidence()]);
    }

    public function convertResponseToEvidenceResult(Response $response, bool $throwException): EvidenceResult
    {
        $data = $response->getData();

        if ($response->getStatusCode() === 404 || !isset($data[$this->config->getEvidence()])) {
            if ($throwException) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }

            return count($data) !== 0
                ? new EvidenceResult($data)
                : new EvidenceResult([]);
        }

        return new EvidenceResult($data[$this->config->getEvidence()]);
    }

}
