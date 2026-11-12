<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\CompanyClient;
use EcomailFlexibee\Config as ClientConfig;
use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class CompanyClientTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    public function testBackupForwardsDebugModeToTheRequest(): void
    {
        $response = new FlexibeeResponse(200, version: null, success: true);
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(
                Mockery::type('string'),
                Method::GET,
                [],
                [],
                [],
                Mockery::on(static fn (ClientConfig $config): bool => $config->debugMode()),
            )
            ->andReturn($response);
        $client = new CompanyClient($this->createConfig(debugMode: true), $httpClient);

        Assert::assertSame($response, $client->backup());
    }

    public function testRestoreSendsTheBackupWithPut(): void
    {
        $response = new FlexibeeResponse(200, version: null, success: true);
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(Mockery::type('string'), Method::PUT, ['backup-data'], [], [], Mockery::type(ClientConfig::class))
            ->andReturn($response);
        $client = new CompanyClient($this->createConfig(debugMode: false), $httpClient);

        Assert::assertSame($response, $client->restore('new-company', 'backup-data'));
    }

    private function createConfig(bool $debugMode): ClientConfig
    {
        return new ClientConfig(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'c',
            verifySSLCertificate: false,
            debugMode: $debugMode,
        );
    }

}
