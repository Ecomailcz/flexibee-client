<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Evidences;

use EcomailFlexibee\AuthClient;
use EcomailFlexibeeTest\BaseTestClient;
use EcomailFlexibeeTest\Config;
use PHPUnit\Framework\Assert;

final class AuthTest extends BaseTestClient
{

    private AuthClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new AuthClient(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, Config::EVIDENCE, false);

    }

    public function testGetLoginFormUrl(): void
    {
        Assert::assertNotEmpty($this->client->getLoginFormUrl([]));
        $queryWithParameters = $this->client->getLoginFormUrl(['otp' => 'test', 'returnUrl' => $this->faker->url]);
        Assert::assertStringContainsString('otp', $queryWithParameters);
        Assert::assertStringContainsString('returnUrl', $queryWithParameters);
    }

    public function testGetAuthTokenAndMakeSuccessCallWithSessionAuthId(): void
    {
        $authToken = $this->client->getAuthAndRefreshToken()->getData();
        Assert::assertArrayHasKey('refreshToken', $authToken);
        Assert::assertArrayHasKey('authSessionId', $authToken);
        Assert::assertArrayHasKey('csrfToken', $authToken);
    }

    public function testInvalidGetAuthToken(): void
    {
        $client = new AuthClient(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, false, null);
        $flexibeeResponse = $client->getAuthAndRefreshToken();
        Assert::assertFalse($flexibeeResponse->isSuccess());
        $data = $flexibeeResponse->getData();
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertArrayHasKey('reason', $data['errors']);
    }

}