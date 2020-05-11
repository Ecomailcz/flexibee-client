<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Result\EvidenceResult;

class ResponseHydrator extends ObjectPrototype
{

    private \EcomailFlexibee\Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \EcomailFlexibee\Http\Response\Response $response
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function convertResponseToEvidenceResults(Response $response): array
    {
        $data = $response->getData();

        if (isset($data['success']) && $data['success'] === 'false') {
            throw new EcomailFlexibeeRequestError($data['message']);
        }

        if (!isset($data[$this->config->getEvidence()])) {
            if (\count($data) === 0) {
                $data = $response->getStatistics();
                $data['status_code'] = $response->getStatusCode();
                $data['message'] = $response->getMessage();
                $data['version'] = $response->getVersion();
                $data['row_count'] = $response->getRowCount();
            }

            return [new EvidenceResult($data)];
        }

        return \array_map(static function (array $data) {
            return new EvidenceResult($data);
        }, $data[$this->config->getEvidence()]);
    }

    public function convertResponseToEvidenceResult(Response $response, bool $throwException): EvidenceResult
    {
        $data = $response->getData();

        if ($response->getStatusCode() === 404 || !isset($data[$this->config->getEvidence()])) {
            if ($throwException) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }

            return \count($data) !== 0 ? new EvidenceResult($data) : new EvidenceResult([]);
        }

        return new EvidenceResult($data[$this->config->getEvidence()]);
    }

}
