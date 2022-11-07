<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Evidences;

use EcomailFlexibee\CompanyClient;
use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibeeTest\BaseTestClient;
use EcomailFlexibeeTest\Config as TestConfig;
use PHPUnit\Framework\Assert;

final class CompanyEvidenceTest extends BaseTestClient
{

    private CompanyClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new CompanyClient(new Config(TestConfig::HOST, TestConfig::COMPANY, TestConfig::USERNAME, TestConfig::PASSWORD, TestConfig::EVIDENCE, false));
    }

    public function testUnknownCompany(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->client->save([], null);

    }

    public function testGetCompany(): void
    {
        Assert::assertNotEmpty($this->client->getCompany());
    }

    public function testGetCompanies(): void
    {
        $companies = $this->client->getCompanies()->getData();
        Assert::assertArrayHasKey('companies', $companies);
        Assert::assertTrue(count($companies) > 0);
    }

}