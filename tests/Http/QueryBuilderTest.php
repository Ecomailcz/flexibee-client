<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\UrlBuilder;
use EcomailFlexibeeTest\Config as TestConfig;
use Iterator;
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

    public function testCreatesUrlsWithoutQuery(): void
    {
        Assert::assertSame('https://demo.flexibee.eu/login-logout/login.json', $this->urlBuilder->createAuthTokenUrl());
        Assert::assertSame('https://demo.flexibee.eu/c/demo/backup', $this->urlBuilder->createBackupUrl());
        Assert::assertSame('https://demo.flexibee.eu/c/demo.json', $this->urlBuilder->createCompanyUrl());
        Assert::assertSame('https://demo.flexibee.eu/c/demo/restore?name=new%20company', $this->urlBuilder->createRestoreUrl('new company'));
        Assert::assertSame('https://demo.flexibee.eu/c/demo/changes/status.json', $this->urlBuilder->createChangesStatusUrl());
    }

    public function testCreatesUrlsWithQuery(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/login-logout/login.html?limit=0&otp=a+b&returnUrl=https%3A%2F%2Fx.cz%2F%3Fa%3D1',
            $this->urlBuilder->createLoginFormUrl(['otp' => 'a b', 'returnUrl' => 'https://x.cz/?a=1']),
        );
        Assert::assertSame('https://demo.flexibee.eu/c/demo/changes.json?limit=0&start=5', $this->urlBuilder->createChangesUrl(['start' => 5]));
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/adresar.json?limit=5',
            $this->urlBuilder->createUri(filterQueryOrId: null, uriParams: ['limit' => 5]),
        );
        Assert::assertSame(
            "https://demo.flexibee.eu/c/demo/adresar/(id%20in%20(1,2)).json?limit=0&detail=custom%3Aid",
            $this->urlBuilder->createUri('(id in (1,2))', ['detail' => 'custom:id']),
        );
    }

    /**
     * @dataProvider getBaseUrls
     */
    public function testKeepsSchemeUserInfoHostAndPortOfTheBaseUrl(string $baseUrl, string $expectedUrl): void
    {
        $urlBuilder = new UrlBuilder(new Config($baseUrl, 'demo', 'u', 'p', 'adresar', verifySSLCertificate: false));

        Assert::assertSame($expectedUrl, $urlBuilder->createCompanyUrl());
        Assert::assertSame($expectedUrl, $urlBuilder->createCompanyUrl());
    }

    /**
     * @return \Iterator<string, array{string, string}>
     */
    public function getBaseUrls(): Iterator
    {
        yield 'port' => ['https://demo.flexibee.eu:5434', 'https://demo.flexibee.eu:5434/c/demo.json'];
        yield 'trailing slash' => ['https://demo.flexibee.eu/', 'https://demo.flexibee.eu/c/demo.json'];
        yield 'base path is replaced' => ['http://user:pw@host.cz:8080/base', 'http://user:pw@host.cz:8080/c/demo.json'];
        yield 'user without password' => ['https://user@host.cz', 'https://user@host.cz/c/demo.json'];
    }

    public function testEncodesCompanyNameInRestoreUrl(): void
    {
        Assert::assertSame(
            'https://demo.flexibee.eu/c/demo/restore?name=a%26b%23c%20d',
            $this->urlBuilder->createRestoreUrl('a&b#c d'),
        );
    }

    public function testRejectsBaseUrlWithoutSchemeOrHost(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionMessage('FlexiBee URL must contain a scheme and a host.');

        new UrlBuilder(new Config('user:secret@demo.flexibee.eu', 'demo', 'u', 'p', 'adresar', verifySSLCertificate: false));
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
