<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Exception\EcomailFlexibeeConnectionError;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\QueryBuilder;
use EcomailFlexibee\Http\Response\FlexibeePdfResponse;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Http\ResponseFactory;
use EcomailFlexibee\Result\EvidenceResult;

class Client extends ObjectPrototype
{

    /**
     * REST API user
     *
     * @var string
     */
    private $user;

    /**
     * REST API password
     *
     * @var string
     */
    private $password;

    /**
     * Name of the evidence section (adresar, faktura-vydana ...)
     *
     * @var string
     */
    private $evidence;

    /**
     * Enable self signed certificates
     *
     * @var bool
     */
    private $selfSignedCertificate;

    /**
     * @var \EcomailFlexibee\Http\QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string|null
     */
    private $authSessionId;

    public function __construct(string $url, string $company, string $user, string $password, string $evidence, bool $selfSignedCertificate, ?string $authSessionId = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->evidence = $evidence;
        $this->selfSignedCertificate = $selfSignedCertificate;
        $this->queryBuilder = new QueryBuilder($company, $evidence, $url);
        $this->authSessionId = $authSessionId;
    }

    public function getAuthAndRefreshToken(): Response
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $queryParameters = [];
        $queryParameters['username'] = $this->user;
        $queryParameters['password'] = $this->password;

        return $this->makeRequest(Method::get(Method::POST), $this->queryBuilder->createAuthTokenUrl(), [], $headers, $queryParameters);
    }
    
    public function deleteById(int $id): Response
    {
        return $this->makeRequest(Method::get(Method::DELETE), $this->queryBuilder->createUriByIdOnly($id), []);
    }
    
    public function deleteByCustomId(string $id): void
    {
        $this->makeRequest(Method::get(Method::DELETE), $this->queryBuilder->createUriByCustomId($id), []);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $method
     * @param string $url
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function makeRequestPrepared(Method $method, string $url): array
    {
        $response =  $this->makeRequest($method, $this->queryBuilder->createBaseUrl($url));

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param string $id
     * @param array<mixed> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function findByCustomId(string $id, array $queryParams = []): array
    {
        try {
            return $this->getByCustomId($id, $queryParams);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return [];
        }
    }

    /**
     * @param string $id
     * @param array<mixed> $queryParams
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getByCustomId(string $id, array $queryParams = []): array
    {
        $response = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByCustomId($id, $queryParams), []);

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param \EcomailFlexibee\Http\Response\Response $Response
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     */
    private function convertResponseToEvidenceResults(Response $Response): array
    {
        if ($Response->getStatusCode() === 404 || count($Response->getData()) === 0) {
            return [];
        }

        return array_map(static function (array $data){
            return new EvidenceResult($data);
        }, $Response->getData()[$this->evidence]);
    }

    private function convertResponseToEvidenceResult(Response $Response, bool $throwException): EvidenceResult
    {
        if ($Response->getStatusCode() === 404 || count($Response->getData()) === 0) {
            if ($throwException) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }

            return new EvidenceResult([]);
        }

        return new EvidenceResult($Response->getData()[$this->evidence]);
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function findById(int $id, array $queryParams = []): EvidenceResult
    {
        try {
            return $this->getById($id, $queryParams);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return new EvidenceResult([]);
        }
    }

    /**
     * @param string $code
     * @param array<mixed> $queryParams
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getByCode(string $code, array $queryParams = []): EvidenceResult
    {
        $code = mb_substr($code, 0, 20);
        $response = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByCodeOnly(strtoupper($code), $queryParams), []);

        return new EvidenceResult($response->getData()[$this->evidence]);
    }

    /**
     * @param int $id
     * @param array<string> $queryParams
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getById(int $id, array $queryParams = []): EvidenceResult
    {
        $responseData = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByIdOnly($id, $queryParams), []);

        return $this->convertResponseToEvidenceResult($responseData, true);
    }

    /**
     * @param string $code
     * @param array<mixed> $queryParams
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function findByCode(string $code, array $queryParams = []): EvidenceResult
    {
        try {
            return $this->getByCode($code, $queryParams);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return new EvidenceResult([]);
        }
    }

    /**
     * @param array<mixed> $evidenceData
     * @param int|null $id
     * @return \EcomailFlexibee\Http\Response\Response
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function save(array $evidenceData, ?int $id): Response
    {
        if ($id) {
            $evidenceData['id'] = $id;
        }

        $postData = [];
        $postData[$this->evidence] = $evidenceData;

        $response = $this->makeRequest(Method::get(Method::PUT), $this->queryBuilder->createUriByEvidenceOnly([]), $postData);

        $statisticsData = $response->getStatistics();

        if ((int) $statisticsData['created'] === 0 && (int) $statisticsData['updated'] === 0) {
            throw new EcomailFlexibeeSaveFailed();
        }

        return $response;
    }

    /**
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function allInEvidence(): array
    {
        $response = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceWithQueryParameters(['limit' => 0]), [], [], []);

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param int $start
     * @param int $limit
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function chunkInEvidence(int $start, int $limit): array
    {
        $queryParameters = [];
        $queryParameters['limit'] = $limit;
        $queryParameters['start'] = $start;

        $response = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceWithQueryParameters($queryParameters), [], [], []);

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param string $query
     * @param array<string> $queryParameters
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function searchInEvidence(string $query, array $queryParameters): array
    {
        $response = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceForSearchQuery($query, $queryParameters), [], [], []);

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $method
     * @param string $uri
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function makeRawRequest(Method $method, string $uri): array
    {
        $response = $this->makeRequest($method, $this->queryBuilder->createUriByDomainOnly($uri), [], [], []);

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return \EcomailFlexibee\Http\Response\Response
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getPdfById(int $id, array $queryParams = []): Response
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriPdf($id, $queryParams), []);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $httpMethod
     * @param string $url
     * @param array<mixed> $postFields
     * @param array<string> $headers
     * @param array<mixed> $queryParameters
     * @return \EcomailFlexibee\Http\Response\Response|\EcomailFlexibee\Http\Response\FlexibeePdfResponse
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function makeRequest(Method $httpMethod, string $url, array $postFields = [], array $headers = [], array $queryParameters = [])
    {
        $url = urldecode($url);
        /** @var resource $ch */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($this->authSessionId !== null) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, FALSE);
            $headers[] = sprintf('X-authSessionId: %s', $this->authSessionId);
        } else {
            curl_setopt($ch, CURLOPT_HTTPAUTH, TRUE);
            curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->user, $this->password));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)');

        if ($this->selfSignedCertificate || $this->authSessionId !== null) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $postData = [];

        if (count($postFields) !== 0) {
            $postData['winstrom'] = $postFields;
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        }

        if (count($queryParameters) !== 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($queryParameters));
        }

        if (count($headers) !== 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $output = curl_exec($ch);

        if (curl_errno($ch) !== CURLE_OK || !is_string($output)) {
            throw new EcomailFlexibeeConnectionError(sprintf('cURL error (%s): %s', curl_errno($ch), curl_error($ch)));
        }

        if (mb_strpos($url, '.pdf') !== false && is_string($output)) {
            return new FlexibeePdfResponse($output);
        }

        return ResponseFactory::createFromOutput($output, curl_getinfo($ch, CURLINFO_HTTP_CODE));
    }

}
