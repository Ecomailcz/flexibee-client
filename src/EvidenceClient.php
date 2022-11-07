<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Result\EvidenceResult;

use function array_merge;
use function sprintf;

final class EvidenceClient extends Client
{

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getPropertiesForEvidence(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createUri('properties', []),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function deleteById(int $id, bool $dryRun = false): Response
    {
        $uriParameters = $dryRun ? ['dry-run' => 'true'] : [];

        return $this->httpClient->request(
            $this->queryBuilder->createUri($id, $uriParameters),
            Method::get(Method::DELETE),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function deleteByCode(string $id, bool $dryRun = false): void
    {
        $uriParameters = $dryRun ? ['dry-run' => 'true'] : [];
        $this->httpClient->request(
            $this->queryBuilder->createUri(sprintf('code:%s', $id), $uriParameters),
            Method::get(Method::DELETE),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @param array $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function findById(int $id, array $uriParameters = []): EvidenceResult
    {
        try {
            return $this->getById($id, $uriParameters);
        } catch (EcomailFlexibeeNoEvidenceResult) {
            return new EvidenceResult([]);
        }
    }

    /**
     * @param array $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getByCode(string $code, array $uriParameters = []): EvidenceResult
    {
        return $this->responseDataBuilder->convertResponseToEvidenceResult(
            $this->httpClient->request(
                $this->queryBuilder->createUriByCodeOnly($code, $uriParameters),
                Method::get(Method::GET),
                [],
                [],
                [],
                $this->config,
            ) ,
            true,
        );
    }

    /**
     * @param array<string> $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getById(int $id, array $uriParameters = []): EvidenceResult
    {
        return $this->responseDataBuilder->convertResponseToEvidenceResult(
            $this->httpClient->request(
                $this->queryBuilder->createUri($id, $uriParameters),
                Method::get(Method::GET),
                [],
                [],
                [],
                $this->config,
            ),
            true,
        );
    }

    /**
     * @param array $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function findByCode(string $code, array $uriParameters = []): EvidenceResult
    {
        try {
            return $this->getByCode($code, $uriParameters);
        } catch (EcomailFlexibeeNoEvidenceResult) {
            return new EvidenceResult([]);
        }
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getUserRelations(int $objectId): EvidenceResult
    {
        return new EvidenceResult(
            $this->getById($objectId, ['relations' => 'uzivatelske-vazby'])->getData()[0]['uzivatelske-vazby'],
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function addUserRelation(int $objectAId, int $objectBId, float $price, int $relationTypeId, ?string $description = null): void
    {
        $objectBData = $this->getById($objectBId, [])->getData()[0];
        $relationData = [
            'id' => $objectAId,
            'uzivatelske-vazby' => [
                'uzivatelska-vazba' => [
                    'vazbaTyp' => $relationTypeId,
                    'cena' => $price,
                    'popis' => $description,
                    'evidenceType' => $this->config->getEvidence(),
                    'object' => sprintf('code:%s', $objectBData['kod']),
                ],
            ],
        ];

        $this->save($relationData, $objectAId);
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function countInEvidence(): int
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(['add-row-count' => 'true']),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $response->getRowCount();
    }

    /**
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function chunkInEvidence(int $start, int $limit): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(['limit' => $limit, 'start' => $start]),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseDataBuilder->convertResponseToEvidenceResults($response);
    }

    /**
     * @param array<string> $uriParameters
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function searchInEvidence(string $query, array $uriParameters): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createFilterQuery($query, $uriParameters),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseDataBuilder->convertResponseToEvidenceResults($response);
    }

    /**
     * @param array<string> $uriParameters
     * @return array
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function searchInEvidencePaginated(string $query, array $uriParameters): array
    {
        $uriParameters = array_merge($uriParameters, ['add-row-count' => 'true']);
        $response = $this->httpClient->request(
            $this->queryBuilder->createFilterQuery($query, $uriParameters),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseDataBuilder->convertResponseToPaginatedEvidenceResults($response);
    }

    /**
     * @param array $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getPdfById(int $id, array $uriParameters): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createPdfUrl($id, $uriParameters),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function findLastInEvidence(bool $fullDetail): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(
                ['order' => 'id', 'limit' => 1, 'detail' => $fullDetail ? 'full' : 'summary'],
            ),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

}