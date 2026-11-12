<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;

class CompanyClient extends Client
{

    private const EVIDENCE = 'c';

    public function __construct(Config $config, ?HttpClient $httpClient = null)
    {
        parent::__construct($config->withEvidence(self::EVIDENCE), $httpClient);
    }

    public function backup(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createBackupUrl(),
            Method::GET,
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
            Method::PUT,
            [$data],
            [],
            [],
            $this->config,
        );
    }

}
