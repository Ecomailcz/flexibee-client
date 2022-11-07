<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use EcomailFlexibee\Http\ResponseDataBuilder;
use EcomailFlexibee\Http\UrlBuilder;

use function array_merge;
use function sprintf;

abstract class Client
{

    protected UrlBuilder $queryBuilder;
    protected HttpClient $httpClient;
    protected Config $config;
    protected ResponseDataBuilder $responseDataBuilder;

    public function __construct(string $url, string $company, string $user, string $password, string $evidence, bool $verifySSLCertificate, ?string $authSessionId = null)
    {
        $this->config = new Config($url, $company, $user, $password, $evidence, $verifySSLCertificate, $authSessionId);
        $this->queryBuilder = new UrlBuilder($this->config);
        $this->responseDataBuilder = new ResponseDataBuilder($this->config);
        $this->httpClient = new HttpClient();
    }

    /**
     * @param array $uriParameters
     * @param array $postFields
     * @param array<string> $headers
     * @return array<\EcomailFlexibee\Result\EvidenceResult>
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function callRequest(Method $httpMethod, int|string|null $queryFilterOrId, array $uriParameters, array $postFields, array $headers): array
    {
        $response = $this->httpClient->request(
            $this->queryBuilder->createUri($queryFilterOrId, $uriParameters),
            $httpMethod,
            $postFields,
            [],
            $headers,
            $this->config,
        );

        return $this->responseDataBuilder->convertResponseToEvidenceResults($response);
    }

    /**
     * @param array $evidenceData
     * @param array $uriParameters
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

        $uriParameters = $dryRun ? array_merge($uriParameters, ['dry-run' => 'true']) : $uriParameters;
        $data = $this->callRequest(Method::get(Method::PUT), null, $uriParameters, [$this->config->getEvidence() => $evidenceData], [])[0]->getData();

        if (!$this->isStoredDataSuccess($data)) {
            throw new EcomailFlexibeeSaveFailed(sprintf('(%d) %s', $data['status_code'], $data['message']));
        }

        if ($this->isInvalidResponse($data)) {
            throw new EcomailFlexibeeSaveFailed($data['message']);
        }

        return new FlexibeeResponse(200, null, true, null, count($data), null, $data, []);
    }

    private function isInvalidResponse(array $data): bool
    {
        return isset($data['success']) && $data['success'] !== 'true' && isset($data['message']);
    }

    private function isStoredDataSuccess(array $dataResponse): bool
    {
        return (isset($dataResponse['created']) && (int) $dataResponse['created'] > 0) || (isset($dataResponse['updated']) && (int) $dataResponse['updated'] > 0);
    }

}
