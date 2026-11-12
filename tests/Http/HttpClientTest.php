<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

use function realpath;
use function sprintf;

final class HttpClientTest extends TestCase
{

    public function testReadsResponseThroughCurl(): void
    {
        $response = (new HttpClient())->request($this->getFixtureUrl(), Method::GET, [], [], [], $this->createConfig(debugMode: false));

        Assert::assertSame(1.0, $response->getVersion());
        Assert::assertSame(['@version' => '1.0', 'adresar' => [['id' => '1']]], $response->getData());
    }

    public function testDebugModeDoesNotBreakTheRequest(): void
    {
        $response = (new HttpClient())->request($this->getFixtureUrl(), Method::GET, [], [], [], $this->createConfig(debugMode: true));

        Assert::assertSame(['@version' => '1.0', 'adresar' => [['id' => '1']]], $response->getData());
    }

    private function getFixtureUrl(): string
    {
        return sprintf('file://%s', realpath(__DIR__ . '/fixtures/adresar.json'));
    }

    private function createConfig(bool $debugMode): Config
    {
        return new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: false, debugMode: $debugMode);
    }

}
