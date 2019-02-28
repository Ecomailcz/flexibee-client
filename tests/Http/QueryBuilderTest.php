<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Http\QueryBuilder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    /**
     * @var \EcomailFlexibee\Http\QueryBuilder
     */
    private $queryBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = new QueryBuilder(Config::COMPANY, Config::EVIDENCE,Config::HOST);
    }

    public function testCreateUriByIdOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.json', $this->queryBuilder->createUriByIdOnly(11));
    }

    public function testCreateUriByCodeOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json', $this->queryBuilder->createUriByCodeOnly('CODE:TEST'));
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json?test=1', $this->queryBuilder->createUriByCodeOnly('CODE:TEST', ['test' => true]));
    }

    public function testCreateUriByEvidenceOnly(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar.json', $this->queryBuilder->createUriByEvidenceOnly([]));
    }

    public function testCreateUriByCustomId(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/code:TEST.json', $this->queryBuilder->createUriByCode('TEST'));
    }

    public function testCreateUriPdf(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf', $this->queryBuilder->createUriPdf(11));
        $queryParams = [];
        $queryParams['report-name'] = 'test';
        $queryParams['report-lang'] = 'en';
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf?report-name=test&report-lang=en', $this->queryBuilder->createUriPdf(11, $queryParams));
    }

    public function testCreateUriByEvidenceForSearchQuery(): void
    {
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(test).json?limit=10000', $this->queryBuilder->createUriByEvidenceForSearchQuery('test', ['limit' => 10000]));
        Assert::assertEquals('https://demo.flexibee.eu/c/demo/adresar/(test).json?limit=10000&start=0', $this->queryBuilder->createUriByEvidenceForSearchQuery('test', ['limit' => 10000, 'start' => 0]));
    }

}
