<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\Method;

class BankClient extends Client
{

    private const EVIDENCE = 'banka';

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

    public function downloadOnlineListings(): bool
    {
        $result = $this->callRequest(Method::get(Method::POST), 'nacteni-vypisu-online', [], [], [])[0];
        \assert($result instanceof \EcomailFlexibee\Result\EvidenceResult);
        $statusCode = $result->getData()['status_code'];

        return $statusCode >= 200 && $statusCode <= 299;
    }

    public function automaticPairing(): bool
    {
        $result = $this->callRequest(Method::get(Method::POST), 'automaticke-parovani', [], [], [])[0];
        \assert($result instanceof \EcomailFlexibee\Result\EvidenceResult);
        $statusCode = $result->getData()['status_code'];

        return $statusCode >= 200 && $statusCode <= 299;
    }

}
