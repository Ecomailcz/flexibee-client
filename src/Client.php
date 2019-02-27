<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Exception\EcomailFlexibeeAnotherError;
use EcomailFlexibee\Exception\EcomailFlexibeeConnectionError;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\QueryBuilder;

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

    /**
     * @return array<string>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getAuthAndRefreshToken(): array
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $queryParameters = [];
        $queryParameters['username'] = $this->user;
        $queryParameters['password'] = $this->password;
        $result = $this->makeRequest(Method::get(Method::POST), $this->queryBuilder->createAuthTokenUrl(), [], $headers, $queryParameters);
        unset($result['success']);

        return $result;
    }
    
    public function deleteById(int $id): void
    {
        $this->makeRequest(Method::get(Method::DELETE), $this->queryBuilder->createUriByIdOnly($id), []);
    }
    
    public function deleteByCustomId(string $id): void
    {
        $this->makeRequest(Method::get(Method::DELETE), $this->queryBuilder->createUriByCustomId($id), []);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $method
     * @param string $url
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function makeRequestPrepared(Method $method, string $url): array
    {
        return $this->makeRequest($method, $this->queryBuilder->createBaseUrl($url));
    }

    /**
     * @param string $id
     * @param array<mixed> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
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
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getByCustomId(string $id, array $queryParams = []): array
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByCustomId($id, $queryParams), []);
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function findById(int $id, array $queryParams = []): array
    {
        try {
            return $this->getById($id, $queryParams);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return [];
        }
    }

    /**
     * @param string $code
     * @param array<mixed> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getByCode(string $code, array $queryParams = []): array
    {
        $code = mb_substr($code, 0, 20);
        $result = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByCodeOnly(strtoupper($code), $queryParams), []);

        return !isset($result[0]) ? [] : $result[0] ;
    }

    /**
     * @param int $id
     * @param array<string> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function getById(int $id, array $queryParams = []): array
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByIdOnly($id, $queryParams), [])[0];
    }

    /**
     * @param string $code
     * @param array<mixed> $queryParams
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function findByCode(string $code, array $queryParams = []): array
    {
        try {
            return $this->getByCode($code, $queryParams);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return [];
        }
    }

    /**
     * @param array<mixed> $evidenceData
     * @param int|null $id
     * @return int
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function save(array $evidenceData, ?int $id): int
    {
        if ($id) {
            $evidenceData['id'] = $id;
        }

        $postData = [];
        $postData[$this->evidence] = $evidenceData;
        $result = $this->makeRequest(Method::get(Method::PUT), $this->queryBuilder->createUriByEvidenceOnly([]), $postData);

        if (count($result) === 0) {
            throw new EcomailFlexibeeSaveFailed();
        }

        return (int) $result[0]['id'];
    }

    /**
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function allInEvidence(): array
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceWithQueryParameters(['limit' => 0]), [], [], []);
    }

    /**
     * @param int $start
     * @param int $limit
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function chunkInEvidence(int $start, int $limit): array
    {
        $queryParameters = [];
        $queryParameters['limit'] = $limit;
        $queryParameters['start'] = $start;

        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceWithQueryParameters($queryParameters), [], [], []);
    }

    /**
     * @param string $query
     * @param array<string> $queryParameters
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function searchInEvidence(string $query, array $queryParameters): array
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByEvidenceForSearchQuery($query, $queryParameters), [], [], []);
    }

    /**
     * @param \EcomailFlexibee\Http\Method $method
     * @param string $uri
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function makeRawRequest(Method $method, string $uri): array
    {
        return $this->makeRequest($method, $this->queryBuilder->createUriByDomainOnly($uri), [], [], []);
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return string
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getPdfById(int $id, array $queryParams = []): string
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriPdf($id, $queryParams), [])['result'];
    }

    /**
     * @param \EcomailFlexibee\Http\Method $httpMethod
     * @param string $url
     * @param array<mixed> $postFields
     * @param array<string> $headers
     * @param array<mixed> $queryParameters
     * @return array<mixed>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     */
    public function makeRequest(Method $httpMethod, string $url, array $postFields = [], array $headers = [], array $queryParameters = []): array
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
        $result = null;

        if (mb_strpos($url, '.pdf') !== false) {
            return ['result' => $output];
        }

        if (is_string($output)) {
            $resultData = json_decode($output, true);
            $result = is_array($resultData) && array_key_exists('winstrom', $resultData) ? $resultData['winstrom'] : $resultData;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200 && $httpCode !== 201) {
            if ($httpCode === 404) {
                $result = is_array($result) ? implode(',', $result): $result;
                $message =  sprintf('%s - %s ', $url, $result);

                throw new EcomailFlexibeeNoEvidenceResult($message);
            }

            // Check authorization

            if ($httpCode === 401) {
                throw new EcomailFlexibeeInvalidAuthorization($this->user, $this->password, $url);
            }

            if ($httpCode === 400 || $httpCode === 500) {
                if ($result['success'] === 'false') {
                    if (!isset($result['results'])) {
                        throw new EcomailFlexibeeRequestError($result['message']);
                    }

                    foreach ($result['results'] as $response) {
                        foreach ($response['errors'] as $error) {
                            $messageLines = [];

                            if (isset($error['code'])) {
                                $messageLines[] = sprintf('code: %s',$error['code']);
                            }

                            if (isset($error['for'])) {
                                $messageLines[] = sprintf('for attribute: %s', $error['for']);
                            }

                            if (isset($error['path'])) {
                                $messageLines[] = sprintf('path: %s',$error['path']);
                            }

                            if (isset($error['message'])) {
                                $messageLines[] = sprintf('message: %s', $error['message']);
                            }

                            throw new EcomailFlexibeeRequestError(implode("\n", $messageLines));
                        }
                    }
                }
            }
        }
    
        if (curl_errno($ch) !== CURLE_OK) {
            throw new EcomailFlexibeeConnectionError(sprintf('cURL error (%s): %s', curl_errno($ch), curl_error($ch)));
        }

        if (!$result) {
            return [];
        }

        if (array_key_exists('success', $result) && !$result['success']) {
            throw new EcomailFlexibeeAnotherError($result);
        }

        if (isset($result['results'])) {
            return $result['results'];
        }

        if (isset($result['@version'])) {
            unset($result['@version']);

            $resultFormatted = array_values($result)[0];

            return is_string($resultFormatted) ? $result : $resultFormatted;
        }

        return $result;
    }

}
