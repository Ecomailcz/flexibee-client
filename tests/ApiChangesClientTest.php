<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\ApiChangesClient;

final class ApiChangesClientTest extends BaseTestClient
{

    private ApiChangesClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new ApiChangesClient(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, Config::EVIDENCE, false);

    }

}
