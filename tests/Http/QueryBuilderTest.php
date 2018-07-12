<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Http\QueryBuilder;
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
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.json', $this->queryBuilder->createUriByIdOnly(11));
    }

    public function testCreateUriByCodeOnly(): void
    {
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json', $this->queryBuilder->createUriByCodeOnly('CODE:TEST'));
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/(kod=\'CODE:TEST\').json?test=1', $this->queryBuilder->createUriByCodeOnly('CODE:TEST', ['test' => true]));
    }

    public function testCreateUriByEvidenceOnly(): void
    {
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar.json', $this->queryBuilder->createUriByEvidenceOnly());
    }

    public function testCreateUriByCustomId(): void
    {
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/CODE:TEST.json', $this->queryBuilder->createUriByCustomId('CODE:TEST'));
    }

    public function testCreateUriPdf(): void
    {
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf', $this->queryBuilder->createUriPdf(11));
        $queryParams = [];
        $queryParams['report-name'] = 'test';
        $queryParams['report-lang'] = 'en';
        $this->assertEquals('https://demo.flexibee.eu/c/demo/adresar/11.pdf?report-name=test&report-lang=en', $this->queryBuilder->createUriPdf(11, $queryParams));
    }

}
