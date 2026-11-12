<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibeeTest\Config as TestConfig;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{

    private UrlBuilder $urlBuilder;

    public function testCreateUriByCodeOnly(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/(kod%20eq%20\'CODE:TEST\').json?limit=0',
            $this->urlBuilder->createUriByCodeOnly('CODE:TEST', []),
        );
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/(kod%20eq%20\'CODE:TEST\').json?limit=0&test=1',
            $this->urlBuilder->createUriByCodeOnly('CODE:TEST', ['test' => true]),
        );
    }

    public function testCreateUriByEvidenceOnly(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar.json?limit=0',
            $this->urlBuilder->createUriByEvidenceOnly([]),
        );
    }

    public function testCreateUriByCustomId(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/code:TEST.json?limit=0',
            $this->urlBuilder->createFilterQuery('code:TEST', []),
        );
    }

    public function testCreateUriPdf(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/11.pdf?limit=0',
            $this->urlBuilder->createPdfUrl(11, []),
        );
        $queryParams = [];
        $queryParams['report-name'] = 'test';
        $queryParams['report-lang'] = 'en';
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/11.pdf?limit=0&report-name=test&report-lang=en',
            $this->urlBuilder->createPdfUrl(11, $queryParams),
        );
    }

    public function testCreateUriByEvidenceForSearchQuery(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=10000',
            $this->urlBuilder->createFilterQuery('1', ['limit' => 10_000]),
        );
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=10000&start=0',
            $this->urlBuilder->createFilterQuery('1', ['limit' => 10_000, 'start' => 0]),
        );
    }

    public function testCreateUriByEvidenceForSearchQueryWithouLimitParameter(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=0',
            $this->urlBuilder->createFilterQuery('1', []),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlBuilder = new UrlBuilder(
            new Config(
                TestConfig::HOST,
                TestConfig::COMPANY,
                TestConfig::USERNAME,
                TestConfig::PASSWORD,
                TestConfig::EVIDENCE,
                verifySSLCertificate: false,
            ),
        );
    }

}
