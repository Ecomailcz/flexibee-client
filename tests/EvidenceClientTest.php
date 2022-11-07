<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\EvidenceClient;

final class EvidenceClientTest extends BaseTestClient
{

    private EvidenceClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new EvidenceClient(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, Config::EVIDENCE, false);

    }

}