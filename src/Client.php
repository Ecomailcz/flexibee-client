<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
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
use function mb_strtolower;
use function sprintf;

class Client
{

    protected UrlBuilder $queryBuilder;
    protected HttpClient $httpClient;
    protected Config $config;

    private ResponseHydrator $responseHydrator;

    public function __construct(
        string $url,
        string $company,
        string $user,
        string $password,
        string $evidence,
        bool $verifySSLCertificate,
        ?string $authSessionId = null,
        ?string $logFilePath = null
    )
    {
        $this->config = new Config(
            $url,
            $company,
            $user,
            $password,
            $evidence,
            $verifySSLCertificate,
            $authSessionId,
            $logFilePath,
        );
        $this->queryBuilder = new UrlBuilder($this->config);
        $this->responseHydrator = new ResponseHydrator($this->config);
        $this->httpClient = new HttpClient();
    }

    public function isAllowedChangesApi(): bool
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesStatusUrl(),
            Method::get(Method::GET),
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
            Method::get(Method::GET),
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
            Method::get(Method::GET),
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
            Method::get(Method::GET),
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
            Method::get(Method::GET),
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
        $result = array_filter(
            $this->getCompanies()->getData()['companies'],
            fn (array $data): bool => mb_strtolower($data['dbNazev']) === mb_strtolower($this->config->getCompany()),
        );

        if (isset($result['company'])) {
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
            Method::get(Method::POST),
            [],
            [
                'username' => $this->config->getUser(),
                'password' => $this->config->getPassword(),
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
            Method::get(Method::DELETE),
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
            Method::get(Method::DELETE),
            [],
            [],
            [],
            $this->config,
        );
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
    public function findById(int $id, array $uriParameters = []): EvidenceResult
    {
        try {
            return $this->getById($id, $uriParameters);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
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
        return $this->responseHydrator->convertResponseToEvidenceResult(
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
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return new EvidenceResult([]);
        }
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
        $response = $this->callRequest(Method::get(Method::PUT), null, $uriParameters, $postData, [])[0];
        $data = $response->getData();

        if (
            isset($data['created'])
            && (int) $data['created'] === 0
            && isset($data['updated'])
            && (int) $data['updated'] === 0
        ) {
            $errorMessage = sprintf('(%d) %s', $data['status_code'], $data['message']);

            throw new EcomailFlexibeeSaveFailed($errorMessage);
        }

        if (isset($data['success']) && $data['success'] !== 'true' && isset($data['message'])) {
            throw new EcomailFlexibeeSaveFailed($data['message']);
        }

        return new FlexibeeResponse(
            200,
            null,
            true,
            null,
            count($data),
            null,
            $response->getData(),
            [],
        );
    }

    public function getUserRelations(int $objectId): EvidenceResult
    {
        return new EvidenceResult(
            $this->getById($objectId, ['relations' => 'uzivatelske-vazby'])->getData()[0]['uzivatelske-vazby'],
        );
    }

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
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
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
    public function findAttachments(int $evidenceId): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createAttachmentUriByEvidence($evidenceId, null),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
    }

    public function createAttachment(int $evidenceId, string $fileName, string $contentType, string $contentTypeData): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createAttachmentUriByData($evidenceId, $fileName),
            Method::get(Method::PUT),
            ['content' => $contentTypeData],
            [],
            ['Content-Type' => $contentType],
            $this->config,
        );
    }

    public function deleteAttachment(int $evidenceId, int $attachmentId): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createAttachmentUriByEvidence($evidenceId, $attachmentId),
            Method::get(Method::DELETE),
            [],
            [],
            [],
            $this->config,
        );
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
    public function findAttachmentById(int $evidenceId, int $attachmentId): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createAttachmentUriByEvidence($evidenceId, $attachmentId),
            Method::get(Method::GET),
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
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

       return $response->getRowCount() ?? 0;
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
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );

        return $this->responseHydrator->convertResponseToEvidenceResults($response);
    }

    /**
     * @param mixed $queryFilterOrId
     * @param array<mixed> $uriParameters
     * @param array<mixed> $postFields
     * @param array<string> $headers
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function callRequest(Method $httpMethod, $queryFilterOrId, array $uriParameters, array $postFields, array $headers): array
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
            Method::get(Method::GET),
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
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

}
