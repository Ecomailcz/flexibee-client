<?php declare(strict_types = 1);

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
            $config->isSelfSignedCertificate(),
            $config->getAuthSessionId(),
            );
    }

    public function backup(): Response
    {
        return $this->makeRequest(
            Method::get(Method::GET),
            $this->queryBuilder->createBackupUrl(),
            [],
        );
    }

    public function restore(string $companyName, string $data): Response
    {
        return $this->makeRequest(
            Method::get(Method::PUT),
            $this->queryBuilder->createRestoreUrl($companyName),
            [$data],
            [],
            [],
            true,
        );
    }

}
