<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Config;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibeeTest\Config as TestConfig;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    /**
     * @var \EcomailFlexibee\Http\UrlBuilder
     */
    private $urlBuilder;

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
                false
            )
        );
    }

    public function testCreateUriByIdOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.json', $this->urlBuilder->createUriByIdOnly(11));
    }

    public function testCreateUriByCodeOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json', $this->urlBuilder->createUriByCodeOnly('CODE:TEST', []));
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json?test=1', $this->urlBuilder->createUriByCodeOnly('CODE:TEST', ['test' => true]));
    }

    public function testCreateUriByEvidenceOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar.json', $this->urlBuilder->createUriByEvidenceOnly([]));
    }

    public function testCreateUriByCustomId(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/code:TEST.json', $this->urlBuilder->createUriByCode('TEST', []));
    }

    public function testCreateUriPdf(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf', $this->urlBuilder->createUriPdf(11, []));
        $queryParams = [];
        $queryParams['report-name'] = 'test';
        $queryParams['report-lang'] = 'en';
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf?report-name=test&report-lang=en', $this->urlBuilder->createUriPdf(11, $queryParams));
    }

    public function testCreateUriByEvidenceForSearchQuery(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(test).json?limit=10000', $this->urlBuilder->createUriByEvidenceForSearchQuery('test', ['limit' => 10000]));
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(test).json?limit=10000&start=0', $this->urlBuilder->createUriByEvidenceForSearchQuery('test', ['limit' => 10000, 'start' => 0]));
    }

}
