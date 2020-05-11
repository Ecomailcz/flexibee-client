<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\Method;

class RecurringContractClient extends Client
{

    private const EVIDENCE = 'smlouvy';

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

    public function generateInvoices(): bool
    {
        $result = $this->callRequest(Method::get(Method::POST), 'generovani-faktur', [], [], [])[0];
        \assert($result instanceof \EcomailFlexibee\Result\EvidenceResult);
        $statusCode = $result->getData()['status_code'];

        return $statusCode >= 200 && $statusCode <= 299;
    }

}