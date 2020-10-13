<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibeeTest\Config as TestConfig;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    private UrlBuilder $urlBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlBuilder = new UrlBuilder(
            new Config(
                TestConfig::HOST,
                TestConfig::COMPANY,
                TestConfig::USERNAME,
                TestConfig::PASSWORD,
                TestConfig::EVIDENCE,
                false,
            ),
        );
    }

    public function testCreateUriByCodeOnly(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/(kod%20eq%20\'CODE:TEST\').json?limit=0',
            $this->urlBuilder->createUriByCodeOnly('CODE:TEST', []),
        );
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/(kod%20eq%20\'CODE:TEST\').json?limit=0&test=1',
            $this->urlBuilder->createUriByCodeOnly('CODE:TEST', ['test' => true]),
        );
    }

    public function testCreateUriByEvidenceOnly(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar.json?limit=0',
            $this->urlBuilder->createUriByEvidenceOnly([]),
        );
    }

    public function testCreateUriByCustomId(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/code:TEST.json?limit=0',
            $this->urlBuilder->createFilterQuery('code:TEST', []),
        );
    }

    public function testCreateUriPdf(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/11.pdf?limit=0',
            $this->urlBuilder->createPdfUrl(11, []),
        );
        $queryParams = [];
        $queryParams['report-name'] = 'test';
        $queryParams['report-lang'] = 'en';
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/11.pdf?limit=0&report-name=test&report-lang=en',
            $this->urlBuilder->createPdfUrl(11, $queryParams),
        );
    }

    public function testCreateUriByEvidenceForSearchQuery(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=10000',
            $this->urlBuilder->createFilterQuery('1', ['limit' => 10000]),
        );
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=10000&start=0',
            $this->urlBuilder->createFilterQuery('1', ['limit' => 10000, 'start' => 0]),
        );
    }

    public function testCreateUriByEvidenceForSearchQueryWithouLimitParameter(): void
    {
        Assert::assertEquals(
            'https://demo.flexibee.eu/c/demo/adresar/1.json?limit=0',
            $this->urlBuilder->createFilterQuery('1', []),
        );
    }

}
