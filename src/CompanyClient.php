<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;

class CompanyClient extends Client
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

}
