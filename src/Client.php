<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\CurlRequest;
use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Http\ResponseHydrator;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibee\Result\EvidenceResult;

use function array_filter;
use function array_merge;
use function count;
use function is_array;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function mb_strtolower;
use function sprintf;

class Client
{

    protected UrlBuilder $queryBuilder;

    protected HttpClient $httpClient;

    private readonly ResponseHydrator $responseHydrator;

    public function __construct(protected Config $config, ?HttpClient $httpClient = null)
    {
        $this->queryBuilder = new UrlBuilder($this->config);
        $this->responseHydrator = new ResponseHydrator($this->config);
        $this->httpClient = $httpClient ?? new HttpClient();
    }

    public function isAllowedChangesApi(): bool
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesStatusUrl(),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        )->isSuccess();
    }

    public function getChangesApiForEvidence(string $evidenceName): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesUrl(['evidence' => $evidenceName]),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    public function getPropertiesForEvidence(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createUri('properties', []),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    public function getAllApiChanges(?string $fromVersion): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesUrl(['start' => $fromVersion]),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    public function getCompanies(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createCompanyUrl(),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @return array<mixed>
     */
    public function getCompany(): array
    {
        $companies = $this->getCompanies()->getData()['companies'] ?? [];
        $result = array_filter(
            is_array($companies) ? $companies : [],
            fn (mixed $data): bool => is_array($data)
                && isset($data['dbNazev'])
                && is_string($data['dbNazev'])
                && mb_strtolower($data['dbNazev']) === mb_strtolower($this->config->getCompany()),
        );

        if (isset($result['company']) && is_array($result['company'])) {
            return $result['company'];
        }

        throw new EcomailFlexibeeNoEvidenceResult();
    }

    /**
     * @param array<mixed> $parameters
     */
    public function getLoginFormUrl(array $parameters): string
    {
        return $this->queryBuilder->createLoginFormUrl($parameters);
    }

    public function getAuthAndRefreshToken(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createAuthTokenUrl(),
            Method::POST,
            [],
            [
                'password' => $this->config->getPassword(),
                'username' => $this->config->getUser(),
            ],
            [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            $this->config,
        );
    }

    public function deleteById(int $id, bool $dryRun = false): Response
    {
        $uriParameters = $dryRun ? ['dry-run' => 'true'] : [];

        return $this->httpClient->request(
            $this->queryBuilder->createUri($id, $uriParameters),
            Method::DELETE,
            [],
            [],
            [],
            $this->config,
        );
    }

    public function deleteByCode(string $id, bool $dryRun = false): void
    {
        $uriParameters = $dryRun ? ['dry-run' => 'true'] : [];
        $this->httpClient->request(
            $this->queryBuilder->createUri(sprintf('code:%s', $id), $uriParameters),
            Method::DELETE,
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
     * @param array<string> $uriParameters
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
     * @param array<mixed> $uriParameters
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
        return $this->responseHydrator->convertResponseToEvidenceResult(
            $this->httpClient->request(
                $this->queryBuilder->createUriByCodeOnly($code, $uriParameters),
                Method::GET,
                [],
                [],
                [],
                $this->config,
            ) ,
            throwException: true,
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
        return $this->responseHydrator->convertResponseToEvidenceResult(
            $this->httpClient->request(
                $this->queryBuilder->createUri($id, $uriParameters),
                Method::GET,
                [],
                [],
                [],
                $this->config,
            ),
            throwException: true,
        );
    }

    /**
     * @param array<mixed> $uriParameters
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
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getAttachments(int $id): array
    {
        return $this->responseHydrator->convertResponseToAttachments(
            $this->httpClient->request($this->queryBuilder->createAttachmentsUrl($id), Method::GET, [], [], [], $this->config),
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
     */
    public function getAttachmentContent(int $id, int $attachmentId): string
    {
        $content = $this->httpClient->request(
            $this->queryBuilder->createAttachmentContentUrl($id, $attachmentId),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        )->getData()['attachmentContent'] ?? null;

        if (!is_string($content)) {
            throw new EcomailFlexibeeNoEvidenceResult();
        }

        return $content;
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function addAttachment(int $id, string $fileName, string $content, string $contentType): int
    {
        $response = $this->httpClient->send(
            new CurlRequest(
                $this->queryBuilder->createNewAttachmentUrl($id, $fileName),
                Method::PUT,
                headers: [sprintf('Content-Type: %s', $contentType), 'Accept: application/json'],
                body: $content,
            ),
            $this->config,
        );
        $result = $response->getData()[0] ?? null;

        if (is_array($result) && isset($result['id']) && is_numeric($result['id'])) {
            return (int) $result['id'];
        }

        throw new EcomailFlexibeeRequestFail('FlexiBee did not return the ID of the new attachment.');
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function deleteAttachment(int $id, int $attachmentId): void
    {
        $this->httpClient->request($this->queryBuilder->createAttachmentUrl($id, $attachmentId), Method::DELETE, [], [], [], $this->config);
    }

    /**
     * @param list<string> $codes
     * @param array<string> $uriParameters
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function findByCodes(array $codes, array $uriParameters = []): array
    {
        if ($codes === []) {
            return [];
        }

        return $this->responseHydrator->convertResponseToEvidenceResults(
            $this->httpClient->request(
                $this->queryBuilder->createUriByCodes($codes, $uriParameters),
                Method::GET,
                [],
                [],
                [],
                $this->config,
            ),
        );
    }

    /**
     * @param array<mixed> $evidenceData
     * @param array<mixed> $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function save(array $evidenceData, ?int $id, bool $dryRun = false, array $uriParameters = []): FlexibeeResponse
    {
        if ($id !== null) {
            $evidenceData['id'] = $id;
        }

        $postData = [];
        $postData[$this->config->getEvidence()] = $evidenceData;
        $uriParameters = $dryRun
            ? array_merge($uriParameters, ['dry-run' => 'true'])
            : $uriParameters;
        /** @var \EcomailFlexibee\Result\EvidenceResult $response */
        $response = $this->callRequest(Method::PUT, queryFilterOrId: null, uriParameters: $uriParameters, postFields: $postData, headers: [])[0];
        $data = $response->getData();
        $this->throwWhenNotSaved($data);

        return new FlexibeeResponse(
            200,
            version: null,
            success: true,
            rowCount: count($data),
            data: $response->getData(),
            statistics: [],
        );
    }

    public function getUserRelations(int $objectId): EvidenceResult
    {
        $relations = $this->getFirstEvidenceItem($objectId, ['relations' => 'uzivatelske-vazby'])['uzivatelske-vazby'] ?? null;

        return new EvidenceResult(is_array($relations) ? $relations : []);
    }

    public function addUserRelation(int $objectAId, int $objectBId, float $price, int $relationTypeId, ?string $description = null): void
    {
        $objectBCode = $this->getFirstEvidenceItem($objectBId, [])['kod'] ?? null;
        $relationData = [
            'id' => $objectAId,
            'uzivatelske-vazby' => [
                'uzivatelska-vazba' => [
                    'cena' => $price,
                    'evidenceType' => $this->config->getEvidence(),
                    'object' => sprintf('code:%s', is_scalar($objectBCode) ? $objectBCode : ''),
                    'popis' => $description,
                    'vazbaTyp' => $relationTypeId,
                ],
            ],
        ];

        $this->save($relationData, $objectAId);
    }

    /**
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function allInEvidence(): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(['limit' => 0]),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
    }

    public function countInEvidence(): int
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(['add-row-count' => 'true']),
            Method::GET,
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
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
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
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
    }

    /**
     * @param array<string> $uriParameters
     * @return array<mixed>
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
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToPaginatedEvidenceResults($response);
    }

    /**
     * @param array<mixed> $uriParameters
     * @param array<mixed> $postFields
     * @param array<int, string> $headers
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function callRequest(Method $httpMethod, null|int|string $queryFilterOrId, array $uriParameters, array $postFields, array $headers): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUri($queryFilterOrId, $uriParameters),
            $httpMethod,
            $postFields,
            [],
            $headers,
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
    }

    /**
     * @param array<mixed> $uriParameters
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
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    public function findLastInEvidence(bool $fullDetail): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createUriByEvidenceOnly(
                ['order' => 'id', 'limit' => 1, 'detail' => $fullDetail ? 'full' : 'summary'],
            ),
            Method::GET,
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @param array<mixed> $data
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    private function throwWhenNotSaved(array $data): void
    {
        if ($this->isZero($data['created'] ?? null) && $this->isZero($data['updated'] ?? null)) {
            $statusCode = $data['status_code'] ?? 0;
            $message = $data['message'] ?? '';

            throw new EcomailFlexibeeSaveFailed(
                sprintf('(%d) %s', is_int($statusCode) ? $statusCode : 0, is_string($message) ? $message : ''),
            );
        }

        if (isset($data['success'], $data['message']) && $data['success'] !== 'true' && is_string($data['message'])) {
            throw new EcomailFlexibeeSaveFailed($data['message']);
        }
    }

    private function isZero(mixed $value): bool
    {
        return is_numeric($value) && (int) $value === 0;
    }

    /**
     * @param array<string> $uriParameters
     * @return array<mixed>
     */
    private function getFirstEvidenceItem(int $id, array $uriParameters): array
    {
        $item = $this->getById($id, $uriParameters)->getData()[0] ?? null;

        return is_array($item) ? $item : [];
    }

}
