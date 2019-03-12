<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeConnectionError;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeePdfResponse;
use EcomailFlexibee\Http\Response\Response;
use EcomailFlexibee\Http\ResponseFactory;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibee\Result\EvidenceResult;

class Client
{

    /**
     * @var \EcomailFlexibee\Http\UrlBuilder
     */
    private $queryBuilder;

    /**
     * @var \EcomailFlexibee\Config
     */
    private $config;

    public function __construct(
        string $url,
        string $company,
        string $user,
        string $password,
        string $evidence,
        bool $selfSignedCertificate,
        ?string $authSessionId = null
    )
    {
        $this->config = new Config(
            $url,
            $company,
            $user,
            $password,
            $evidence,
            $selfSignedCertificate,
            $authSessionId
        );
        $this->queryBuilder = new UrlBuilder($this->config);
    }

    /**
     * @param array<mixed> $parameters
     * @return string
     */
    public function getLoginFormUrl(array $parameters): string
    {
        return $this->queryBuilder->createLoginFormUrl($parameters);
    }

    public function getAuthAndRefreshToken(): Response
    {
        return $this->makeRequest(
            Method::get(Method::POST),
            $this->queryBuilder->createAuthTokenUrl(),
            [],
            [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            [
                'username' => $this->config->getUser(),
                'password' => $this->config->getPassword(),
            ]
        );
    }
    
    public function deleteById(int $id): Response
    {
        return $this->makeRequest(
            Method::get(Method::DELETE),
            $this->queryBuilder->createUri($id, []),
            []
        );
    }
    
    public function deleteByCode(string $id): void
    {
        $this->makeRequest(
            Method::get(Method::DELETE),
            $this->queryBuilder->createUri(sprintf('code:%s', $id), []),
            [],
            [],
            []
        );
    }

    /**
     * @param \EcomailFlexibee\Http\Response\Response $response
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     */
    private function convertResponseToEvidenceResults(Response $response): array
    {
        $data = $response->getData();

        if (!isset($data[$this->config->getEvidence()])) {
            return count($data) !== 0  ? [new EvidenceResult($data)] : [];
        }

        if (!isset($data[$this->config->getEvidence()])) {
            return [new EvidenceResult($data)];
        }

        return array_map(static function (array $data){
            return new EvidenceResult($data);
        }, $data[$this->config->getEvidence()]);
    }

    private function convertResponseToEvidenceResult(Response $response, bool $throwException): EvidenceResult
    {
        $data = $response->getData();

        if ($response->getStatusCode() === 404 || !isset($data[$this->config->getEvidence()])) {
            if ($throwException) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }

            return count($data) !== 0  ? new EvidenceResult($data) : new EvidenceResult([]);
        }

        return new EvidenceResult($data[$this->config->getEvidence()]);
    }

    /**
     * @param int $id
     * @param array<mixed> $uriParameters
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
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
     * @param string $code
     * @param array<mixed> $uriParameters
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getByCode(string $code, array $uriParameters = []): EvidenceResult
    {
        return $this->convertResponseToEvidenceResult(
            $this->makeRequest(
                Method::get(Method::GET),
                $this->queryBuilder->createUriByCodeOnly($code, $uriParameters),
                []
            ) ,
            true
        );
    }

    /**
     * @param int $id
     * @param array<string> $uriParameters
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getById(int $id, array $uriParameters = []): EvidenceResult
    {
        return $this->convertResponseToEvidenceResult(
            $this->makeRequest(
                Method::get(Method::GET),
                $this->queryBuilder->createUri($id, $uriParameters),
                []
            ),
            true
        );
    }

    /**
     * @param string $code
     * @param array<mixed> $uriParameters
     * @return \EcomailFlexibee\Result\EvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
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
     * @param int|null $id
     * @param bool $dryRun
     * @return \EcomailFlexibee\Http\Response\Response
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function save(array $evidenceData, ?int $id, bool $dryRun = false): Response
    {
        if ($id !== null) {
            $evidenceData['id'] = $id;
        }

        $postData = [];
        $postData[$this->config->getEvidence()] = $evidenceData;
        $uriParameters = $dryRun ? ['dry-run' => 'true'] : [];
        $response = $this->makeRequest(Method::get(Method::PUT), $this->queryBuilder->createUriByEvidenceOnly($uriParameters), $postData);
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
        $response = $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createUriByEvidenceOnly(['limit' => 0]),
            [],
            [],
            []
        );

        return $this->convertResponseToEvidenceResults($response);
    }

    public function countInEvidence(): int
    {
        $response = $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createUriByEvidenceOnly(['add-row-count' => 'true']),
            [],
            [],
            []
        );

        /** @var int $result */
        $result = $response->getRowCount() ?? 0;

        return $result;
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
        $response = $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createUriByEvidenceOnly(['limit' => $limit, 'start' => $start]),
            [],
            [],
            []
        );

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param string $query
     * @param array<string> $uriParameters
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function searchInEvidence(string $query, array $uriParameters): array
    {
        $response = $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createFilterQuery($query, $uriParameters),
            [],
            [],
            []
        );

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $httpMethod
     * @param mixed $queryFilterOrId
     * @param array<mixed> $uriParameters
     * @param array<mixed> $postFields
     * @param array<string> $headers
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function callRequest(
        Method $httpMethod,
        $queryFilterOrId,
        array $uriParameters,
        array $postFields,
        array $headers
    ): array
    {
        $response = $this->makeRequest(
            $httpMethod,
            $this->queryBuilder->createUri($queryFilterOrId, $uriParameters),
            $postFields,
            $headers
        );

        return $this->convertResponseToEvidenceResults($response);
    }

    /**
     * @param int $id
     * @param array<mixed> $uriParameters
     * @return \EcomailFlexibee\Http\Response\Response
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getPdfById(int $id, array $uriParameters): Response
    {
        return $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createPdfUrl($id, $uriParameters),
            []
        );
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
    private function makeRequest(Method $httpMethod, string $url, array $postFields = [], array $headers = [], array $queryParameters = [])
    {
        $url = urldecode($url);

        /** @var resource $ch */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($this->config->getAuthSessionId() !== null) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, FALSE);
            $headers[] = sprintf('X-authSessionId: %s', $this->config->getAuthSessionId());
        } else {
            curl_setopt($ch, CURLOPT_HTTPAUTH, TRUE);
            curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->config->getUser(), $this->config->getPassword()));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ecomail.cz Flexibee client (https://github.com/Ecomailcz/flexibee-client)');

        if ($this->config->isSelfSignedCertificate() || $this->config->getAuthSessionId() !== null) {
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

        if (mb_strpos($url, '.pdf') !== false) {
            return new FlexibeePdfResponse($output);
        }

        return ResponseFactory::createFromOutput($output, curl_getinfo($ch, CURLINFO_HTTP_CODE));
    }

}
