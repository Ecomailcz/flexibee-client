<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;

use function array_filter;

final class CompanyClient extends Client
{

    private const EVIDENCE = 'c';

    public function __construct(Config $config)
    {
        parent::__construct(
            $config->getUrl(),
            $config->getCompany(),
            $config->getUser(),
            $config->getPassword(),
            self::EVIDENCE,
            $config->verifySSLCertificate(),
            $config->getAuthSessionId(),
            );
    }

    public function backup(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createBackupUrl(),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

    public function restore(string $companyName, string $data): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createRestoreUrl($companyName),
            Method::get(Method::PUT),
            [$data],
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
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
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

}
